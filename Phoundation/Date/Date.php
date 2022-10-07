<?php

namespace Phoundation\Date;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
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



    /**
     * ???
     *
     * @todo Check this function, what does it do, should it return null or exception on failure??
     * @param int $timestamp
     * @param null|int $now
     * @param null|array $periods
     * @return null|string
     */
    public static function relative(int $timestamp, ?int $now = null, ?array $periods = null): ?string
    {
        if (!$now) {
            $now = time();
        }

        if (!$periods) {
            $periods = [
                10       => tr('Right now'),
                86400    => tr('Today'),
                604800   => tr('Last week'),
                31536000 => tr('This year')
            ];
        }

        usort($periods);

        foreach ($periods as $time => $label) {
            if ($timestamp < $time) {
                return $label;
            }
        }

        return null;
    }



    /**
     * Return a random date
     *
     * @param null|DateTime $min
     * @param null|DateTime $max
     * @return DateTime
     * @throws Exception
     */
    public static function random(?DateTime $min = null, ?DateTime $max = null): DateTime
    {
        if ($min) {
            $min = new DateTime(Date::convert($min, 'y-m-d'));
            $min = $min->getTimestamp();

        } else {
            $min = 1;
        }

        if ($max) {
            $max = new DateTime(Date::convert($max, 'y-m-d'));
            $max = $max->getTimestamp();

        } else {
            $max = 2147483647;
        }

        $timestamp  = mt_rand($min, $max);
        return date("Y-m-d", $timestamp);
    }


    /**
     * Returns the HTML for a timezone selection HTML select
     *
     * @param null $params
     * @return string
     * @throws CoreException
     */
    public static function timezonesSelect($params = null)
    {
        try {
            Arrays::ensure($params);
            array_default($params, 'name', 'timezone');

            $params['resource'] = Date::timezonesList();
            asort($params['resource']);

// :DELETE: Remove MySQL requirement because production users will not have access to "mysql" database
            //$params['resource'] = Sql::query('SELECT   LCASE(SUBSTR(`Name`, 7)) AS `id`,
            //                                                SUBSTR(`Name`, 7)  AS `name`
            //
            //                                 FROM     `mysql`.`time_zone_name`
            //
            //                                 WHERE    `Name` LIKE "posix%"
            //
            //                                 ORDER BY `id`');

            return html_select($params);

        }catch(Exception $e) {
            throw new CoreException(tr('Date::timezones_select(): Failed'), $e);
        }
    }



    /**
     * Returns true if the specified timezone exists, false if not
     *
     * @param string $timezone
     * @return bool
     */
    public static function timezonesExists(string $timezone): bool
    {
        return isset_get(Date::timezonesList()[strtolower($timezone)]);
    }



    /**
     * Returns a list of all timezones supported by PHP
     */
    public static function timezonesList() {
        $list = [];

        foreach (timezone_abbreviations_list() as $zones) {
            foreach ($zones as $timezone) {
                if (empty($timezone['timezone_id'])) continue;

                $list[strtolower($timezone['timezone_id'])] = $timezone['timezone_id'];
            }
        }

        return $list;
    }


    /**
     * Return the specified $date with the specified $interval applied.
     * If $date is null, the default date from Date::convert() will be used
     * $interval must be a valid ISO 8601 specification (see http://php.net/manual/en/dateinterval.construct.php)
     * If $interval is "negative", i.e. preceded by a - sign, the interval will be subtraced. Else the interval will be added
     * Return date will be formatted according to Date::convert() $format
     *
     * @param DateTime $date
     * @param DateInterval $interval
     * @param string|null $format
     * @return array|DateTime|string|string[]
     * @throws Exception
     */
    public static function interval(DateTime $date, DateInterval $interval, ?string $format = null)
    {
        $date = Date::convert($date, 'd-m-Y');
        $date = new DateTime($date);

        if (substr($interval, 0, 1) == '-') {
            $date->sub(new DateInterval(substr($interval, 1)));

        } else {
            $date->add(new DateInterval($interval));
        }

        return Date::convert($date, $format);
    }



    /**
     * Translate the specified day and month names
     *
     * Translate the date
     * Seriously PHP, you couldn't add either translatable dates to
     * date() or have strftime() have the same format? strftime() lacks
     * loads of items, so it cant be used, and date() cannot have
     * translated dates. Great!
     *
     * So for now we have this barf solution
     *
     * @todo REIMPLEMENT
     * @version 2.8.15: Added function and documentation
     * @example When executed with LANGUAGE "es"
     * code
     * $result = Date::translate('Saturday, 14 September 2019');
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * Sabado, 18 Septiembre 2019
     * /code
     *
     * @param DateTime|string $date
     * @return string The result
     */
    public static function translate(DateTime|string $date): string
    {
        try {
            /*
             * First check if there are any translatable words in the specified date
             */
            if (!is_string($date)) {
                throw new CoreException(tr('Date::translate(): The specified date should be a string but is a ":type"', array(':type' => gettype($date))), 'invalid');
            }

            if (!preg_match('/[a-z]/', $date)) {
                return $date;
            }

            /*
             * Date contains translatable text, translate all possible words
             */
            $words = array('January'   => tr('January'),
                'February'  => tr('February'),
                'March'     => tr('March'),
                'April'     => tr('April'),
                'May'       => tr('May'),
                'June'      => tr('June'),
                'July'      => tr('July'),
                'August'    => tr('August'),
                'September' => tr('September'),
                'October'   => tr('October'),
                'November'  => tr('November'),
                'December'  => tr('December'),
                'Jan'       => tr('Jan'),
                'Feb'       => tr('Feb'),
                'Mar'       => tr('Mar'),
                'Apr'       => tr('Apr'),
                'May'       => tr('May'),
                'Jun'       => tr('Jun'),
                'Jul'       => tr('Jul'),
                'Aug'       => tr('Aug'),
                'Sep'       => tr('Sep'),
                'Oct'       => tr('Oct'),
                'Nov'       => tr('Nov'),
                'Dec'       => tr('Dec'),
                'Sunday'    => tr('Sunday'),
                'Monday'    => tr('Monday'),
                'Tuesday'   => tr('Tuesday'),
                'Wednesday' => tr('Wednesday'),
                'Thursday'  => tr('Thursday'),
                'Friday'    => tr('Friday'),
                'Saturday'  => tr('Saturday'),
                'Sun'       => tr('Sun'),
                'Mon'       => tr('Mon'),
                'Tue'       => tr('Tue'),
                'Wed'       => tr('Wed'),
                'Thu'       => tr('Thu'),
                'Fri'       => tr('Fri'),
                'Sat'       => tr('Sat'));

            foreach ($words as $english => $translation) {
                $date = str_replace($english, $translation, $date);
            }

            return $date;

        }catch(Exception $e) {
            throw new CoreException(tr('Date::translate(): Failed'), $e);
        }
    }




    /*
     *
     */
    function date_convert($date = null, $requested_format = 'human_datetime', $to_timezone = null, $from_timezone = null)
    {
        global $_CONFIG;

        try {
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
                $from_timezone = $_CONFIG['timezone']['system'];
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
                        if (($requested_format != 'mysql') and strstr($format, 'g')) {
                            $format = str_replace('H', 'g', $format);

                            if (!strstr($format, 'a')) {
                                $format .= ' a';
                            }
                        }

                        break;

                    case '24':
                        $format = str_replace('g', 'H', $format);
                        $format = trim(str_replace('a', '', $format));
                        break;

                    default:
                        throw new OutOfBoundsException(tr('date_convert(): Invalid force1224 hour format ":format" specified. Must be either false, "12", or "24". See $_CONFIG[formats][force1224]', array(':format' => $_CONFIG['formats']['force1224'])), 'invalid');
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
                    throw new OutOfBoundsException(tr('date_convert(): Specified date variable is a ":type" which is invalid. Should be either scalar or a DateTime object', array(':type' => gettype($date))), 'invalid');
                }
            }

            if ($to_timezone) {
                /*
                 * Output to specified timezone
                 */
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

                load_libs('date');
                return date_translate($retval);

            } catch (Exception $e) {
                throw new OutOfBoundsException(tr('date_convert(): Failed to convert to format ":format" because ":e"', array(':format' => $format, ':e' => $e)), 'invalid');
            }

        } catch (Exception $e) {
            throw new OutOfBoundsException('date_convert(): Failed', $e);
        }
    }
}