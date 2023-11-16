<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Mounts\Exception;


/**
 * Class NotAMountException
 *
 * Thrown when a specified path is not a mount source or mount target
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class NotAMountException extends MountsException
{
}
