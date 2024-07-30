<?php

/**
 * Class Date
 *
 * This class contains various date handling methods
 *
 * @deprecated Use Phoundation\Date\DateTime instead
 */

declare(strict_types=1);

namespace Phoundation\Date;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Date\Exception\DateException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Throwable;

class Date
{
    /**
     * ???
     *
     * @param int        $timestamp
     * @param null|int   $now
     * @param null|array $periods
     *
     * @return null|string
     * @todo Check this function, what does it do, should it return null or exception on failure??
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
                31536000 => tr('This year'),
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
     *
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
        $timestamp = random_int($min, $max);

        return date("Y-m-d", $timestamp);
    }


    public static function convert(int|float|DateTime|null $date = null, $requested_format = 'human_datetime', $to_timezone = null, $from_timezone = null)
    {
        // Ensure we have some valid date string
        if ($date === null) {
            $date = date('Y-m-d H:i:s');

        } elseif (!$date) {
            // TODO WTF? Return nothing?
            return '';

        } elseif (is_numeric($date)) {
            $date = date('Y-m-d H:i:s', (int) $date);
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
        // Ensure we have a valid format
        switch ($requested_format) {
            case 'ISO8601':
                // no break
            case 'human_datetime_timezone':
                // Use mysql format
                $format = DateTimeInterface::ATOM;
                break;
            case 'mysql':
                // no break
            case 'human_datetime':
                // Use mysql format
                $format = 'Y-m-d H:i:s';
                break;
            case 'iso_date':
                // no break
            case 'human_date':
                $format = 'Y-m-d';
                break;
            default:
                if (Config::get('formats.date.' . $requested_format, false)) {
                    // Use predefined format
                    $format = Config::get('formats.date.' . $requested_format, false);
                } else {
                    // Use custom format
                    $format = $requested_format;
                }
        }
        // Force 12 or 24 hour format?
        if ($requested_format == 'object') {
            // Return a PHP DateTime object
            $format = $requested_format;

        } else {
            switch (Config::get('formats.date.force1224', '24')) {
                case false:
                    break;
                case '12':
                    // Only add AM/PM in case original spec has 24H and no AM/PM
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
                    throw new OutOfBoundsException(tr('Invalid force1224 hour format ":format" specified. Must be either false, "12", or "24". See configuration formats.date.force1224', [
                        ':format' => Config::get('formats.date.' . $requested_format, '24'),
                    ]));
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
                throw new OutOfBoundsException(tr('Specified date variable is a ":type" which is invalid. Should be either scalar or a DateTime object', [
                    ':type' => gettype($date),
                ]));
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
            $return = $date->format($format);
            if (LANGUAGE === 'en') {
                return $return;
            }

            return Date::translate($return);

        } catch (Throwable $e) {
            throw new DateException(tr('Failed to convert to format ":format" because ":e"', [
                ':format' => $format,
                ':e'      => $e,
            ]));
        }
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
     * @param DateTime|string $date
     *
     * @return string The result
     * @example When executed with LANGUAGE "es"
     *          code
     *          $result = Date::translate('Saturday, 14 September 2019');
     *          showdie($result);
     *          /code
     *
     * This would return
     * code
     * Sabado, 18 Septiembre 2019
     * /code
     *
     * @todo    REIMPLEMENT
     * @version 2.8.15: Added function and documentation
     */
    public static function translate(DateTime|string $date): string
    {
        // First check if there are any translatable words in the specified date
        if (!is_string($date)) {
            throw new DateException(tr('The specified date should be a string but is a ":type"', [':type' => gettype($date)]));
        }
        if (!preg_match('/[a-z]/', $date)) {
            return $date;
        }
        // Date contains translatable text, translate all possible words
        $words = [
            'January'   => tr('January'),
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
            'Sat'       => tr('Sat'),
        ];
        foreach ($words as $english => $translation) {
            $date = str_replace($english, $translation, $date);
        }

        return $date;
    }


    /**
     * Returns the HTML for a timezone selection HTML select
     *
     * @param null $params
     *
     * @return string
     * @throws CoreException
     */
    public static function timezonesSelect($params = null)
    {
        Arrays::ensure($params);
        Arrays::default($params, 'name', 'timezone');
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
    }


    /**
     * Returns a list of all timezones supported by PHP
     */
    public static function timezonesList()
    {
        $list = [];
        foreach (timezone_abbreviations_list() as $zones) {
            foreach ($zones as $timezone) {
                if (empty($timezone['timezone_id'])) {
                    continue;
                }
                $list[strtolower($timezone['timezone_id'])] = $timezone['timezone_id'];
            }
        }

        return $list;
    }


    /**
     * Returns true if the specified timezone exists, false if not
     *
     * @param string $timezone
     *
     * @return bool
     */
    public static function timezonesExists(string $timezone): bool
    {
        return isset_get(Date::timezonesList()[strtolower($timezone)]);
    }


    /**
     * Return the specified $date with the specified $interval applied.
     * If $date is null, the default date from Date::convert() will be used
     * $interval must be a valid ISO 8601 specification (see http://php.net/manual/en/dateinterval.construct.php)
     * If $interval is "negative", i.e. preceded by a - sign, the interval will be subtraced. Else the interval will be
     * added Return date will be formatted according to Date::convert() $format
     *
     * @param DateTime     $date
     * @param DateInterval $interval
     * @param string|null  $format
     *
     * @return array|DateTime|string|string[]
     * @throws Exception
     */
    public static function interval(DateTime $date, DateInterval $interval, ?string $format = null)
    {
        throw new UnderConstructionException();
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
     * Returns a string representation of how long ago the specified date was, from now
     *
     * @param Date|DateTime|string|int $date
     * @param bool                     $microseconds
     *
     * @return string
     */
    public static function getAge(Date|DateTime|string|int $date, bool $microseconds = false): string
    {
        if (!is_object($date)) {
            if (is_integer($date)) {
                $timestamp = $date;
                $date      = new DateTime();
                $date->setTimestamp($timestamp);

            } else {
                $date = new DateTime($date);
            }
        }

        $now  = new DateTime();
        $diff = $now->diff($date);

        if ($diff->y) {
            return Strings::plural($diff->y, tr(':count year', [':count' => $diff->y]), tr(':count years', [
                ':count' => $diff->y
            ]));
        }

        if ($diff->m) {
            return Strings::plural($diff->m, tr(':count month', [':count' => $diff->m]), tr(':count months', [
                ':count' => $diff->m
            ]));
        }

        if ($diff->d) {
            return Strings::plural($diff->d, tr(':count day', [':count' => $diff->d]), tr(':count days', [
                ':count' => $diff->d
            ]));
        }

        if ($diff->h) {
            return Strings::plural($diff->h, tr(':count hour', [':count' => $diff->h]), tr(':count hours', [
                ':count' => $diff->h
            ]));
        }

        if ($diff->i) {
            return Strings::plural($diff->i, tr(':count minute', [':count' => $diff->i]), tr(':count minutes', [
                ':count' => $diff->i
            ]));
        }

        if ($diff->s) {
            return Strings::plural($diff->s, tr(':count second', [':count' => $diff->s]), tr(':count seconds', [
                ':count' => $diff->s
            ]));
        }

        if ($microseconds) {
            if (isset($diff->u) and $diff->u) {
                return Strings::plural($diff->s, tr(':count second', [':count' => $diff->s]), tr(':count microseconds', [
                    ':count' => $diff->s
                ]));
            }
        }

        return tr('Right now');
    }
}
