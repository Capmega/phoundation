<?php

declare(strict_types=1);

use Phoundation\Core\Sessions\Session;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Notifications test page
 *
 * This page will send a test notification to this user
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */


// Create the notification, log it, and send it to this user.
Notification::new()
    ->setUrl('/index.html')
    ->setMode(pick_random(DisplayMode::error, DisplayMode::warning, DisplayMode::success, DisplayMode::info, DisplayMode::notice))
    ->setUsersId(Session::getUser()->getId())
    ->setTitle(tr('This is a test notification'))
    ->setMessage(tr('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.'))
    ->setDetails(['test' => Strings::random(16)])
    ->log()
    ->send();

// Redirect to the all notifications page
Page::redirect(UrlBuilder::getPrevious('/notifications/notifications.html'));
