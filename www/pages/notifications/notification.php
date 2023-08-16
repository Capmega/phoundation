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
                Page::redirect();
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
    }
}


// Build the notification form
$notification_card = Card::new()
    ->setMaximizeSwitch(true)
    ->setTitle($notification->getTitle())
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
    ->setContent(tr('Here you can find all new notifications that you have not yet read. These notifications will 
                         also show in the notifications drop down at the top of your screen (Click the bell icon to see 
                         them). You can here click on each notification and view them, or mark them all as read. Once 
                         marked as read they will no longer show up either here or in the notifications drop down at the 
                         top of your screen.'));


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
    ''                        => tr('Notification :id', [':id' => $notification->getId()])
]));
