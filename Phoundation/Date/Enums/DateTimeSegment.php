<?php

/**
 * Enum DateSegment
 *
 * This is a list of the date segments that can be processed
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */

declare(strict_types=1);

namespace Phoundation\Date\Enums;

enum DateTimeSegment: string
{
    case millennium  = 'millennium';
    case century     = 'century';
    case decennium   = 'decennium';
    case year        = 'year';
    case month       = 'month';
    case week        = 'week';
    case day         = 'day';
    case hour        = 'hour';
    case minute      = 'minute';
    case second      = 'second';
    case millisecond = 'millisecond';
    case microsecond = 'microsecond';
}
