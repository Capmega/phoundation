<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Exception;


/**
 * Class FileOpenException
 *
 * This exception is thrown when a file should be closed but is open
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class FileOpenException extends FilesystemException
{
}
