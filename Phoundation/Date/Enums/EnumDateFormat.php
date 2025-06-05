<?php

/**
 * Enum EnumDateFormat
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date\Enums;


enum EnumDateFormat: string
{
    case user_time      = 'user_time';
    case user_date      = 'user_date';
    case user_datetime  = 'user_datetime';
    case human_time     = 'human_time'; // DEPRECATED
    case human_date     = 'human_date'; // DEPRECATED
    case human_datetime = 'human_datetime'; // DEPRECATED
    case user_date_time = 'user_date_time'; // DEPRECATED
    case iso_date       = 'iso_date';
    case system_date    = 'system_date';
    case mysql_date     = 'mysql_date';
    case iso_date_time  = 'iso_date_time';
    case mysql          = 'mysql';
    case mysql_datetime = 'mysql_datetime';
    case file           = 'file';
}
