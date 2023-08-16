<?php

use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\NotificationsDropDown;


/**
 * Ajax system/notifications/dropdown.php
 *
 * This ajax call will return the complete HTML for the notifications drop-down
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */


// Gets the notifications dropdown
$dropdown = NotificationsDropDown::new()
    ->setStatus('UNREAD')
    ->setNotificationsUrl('/notifications/notification-:ID.html')
    ->setAllNotificationsUrl('/notifications/unread.html');


// Link the users notifications hash and see if we need to ping
$ping = $dropdown->getNotifications()->linkHash();


// Reply
$reply = [
    'html'  => '<li class="nav-item dropdown notifications">' . $dropdown->render() . '</li>',
    'count' => $dropdown->getNotifications()->getCount(),
    'ping'  => $ping,
];

Json::reply($reply);