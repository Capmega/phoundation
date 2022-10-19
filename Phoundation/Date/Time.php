<?php

namespace Phoundation\Date;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Class Time
 *
 * This class contains various time handling methods
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class Time
{
    /**
     * Returns the difference in times with the pointed precision
     *
     * @param float $start
     * @param float $stop
     * @param string $precision
     * @param int $decimals
     * @return string
     */
    public static function difference(float $start, float $stop, string $precision = 'auto', int $decimals = 2): string
    {
        $time = $stop - $start;
        $ceil = ceil($time);

        switch ($precision) {
            case 'second':
                // no-break
            case 'seconds':
                $time = number_format($time, $decimals);
                return Strings::plural($ceil, tr(':time second', [':time' => $time]), tr(':time seconds', [':time' => $time]));

            case 'minute':
                // no-break
            case 'minutes':
                $time = number_format($time / 60, $decimals);
                return Strings::plural($ceil, tr(':time minute', [':time' => $time]), tr(':time minutes', [':time' => $time]));

            case 'hour':
                // no-break
            case 'hours':
                $time = number_format($time / 3600, $decimals);
                return Strings::plural($ceil, tr(':time hour', [':time' => $time]), tr(':time hours', [':time' => $time]));

            case 'day':
                // no-break
            case 'days':
                $time = number_format($time / 86400, $decimals);
                return Strings::plural($ceil, tr(':time day', [':time' => $time]), tr(':time days', [':time' => $time]));

            case 'week':
                // no-break
            case 'weeks':
                $time = number_format($time / 604800, $decimals);
                return Strings::plural($ceil, tr(':time week', [':time' => $time]), tr(':time weeks', [':time' => $time]));

            case 'month':
                // no-break
            case 'months':
                /*
                 * NOTE: Month is assumed 30 days!
                 */
                $time    = number_format($time / 2592000, $decimals);
                return Strings::plural($ceil, tr(':time month', [':time' => $time]), tr(':time months', [':time' => $time]));

            case 'year':
                // no-break
            case 'years':
                /*
                 * NOTE: Year is assumed 365 days!
                 */
                $time    = number_format($time / 31536000, $decimals);
                return Strings::plural($ceil, tr(':time year', [':time' => $time]), tr(':time years', [':time' => $time]));

            case 'auto':
                if ($time < 60) {
                    /*
                     * Seconds
                     */
                    return Time::difference($start, $stop, 'seconds', $decimals);
                }

                if ($time / 60 < 60) {
                    /*
                     * Minutes
                     */
                    return Time::difference($start, $stop, 'minutes', $decimals);
                }

                if ($time / 3600 < 24) {
                    /*
                     * Hours
                     */
                    return Time::difference($start, $stop, 'hours', $decimals);
                }

                if ($time / 86400 < 7) {
                    /*
                     * Days
                     */
                    return Time::difference($start, $stop, 'days', $decimals);
                }

                if ($time / 604800 < 52) {
                    /*
                     * Weeks
                     */
                    return Time::difference($start, $stop, 'weeks', $decimals);
                }

                if ($time / 2592000 < 12) {
                    /*
                     * Months
                     */
                    return Time::difference($start, $stop, 'months', $decimals);
                }

                /*
                 * Years
                 */
                return Time::difference($start, $stop, 'years', $decimals);

            default:
                throw new OutOfBoundsException(tr('Unknown precision ":precision" specified', [':precision' => $precision]));
        }
    }



    /**
     * Returns "... days and hours ago" string.
     *
     * $original should be the original date and time in Unix format
     *
     * @param float $original
     * @return string
     */
    public static function ago(float $original): string
    {
        /*
         * Common time periods as an array of arrays
         */
        $periods = array(array(60 * 60 * 24 * 365 , tr('year')),
                         array(60 * 60 * 24 * 30  , tr('month')),
                         array(60 * 60 * 24 * 7   , tr('week')),
                         array(60 * 60 * 24       , tr('day')),
                         array(60 * 60            , tr('hour')),
                         array(60                 , tr('minute')));

        $today = time();
        $since = $today - $original; // Find the difference of time between now and the past

        // Loop around the periods, starting with the biggest
        for ($i = 0, $j = count($periods); $i < $j; $i++) {
            $seconds = $periods[$i][0];
            $name    = $periods[$i][1];

            // Find the biggest whole period
            if (($count = floor($since / $seconds)) != 0) {
                break;
            }
        }

        $output = ($count == 1) ? '1 ' . $name : "$count {$name}s";

        if ($i + 1 < $j) {
            // Retrieving the second relevant period
            $seconds2 = $periods[$i + 1][0];
            $name2    = $periods[$i + 1][1];

            // Only show it if it's greater than 0
            if (($count2 = floor(($since - ($seconds * $count)) / $seconds2)) != 0) {
                $output .= ($count2 == 1) ? ', 1 ' . $name2 : ", $count2 {$name2}s";
            }
        }

        return $output;
    }


    /**
     * Validates the given time.
     * Can check either hh:mm:ss, or hh:mm
     * Can check both 12H or 24H format
     *
     * @param float|string $time
     * @param bool $format
     * @param string $separator
     * @return array
     */
    public static function validate(float|string $time, bool $format = false, string $separator = ':'): array
    {
        $time = trim($time);

        // Check for 12 hours format
        if (!$format or ($format = '12')) {
            if (preg_match('/^(0?\d|(?:1(?:0|1)))\s?' . $separator.'\s?((?:0?|[1-5])\d)(?:\s?' . $separator.'\s?((?:0?|[1-5])\d)|)\s*(am|pm)$/i', $time, $matches)) {
                return [
                    'time'    => $matches[1] . $separator.$matches[2].($matches[3] ? $separator.$matches[3] : '').' '.strtoupper($matches[4]),
                         'format'  => '12',
                         'hours'   => $matches[1],
                         'minutes' => $matches[2],
                         'seconds' => $matches[3],
                         'period'  => strtoupper($matches[4])
                ];
            }
        }

        // Check for 24 hours format
        if (!$format or ($format = '24')) {
            if (preg_match('/^((?:0?|1)\d|(?:2[0-3]))\s?' . $separator.'\s?((?:0?|[1-5])\d)(?:\s?' . $separator.'\s?((?:0?|[1-5])\d)|)$/', $time, $matches)) {
                return [
                    'time'    => $matches[1] . $separator.$matches[2].(isset_get($matches[3]) ? $separator.$matches[3] : ''),
                    'format'  => '24',
                    'hours'   => $matches[1],
                    'minutes' => $matches[2],
                    'seconds' => isset_get($matches[3])
                ];
            }
        }

        if ($format) {
            // The time format is either not valid at all, or not valid for the specifed 12H or 24H format
            throw new OutOfBoundsException('Specified time ":time" is not a valid ":format" format time', [':time' => $time, ':format' => $format]);
        }

        // The time format is not valid
        throw new OutOfBoundsException(tr('Specified time ":time" is not a valid time format', [':time' => $time]));
    }



    /**
     * Format the specified time to 12H or 24H
     *
     * @param float $time
     * @param int $format
     * @param string $separator
     * @return string
     */
    public static function format(float $time, int $format = 24, string $separator = ':'): string
    {
        $time = self::validate($time);

        switch ($format) {
            case 12:
                // Go for 12H format
                if ($time['format'] == '12') {
                    return $time['time'];
                }

                if ($time['hours'] > 11) {
                    $time['hours']  -= 12;
                    $time['period']  = 'PM';

                } else {
                    $time['period']  = 'AM';
                }

                if ($time['seconds'] === null) {
                    return $time['hours'] . $separator . $time['minutes'] . ' ' . $time['period'];
                }

                return $time['hours'] . $separator . $time['minutes'] . $separator.$time['seconds'] . ' ' . $time['period'];

            case 24:
                // Go for 24H format
                if ($time['format'] == '24') {
                    return $time['time'];
                }

                if ($time['period'] == 'PM') {
                    $time['hours'] += 12;
                }

                if ($time['seconds'] === null) {
                    return $time['hours'] . $separator.$time['minutes'];
                }

                return $time['hours'] . $separator . $time['minutes'] . $separator . $time['seconds'];

            default:
                throw new OutOfBoundsException(tr('Unknown format ":format" specified', [':format' => $format]));
        }
    }
}