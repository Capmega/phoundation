<?php

declare(strict_types=1);

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Img;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Page notification
 *
 * This page will display the requested notification, and mark it as read
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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


// Build the notification form
$notification_card = Card::new()
    ->setCollapseSwitch(true)
    ->setMaximizeSwitch(true)
    ->setTitle(tr('Display data for notification ":name"', [':name' => $notification->getTitle()]))
    ->setContent($notification->getHtmlForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Mark unread'))
        ->addButton(tr('Back'), DisplayMode::secondary, 'prev', true)
        ->addButton(isset_get($delete))
        ->addButton(isset_get($impersonate)));


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


// Build and render the grid
$grid = Grid::new()
    ->addColumn($notification_card, DisplaySize::nine, true)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('Notification'));
Page::setHeaderSubTitle($notification->getTitle());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                       => tr('Home'),
    '/notifications/all.html' => tr('Notifications'),
    ''                        => $notification->getTitle()
]));
