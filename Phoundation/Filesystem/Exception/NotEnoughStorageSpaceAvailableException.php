<?php

/**
 * Class NotEnoughStorageSpaceAvailableException
 *
 * Thrown when the used file system does not have enough storage space available to fulfill the requested action
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem\Exception;

class NotEnoughStorageSpaceAvailableException extends FilesystemException
{
}
