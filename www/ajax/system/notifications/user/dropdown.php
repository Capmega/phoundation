<?php

use Phoundation\Data\Validator\GetValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Components\Button;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\NotificationsDropDown;
use Phoundation\Web\Http\Html\Enums\DisplayMode;


/**
 * Ajax system/notifications/user/dropdown.php
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

$reply = [
    'html'  => '<li class="nav-item dropdown notifications">' . $dropdown->render() . '</li>',
    'count' => $dropdown->getNotifications()->getCount(),
];

Json::reply($reply);