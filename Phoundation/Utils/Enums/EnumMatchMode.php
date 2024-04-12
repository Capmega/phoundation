<?php
/**
 * Enum EnumMatchMode
 *
 * This enum contains the possible match modes when trying to find or filter data on keys
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package   Phoundation\Utils
 */

declare(strict_types=1);

namespace Phoundation\Utils\Enums;

enum EnumMatchMode: string
{
    case not             = 'not';             // The value must NOT match the needle
    case full            = 'full';            // The value must match the needle completely, but loosely (So "0" is 0)
    case regex           = 'regex';           // The value must match this regular expression needle
    case strict          = 'strict';          // The value must match the needle, and strict (So "0" is NOT 0)
    case case_ignore     = 'case_ignore';     // The value must match the needle in a loose, case-insensitive comparison
    case contains        = 'contains';        // The value must contain the needle
    case contains_not    = 'contains_not';    // The value must NOT contain the needle
    case starts_with     = 'starts_with';     // The value must start with the needle
    case starts_not_with = 'starts_not_with'; // The value must NOT start with the needle
    case ends_with       = 'ends_with';       // The value must end with the needle
    case ends_not_with   = 'ends_not_with';   // The value must NOT end with the needle
}
