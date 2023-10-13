<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Exception;


/**
 * Class FileEofException
 *
 * This exception is thrown when a read action on a file was performed while the file pointer is at the End Of File
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Filesystem
 */
class FileEofException extends FilesystemException
{
}
