<?php

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Html\Components\BreadCrumbs;
use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\DisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page notification
 *
 * This page will display the requested notification, and mark it as read
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Validate GET
$get = GetValidator::new()
    ->select('id')->isOptional()->isDbId()
    ->validate();


// Get notification and update status to read
$notification = Notification::get($get['id']);
$notification->setStatus('READ');


// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        switch (PostValidator::getSubmitButton()) {
            case tr('Mark unread'):
                $notification->setStatus('UNREAD');
                Page::getFlashMessages()->addSuccessMessage(tr('The notification ":notification" has been marked as unread', [':notification' => $notification->getTitle()]));
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
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
    ->setContent($notification->getHtmlDataEntryForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Mark unread'))
        ->addButton(tr('Back'), DisplayMode::secondary, UrlBuilder::getPrevious('/notifications/notifications.html'), true)
        ->addButton(isset_get($go)));


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/notifications/all.html') . '">' . tr('All notifications') . '</a><br>
                          <a href="' . UrlBuilder::getWww('/notifications/unread.html') . '">' . tr('Unread notifications') . '</a><br>
                          <hr>
                          <a href="' . UrlBuilder::getWww('/security/incidents.html') . '">' . tr('Security incidents') . '</a><br>
                          <a href="' . UrlBuilder::getWww('/development/incidents.html') . '">' . tr('Development incidents') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($notification_card, DisplaySize::nine, true)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('Notification'));
Page::setHeaderSubTitle($notification->getId());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                       => tr('Home'),
    '/notifications/all.html' => tr('Notifications'),
    ''                        => tr(':id [:title]', [
        ':title' => $notification->getTitle(),
        ':id'    => $notification->getId()
    ])
]));
