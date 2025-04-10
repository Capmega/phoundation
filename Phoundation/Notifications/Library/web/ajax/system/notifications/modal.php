<?php

/**
 * Ajax call system/notifications/modal.php
 *
 * This ajax call will return the contents of the specified notifications id for use with notification modals
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\JsonPage;

// Validate the ID
$get = GetValidator::new()
                   ->select('id')->isDbId()
                   ->validate();


// Get notification
$notification = Notification::new()->load($get['id']);


// Update notification status to READ
if (!Session::isImpersonated()) {
    // This is not allowed while impersonating!
    $notification->setStatus('READ');
}


// Build modal information
if ($notification->getUrl()) {
    $button = Button::new()
                    ->setMode(EnumDisplayMode::primary)
                    ->setAnchorUrl($notification->getUrl())
                    ->setContent(tr('Go'))
                    ->render();
}


// Send reply
$reply = [
    'title'   => $notification->getTitle(),
    'body'    => $notification->getMessage(),
    'url'     => $notification->getUrl(),
    'buttons' => isset_get($button) . Button::new()
                                            ->setOutlined(true)
                                            ->setAnchorUrl(Url::new('notifications/notification+' . $notification->getId() . '.html')->makeWww())
                                            ->setContent(tr('See notification details'))
                                            ->render(),
];

JsonPage::new()->reply($reply);
