<?php

declare(strict_types=1);

use Phoundation\Core\Session;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Notifications\FilterForm;
use Phoundation\Notifications\Notifications;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page all
 *
 * This page displays all notifications
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Get new notifications object
$notifications = Notifications::new()
    ->markSeverityColumn();

$notifications->getQueryBuilder()
    ->addSelect('`id`, `title`, `status`, `mode` AS `severity`, `priority`, `created_on`')
    ->addWhere('`users_id` = :users_id', [':users_id' => Session::getUser()->getId()])
    ->addOrderBy('`created_by` ASC');


// Process POST requests
if (Page::isPostRequestMethod()) {
    if (PostValidator::getSubmitButton() === tr('Mark all as read')) {
//        $notifications->setStatus('READ');
        sql()->query('UPDATE `notifications` SET `status` = "READ" WHERE `users_id` = :users_id', [':users_id' => Session::getUser()->getId()]);
        Page::getFlashMessages()->addSuccessMessage(tr('All your notifications have been marked as read'));
        Page::redirect();
    }
}

// Build the page content


// Build incidents filter card
$filters_content = FilterForm::new();

$filters = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle('Notifications filters')
    ->setContent($filters_content->render())
    ->useForm(true);


// Build notifications table
$table = $notifications
    ->getHtmlDataTable()
        ->setRowUrl('/notifications/notification-:ROW.html')
        ->setAnchorClasses('notification open-modal');

$table->getAnchorDataAttributes()->add(':ROW', 'id');
;


// Build notifications card
$notifications = Card::new()
    ->setTitle('Active notifications')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true)
    ->setButtons(Buttons::new()
        ->addButton(tr('Mark all as read')));

$notifications->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/notifications/unread.html') . '">' . tr('Unread notifications') . '</a><br>
                          <a href="' . UrlBuilder::getWww('/notifications/test.html') . '">' . tr('Send me a test notification') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters->render() . $notifications->render(), DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Notifications'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Notifications')
]));
