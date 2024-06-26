<?php

/**
 * Class PushOver
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Notifications
 */

declare(strict_types=1);

namespace Phoundation\Notifications\PushNotifications;

use Phoundation\Core\Sessions\Config;
use Phoundation\Notifications\Exception\NotificationsException;
use Phoundation\Notifications\PushNotifications\Interfaces\PushNotificationInterface;
use Serhiy\Pushover\Application;

class PushOver extends Application implements PushNotificationInterface
{
    /**
     * PushOver class constructor
     *
     * @param string|null $token
     */
    public function __construct(?string $token = null) {
        $token = $token ?? Config::getString('notifications.push.keys.application', '');

        if (empty($token)) {
            throw new NotificationsException(tr('Cannot instantiate PushOver object, no aplication key specified or configured in "notifications.push.keys.application"'));
        }

        parent::__construct($token);
    }
}
