<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Enums;

use Phoundation\Filesystem\Enums\Interfaces\EnumFileOpenModeInterface;


/**
 * enum EnumFileOpenMode
 *
 * This enum contains all possible file modes in a human readable way
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
enum EnumFileOpenMode: string implements EnumFileOpenModeInterface
{
    case readOnly = 'r';
    case readWriteExisting = 'r+';
    case writeOnlyTruncate = 'w';
    case readWriteTruncate = 'w+';
    case writeOnlyAppend = 'a';
    case readWriteAppend = 'a+';
    case writeOnlyCreateOnly = 'x';
    case readWriteCreateOnly = 'x+';
    case writeOnly = 'c';
    case readWrite = 'c+';
    case closeOnExec = 'e';
}
