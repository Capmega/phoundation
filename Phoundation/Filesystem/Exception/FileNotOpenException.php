<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Exception;


/**
 * Class FileNotOpenException
 *
 * This exception is thrown when a file is closed while it should be open
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class FileNotOpenException extends FilesystemException
{
}
