<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Mounts\Exception;

/**
 * Class NotMountedException
 *
 * Thrown when a specified path cannot be unmounted because its busy
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */
class UnmountBusyException extends MountsException
{
}
