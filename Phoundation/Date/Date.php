<?php

namespace Phoundation\Date;

use DateTime;
use DateTimeZone;
use Phoundation\Core\Config;
use Phoundation\Date\Exception\DateException;
use Phoundation\Exception\OutOfBoundsException;
use Throwable;



/**
 * Class Date
 *
 * This class contains various date handling methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class Date
{
    public static function convert($date = null, $requested_format = 'human_datetime', $to_timezone = null, $from_timezone = null) {
        /*
         * Ensure we have some valid date string
         */
        if ($date === null) {
            $date = date('Y-m-d H:i:s');

        } elseif (!$date) {
            return '';

        } elseif (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', $date);
        }

        /*
         * Compatibility check!
         * Older systems will still have the timezone specified as a single string, newer as an array
         * The difference between these two can result in systems no longer starting up after an update
         */
        if ($to_timezone === null) {
            $to_timezone = TIMEZONE;
        }

        if ($from_timezone === null) {
            $from_timezone = Config::get('system.timezone.system', 'UTC');
        }

        /*
         * Ensure we have a valid format
         */
        if ($requested_format == 'mysql') {
            /*
             * Use mysql format
             */
            $format = 'Y-m-d H:i:s';

        } elseif (isset($_CONFIG['formats'][$requested_format])) {
            /*
             * Use predefined format
             */
            $format = $_CONFIG['formats'][$requested_format];

        } else {
            /*
             * Use custom format
             */
            $format = $requested_format;
        }

        /*
         * Force 12 or 24 hour format?
         */
        if ($requested_format == 'object') {
            /*
             * Return a PHP DateTime object
             */
            $format = $requested_format;

        } else {
            switch ($_CONFIG['formats']['force1224']) {
                case false:
                    break;

                case '12':
                    /*
                     * Only add AM/PM in case original spec has 24H and no AM/PM
                     */
                    if (($requested_format != 'mysql') and str_contains($format, 'g')) {
                        $format = str_replace('H', 'g', $format);

                        if (!str_contains($format, 'a')) {
                            $format .= ' a';
                        }
                    }

                    break;

                case '24':
                    $format = str_replace('g', 'H', $format);
                    $format = trim(str_replace('a', '', $format));
                    break;

                default:
                    throw new OutOfBoundsException(tr('Invalid force1224 hour format ":format" specified. Must be either false, "12", or "24". See $_CONFIG[formats][force1224]', [':format' => $_CONFIG['formats']['force1224']]));
            }
        }

        /*
         * Create date in specified timezone (if specifed)
         * Return formatted date
         *
         * If specified date is already a DateTime object, then from_timezone will not work
         */
        if (is_scalar($date)) {
            $date = new DateTime($date, ($from_timezone ? new DateTimeZone($from_timezone) : null));

        } else {
            if (!($date instanceof DateTime)) {
                throw new OutOfBoundsException(tr('Specified date variable is a ":type" which is invalid. Should be either scalar or a DateTime object', [':type' => gettype($date)]));
            }
        }

        if ($to_timezone) {
            // Output to specified timezone
            $date->setTimezone(new DateTimeZone($to_timezone));
        }

        try {
            if ($format === 'object') {
                return $date;
            }

            $retval = $date->format($format);

            if (LANGUAGE === 'en') {
                return $retval;
            }

            return Date::translate($retval);

        }catch(Throwable $e) {
            throw new DateException(tr('Failed to convert to format ":format" because ":e"', [':format' => $format, ':e' => $e]));
        }
    }
}