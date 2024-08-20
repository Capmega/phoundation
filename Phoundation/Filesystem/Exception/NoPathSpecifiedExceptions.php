<?php

/**
 * Class NoPathSpecifiedExceptions
 *
 * This exception is thrown when a Filesystem object like FsPath, FsFile, or FsDirectory is created with no path
 * specified
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Exception;

class NoPathSpecifiedExceptions extends FilesystemException
{
}
