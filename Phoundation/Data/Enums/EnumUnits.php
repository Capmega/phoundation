<?php

/**
 * Enum EnumUnits
 *
 * Used to track whether we're using metric or imperial (seriously? 2024? still?) units
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Enums;

enum EnumUnits: string
{
    case metric   = 'metric';
    case imperial = 'imperial';
}
