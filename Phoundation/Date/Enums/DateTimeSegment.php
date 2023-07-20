<?php

namespace Phoundation\Date\Enums;

use Phoundation\Date\Enums\Interfaces\DateTimeSegmentInterface;


/**
 * Enum DateSegment
 *
 * This is a list of the date segments that can be processed
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
enum DateTimeSegment: string implements DateTimeSegmentInterface
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