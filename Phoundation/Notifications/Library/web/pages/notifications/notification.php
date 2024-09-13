<?php

/**
 * Page notification
 *
 * This page will display the requested notification, and mark it as read
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;


// Validate GET
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();


// Get notification and update status to read
$notification = Notification::load($get['id']);
$notification->setStatus('READ');


// Validate POST and submit
if (Request::isPostRequestMethod()) {
    try {
        switch (PostValidator::new()->getSubmitButton()) {
            case tr('Mark unread'):
                $notification->setStatus('UNREAD');
                Response::getFlashMessagesObject()->addSuccess(tr('The notification ":notification" has been marked as unread', [
                    ':notification' => $notification->getTitle()
                ]));
        }
    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessagesObject()->addMessage($e);
    }
}


// Do we have a URL?
if ($notification->getUrl()) {
    $go = Button::new()
                ->setFloatRight(true)
                ->setValue(tr('Go'))
                ->setAnchorUrl($notification->getUrl());
}

// Build the notification form
$notification_card = Card::new()
    ->setCollapseSwitch(true)
    ->setMaximizeSwitch(true)
    ->setTitle($notification->getTitle())
    ->setContent($notification->getHtmlDataEntryFormObject()->render())
    ->setButtons(Buttons::new()
                        ->addButton(tr('Mark unread'))
                        ->addButton(tr('Back'), EnumDisplayMode::secondary, Url::getPrevious('/notifications/notifications.html'), true)
                        ->addButton(isset_get($go)));


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . Url::getWww('/notifications/all.html') . '">' . tr('All notifications') . '</a><br>
                          <a href="' . Url::getWww('/notifications/unread.html') . '">' . tr('Unread notifications') . '</a><br>
                          <hr>
                          <a href="' . Url::getWww('/security/incidents.html') . '">' . tr('Security incidents') . '</a><br>
                          <a href="' . Url::getWww('/development/incidents.html') . '">' . tr('Development incidents') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addGridColumn($notification_card, EnumDisplaySize::nine, true)
    ->addGridColumn($relevant->render() . '<br>' . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();

// Set page meta data
Response::setHeaderTitle(tr('Notification'));
Response::setHeaderSubTitle($notification->getId());
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                       => tr('Home'),
    '/notifications/all.html' => tr('Notifications'),
    ''                        => tr(':id [:title]', [
        ':title' => $notification->getTitle(),
        ':id'    => $notification->getId()
    ])
]));
