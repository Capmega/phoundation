<?php

declare(strict_types=1);

namespace Phoundation\Utils\Enums;

use Phoundation\Utils\Enums\Interfaces\EnumMatchModeInterface;


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
enum EnumMatchMode: string implements EnumMatchModeInterface
{
    case not             = 'not';
    case full            = 'full';
    case regex           = 'regex';
    case strict          = 'strict';
    case case_ignore     = 'case_ignore';
    case contains        = 'contains';
    case contains_not    = 'contains_not';
    case starts_with     = 'starts_with';
    case starts_not_with = 'starts_not_with';
    case ends_with       = 'ends_with';
    case ends_not_with   = 'ends_not_with';
}
