<?php

declare(strict_types=1);

namespace Phoundation\Core\Enums;

use Phoundation\Core\Interfaces\EnumMatchModeInterface;


/**
 * Enum EnumMatchMode
 *
 * This enum contains the possible match modes when trying to find or filter data on keys
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @package Phoundation\Core
 */
enum EnumMatchMode: string implements EnumMatchModeInterface
{
    case full    = 'full';
    case partial = 'partial';
    case regex   = 'regex';
}
