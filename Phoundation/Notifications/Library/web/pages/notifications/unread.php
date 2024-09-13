<?php

/**
 * Page notifications/unread
 *
 * This page displays the unread notifications
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notifications
 */


declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Notifications\FilterForm;
use Phoundation\Notifications\Notifications;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Get a new notifications object
$notifications = Notifications::new()->markSeverityColumn();


// Process POST requests
if (Request::isPostRequestMethod()) {
    if (PostValidator::new()->getSubmitButton() === tr('Mark all as read')) {
//        $notifications->setStatus('READ');

        sql()->query('UPDATE `notifications`
                      SET    `status`   = "READ"
                      WHERE  `users_id` = :users_id', [
                          ':users_id' => Session::getUserObject()->getId()
        ]);

        Response::getFlashMessagesObject()->addSuccess(tr('All your notifications have been marked as read'));
        Response::redirect();
    }
}


// Build the page content


// Build incidents filter card
$filters      = FilterForm::new()->apply();
$filters_card = Card::new()
                    ->setCollapseSwitch(true)
                    ->setTitle('Notifications filters')
                    ->setContent($filters->render())
                    ->useForm(true);


// Build notifications table
$notifications_card = Card::new()
                          ->setTitle('Active notifications')
                          ->setSwitches('reload')
                          ->setContent($notifications->getHtmlDataTableObject()
                                                     ->setRowUrl('/notifications/notification+:ROW.html'))
                          ->useForm(true)
                          ->setButtons(Buttons::new()
                                              ->addButton(tr('Mark all as read'), EnumDisplayMode::warning, outline: true));

$notifications_card->getForm()
                   ->setAction(Url::getCurrent())
                   ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$relevant_card = Card::new()
                     ->setMode(EnumDisplayMode::info)
                     ->setTitle(tr('Relevant links'))
                     ->setContent('<a href="' . Url::getWww('/notifications/all.html') . '">' . tr('All notifications') . '</a><br>
                                   <a href="' . Url::getWww('/notifications/test.html') . '">' . tr('Send me a test notification') . '</a>');


// Build documentation
$documentation_card = Card::new()
                          ->setMode(EnumDisplayMode::info)
                          ->setTitle(tr('Documentation'))
                          ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Notifications'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Notifications')
]));


// Build and render the page grid
return Grid::new()
    ->addGridColumn($filters_card->render() . $notifications_card->render(), EnumDisplaySize::nine)
    ->addGridColumn($relevant_card->render() . '<br>' . $documentation_card->render(), EnumDisplaySize::three);
