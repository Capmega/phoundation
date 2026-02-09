<?php

/**
 * Enum EnumSpecialKeys
 *
 * Contains the non number, letter, special character keys on IBM compatible keyboards
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum EnumSpecialKeys: string
{
    case esc          = 'esc';
    case enter        = 'enter';
    case delete       = 'delete';
    case tab          = 'tab';
    case os           = 'os'; // Operating System special key, like the M$ Windows key on IBM keyboard, or the command key on apple keyboards
    case insert       = 'insert';
    case home         = 'home';
    case end          = 'end';
    case page_up      = 'page_up';
    case page_down    = 'page_down';
    case pause        = 'pause';
    case print_screen = 'print_screen';
    case f1           = 'f1';
    case f2           = 'f2';
    case f3           = 'f3';
    case f4           = 'f4';
    case f5           = 'f5';
    case f6           = 'f6';
    case f7           = 'f7';
    case f8           = 'f8';
    case f9           = 'f9';
    case f10          = 'f10';
    case f11          = 'f11';
    case f12          = 'f12';
}
