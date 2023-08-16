<?php

declare(strict_types=1);

use Phoundation\Core\Session;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Notifications\FilterForm;
use Phoundation\Notifications\Notifications;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Button;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page unread
 *
 * This page displays the unread notifications
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Get new notifications object
$notifications = Notifications::new()->markSeverityColumn();


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
$table = $notifications->getHtmlDataTable()
    ->setRowUrl('/notifications/notification-:ROW.html');

$notifications = Card::new()
    ->setTitle('Active notifications')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true)
    ->setButtons(Buttons::new()
        ->addButton(tr('Mark all as read'), DisplayMode::warning, outline: true));

$notifications->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/notifications/all.html') . '">' . tr('All notifications') . '</a><br>
                          <a href="' . UrlBuilder::getWww('/notifications/test.html') . '">' . tr('Send me a test notification') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent(tr('Here you can find all new notifications that you have not yet read. These notifications will 
                         also show in the notifications drop down at the top of your screen (Click the bell icon to see 
                         them). You can here click on each notification and view them, or mark them all as read. Once 
                         marked as read they will no longer show up either here or in the notifications drop down at the 
                         top of your screen.'));


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
