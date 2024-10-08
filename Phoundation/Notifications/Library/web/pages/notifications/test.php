<?php

declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


/**
 * Notifications test page
 *
 * This page will send a test notification to this user
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */


// Create the notification, log it, and send it to this user.
Notification::new()
    ->setUrl('/index.html')
    ->setMode(pick_random_argument(EnumDisplayMode::error, EnumDisplayMode::warning, EnumDisplayMode::success, EnumDisplayMode::info, EnumDisplayMode::notice))
    ->setUsersId(Session::getUser()->getId())
    ->setTitle(tr('This is a test notification'))
    ->setMessage(tr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'))
    ->setDetails(['test' => Strings::getRandom(16)])
    ->log()
    ->send();

// Redirect to the all notifications page
Response::redirect(UrlBuilder::getPrevious('/notifications/notifications.html'));
