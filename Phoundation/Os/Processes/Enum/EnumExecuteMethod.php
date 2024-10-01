<?php

/**
 * Enum EnumExecuteMethod
 *
 * This enum defines the ways processes can be executed and return their output
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Enum;


enum EnumExecuteMethod: string
{
    case background     = 'background';
    case passthru       = 'passthru';
    case log            = 'log';
    case returnString   = 'return string';
    case returnArray    = 'return array';
    case returnIterator = 'return iterator';
    case noReturn       = 'return nothing';
}
