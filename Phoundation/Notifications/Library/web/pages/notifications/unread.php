<?php

/**
 * Page notifications/unread
 *
 * This page displays the unread notifications
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Notifications
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Exception\AccessDeniedException;
use Phoundation\Notifications\FilterForm;
use Phoundation\Notifications\Notifications;
use Phoundation\Web\Html\Components\AnchorBlock;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\Breadcrumbs\Breadcrumb;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Build "incidents filter" card
$o_filters      = FilterForm::new();
$o_filters_card = Card::new()
                      ->setCollapseSwitch(true)
                      ->setTitle('Notifications filters')
                      ->setContent($o_filters);


// Process GET arguments
GetValidator::new()->validate();


// Get a new notifications object
$notifications = Notifications::new()->markSeverityColumn();


// Process POST requests
if (Request::isPostRequestMethod()) {
    if (Request::getSubmitButton() === tr('Mark all as read')) {
        if (Session::isImpersonated()) {
            throw new AccessDeniedException(tr('Marking all notifications as READ is not allowed when impersonating accounts'));
        }

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


// Build "notifications" table
$o_notifications_card = Card::new()
                            ->setTitle('Active notifications')
                            ->setSwitches('reload')
                            ->setContent($notifications->getHtmlDataTableObject()
                                                       ->setRowUrl('/notifications/notification+:ROW.html'))
                            ->useForm(true)
                            ->setButtonsObject(
                                  Session::isImpersonated()
                                ? null
                                : Buttons::new()->addButton(tr('Mark all as read'), EnumDisplayMode::warning, outline: true)
                            );

$o_notifications_card->getForm()
                   ->setAction(Url::newCurrent())
                   ->setRequestMethod(EnumHttpRequestMethod::post);


// Build relevant links
$o_relevant_card = Card::new()
                       ->setMode(EnumDisplayMode::info)
                       ->setTitle(tr('Relevant links'))
                       ->setContent(AnchorBlock::new(Url::new('/notifications/all.html')->makeWww(), tr('All notifications')) .
                                    AnchorBlock::new(Url::new('/notifications/test.html')->makeWww(), tr('Send me a test notification')));


// Build documentation
$o_documentation_card = Card::new()
                            ->setMode(EnumDisplayMode::info)
                            ->setTitle(tr('Documentation'))
                            ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Set page meta data
Response::setHeaderTitle(tr('Notifications'));
Response::setBreadcrumbs([
    Breadcrumb::new('/'                      , tr('Home')),
    Breadcrumb::new('/notifications/all.html', tr('Notifications')),
    Breadcrumb::new(''                       , tr('Unread'))
]);


// Render and return the page grid
return Grid::new()
           ->addGridColumn($o_filters_card . $o_notifications_card, EnumDisplaySize::nine)
           ->addGridColumn($o_relevant_card . $o_documentation_card, EnumDisplaySize::three);
