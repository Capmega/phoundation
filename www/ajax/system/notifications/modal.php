<?php

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Enums\DisplayMode;


/**
 * Ajax system/notifications/modal.php
 *
 * This ajax call will return the contents of the specified notifications id for use with notification modals
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */


// Validate the ID
$get = GetValidator::new()
    ->select('id')->isDbId()
    ->validate();


// Update notification status to READ and build modal information and send reply
$notification = Notification::get($get['id'])->setStatus('READ');

if ($notification->getUrl()) {
    $button = Button::new()
        ->setMode(DisplayMode::primary)
        ->setAnchorUrl($notification->getUrl())
        ->setContent(tr('Go'))
        ->render();
}

$reply = [
    'title'   => $notification->getTitle(),
    'body'    => $notification->getMessage(),
    'url'     => $notification->getUrl(),
    'buttons' => isset_get($button) .
        Button::new()
            ->setOutlined(true)
            ->setAnchorUrl('http://mediweb.medinet.ca.local/en/notifications/notification-' . $notification->getId() . '.html')
            ->setContent(tr('See details'))
            ->render()];

Json::reply($reply);