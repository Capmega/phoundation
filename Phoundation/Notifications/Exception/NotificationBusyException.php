<?php

declare(strict_types=1);

namespace Phoundation\Notifications\Exception;

/**
 * Class NotificationBusyException
 *
 * This exception is thrown when the notifications sending mechanism somehow causes another notification, which would
 * result in an endless loop
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */
class NotificationBusyException extends NotificationsException
{
}
