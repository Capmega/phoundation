<?php

/**
 * Ajax system/notifications/dropdown.php
 *
 * This ajax call will return the complete HTML for the notifications drop-down
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Widgets\NotificationsDropDown;


// Gets the notification dropdown
$dropdown = NotificationsDropDown::new()
                                 ->setStatus('UNREAD')
                                 ->setNotificationsUrl('/notifications/notification-:ID.html')
                                 ->setAllNotificationsUrl('/notifications/unread.html');


// Link the users notifications hash and see if we need to ping
$ping = $dropdown->getNotifications()
                 ->linkHash();

// Reply
$reply = [
    'html'  => '<li class="nav-item dropdown notifications">' . $dropdown->render() . '</li>',
    'count' => $dropdown->getNotifications()
                        ->getCount(),
    'ping'  => $ping,
];

Json::new()->reply($reply);
