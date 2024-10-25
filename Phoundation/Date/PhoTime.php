<?php

/**
 * Class PhoTime
 *
 * This class contains various time handling methods
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;


class PhoTime
{
    /**
     * Returns the difference in times with the pointed precision
     *
     * @param float  $start
     * @param float  $stop
     * @param string $precision
     * @param int    $decimals
     *
     * @return string
     */
    public static function difference(float $start, float $stop, string $precision = 'auto', int $decimals = 2): string
    {
        $time = $stop - $start;

        //// TEST VALUES
        // 2 hours 7 minutes 53.8236 seconds
        //$time = 7673.8237;
        // 2 days 4 hours 7 minutes 53.8236 seconds
        //$time = 7673.8237 + 7200 + (86400 * 2);
        // 2 weeks 5 days 4 hours 7 minutes 53.8236 seconds
        //$time = 7673.8237 + 7200 + (86400 * 5) + (86400 * 14);
        // 2 months 5 days 4 hours 7 minutes 53.8236 seconds
        //$time = 7673.8237 + 7200 + (86400 * 5) + (86400 * 60);
        // 6 years 2 months 5 days 4 hours 7 minutes 53.8236 seconds
        //$time = 7673.8237 + 7200 + (86400 * 5) + (86400 * 60) + ((86400 * 365) * 6);

        switch ($precision) {
            case 'second':
                // no break
            case 'seconds':
                return static::differenceSeconds($time, $decimals);

            case 'minute':
                // no break
            case 'minutes':
                return static::differenceMinutes($time, $decimals);

            case 'hour':
                // no break
            case 'hours':
                return static::differenceHours($time, $decimals);

            case 'day':
                // no break
            case 'days':
                return static::differenceDays($time, $decimals);

            case 'week':
                // no break
            case 'weeks':
                return static::differenceWeeks($time, $decimals);

            case 'month':
                // no break
            case 'months':
                return static::differenceMonths($time, $decimals);

            case 'year':
                // no break
            case 'years':
                return static::differenceYears($time, $decimals);

            case 'auto':
                return static::differenceAuto($time, $start, $stop, $decimals);

            default:
                throw new OutOfBoundsException(tr('Unknown precision ":precision" specified', [
                    ':precision' => $precision,
                ]));
        }
    }


    /**
     * Returns the difference in times with automatic precision
     *
     * @param float $time
     * @param float $start
     * @param float $stop
     * @param int   $decimals
     *
     * @return string
     */
    protected static function differenceAuto(float $time, float $start, float $stop, int $decimals = 2): string
    {
        if ($time < 60) {
            // Seconds
            return PhoTime::difference($start, $stop, 'seconds', $decimals);
        }

        if ($time / 60 < 60) {
            // Minutes
            return PhoTime::difference($start, $stop, 'minutes', $decimals);
        }

        if ($time / 3600 < 24) {
            // Hours
            return PhoTime::difference($start, $stop, 'hours', $decimals);
        }

        if ($time / 86400 < 7) {
            // Days
            return PhoTime::difference($start, $stop, 'days', $decimals);
        }

        if ($time / 604800 < 52) {
            // Weeks
            return PhoTime::difference($start, $stop, 'weeks', $decimals);
        }

        if ($time / 2592000 < 12) {
            // Months
            return PhoTime::difference($start, $stop, 'months', $decimals);
        }

        // Years
        return PhoTime::difference($start, $stop, 'years', $decimals);
    }


    /**
     * Returns the correct string for years
     *
     * @param int $years
     *
     * @return string
     */
    protected static function getYearsString(int $years): string
    {
        return Strings::plural($years, tr(':time year', [
            ':time' => $years,
        ]), tr(':time years', [
            ':time' => $years,
        ]));
    }


    /**
     * Returns the correct string for months
     *
     * @param int $months
     *
     * @return string
     */
    protected static function getMonthString(int $months): string
    {
        return Strings::plural($months, tr(':time month', [
            ':time' => $months,
        ]), tr(':time months', [
            ':time' => $months,
        ]));
    }


    /**
     * Returns the correct string for weeks
     *
     * @param int $weeks
     *
     * @return string
     */
    protected static function getWeeksString(int $weeks): string
    {
        return Strings::plural($weeks, tr(':time week', [
            ':time' => $weeks,
        ]), tr(':time weeks', [
            ':time' => $weeks,
        ]));
    }


    /**
     * Returns the correct string for days
     *
     * @param int $days
     *
     * @return string
     */
    protected static function getDaysString(int $days): string
    {
        return Strings::plural($days, tr(':time day', [
            ':time' => $days,
        ]), tr(':time days', [
            ':time' => $days,
        ]));
    }


    /**
     * Returns the correct string for hours
     *
     * @param int $hours
     *
     * @return string
     */
    protected static function getHoursString(int $hours): string
    {
        return Strings::plural($hours, tr(':time hour', [
            ':time' => $hours,
        ]), tr(':time hours', [
            ':time' => $hours,
        ]));
    }


    /**
     * Returns the correct string for minutes
     *
     * @param int $minutes
     *
     * @return string
     */
    protected static function getMinutesString(int $minutes): string
    {
        return Strings::plural($minutes, tr(':time minute', [
            ':time' => $minutes,
        ]), tr(':time minutes', [
            ':time' => $minutes,
        ]));
    }


    /**
     * Returns the correct string for seconds
     *
     * @param float $seconds
     *
     * @return string
     */
    protected static function getSecondsString(float $seconds): string
    {
        return Strings::plural($seconds, tr(':time second', [
            ':time' => $seconds,
        ]), tr(':time seconds', [
            ':time' => $seconds,
        ]));
    }


    /**
     * Returns the difference in times with the pointed precision for weeks
     *
     * @param float $time
     * @param int   $decimals
     *
     * @return string
     */
    protected static function differenceYears(float $time, int $decimals = 2): string
    {
        // Calculate the times
        // NOTE: Year is assumed 365 days!
        $years   = (int) floor($time / 31536000);
        $months  = (int) floor(($time - ($years * 31536000)) / 2592000);
        $days    = (int) floor(($time - ($years * 31536000) - ($months * 2592000)) / 86400);
        $hours   = (int) floor(($time - ($years * 31536000) - ($months * 2592000) - ($days * 86400)) / 3600);
        $minutes = (float) number_format(($time - ($years * 31536000) - ($months * 2592000) - ($days * 86400) - ($hours * 3600)) / 60, $decimals);
        $seconds = $minutes - (int) $minutes;
        $minutes = (int) ($minutes - $seconds);
        $seconds *= 60;

        // Build the time strings
        $years   = static::getYearsString($years);
        $months  = static::getMonthString($months);
        $days    = static::getDaysString($days);
        $hours   = static::getHoursString($hours);
        $minutes = static::getMinutesString($minutes);
        $seconds = static::getSecondsString($seconds);

        // Return the difference string
        return $years . ' ' . $months . ' ' . $days . ' ' . $hours . ' ' . $minutes . ' ' . $seconds;
    }



    /**
     * Returns the difference in times with the pointed precision for weeks
     *
     * @param float  $time
     * @param int    $decimals
     *
     * @return string
     */
    protected static function differenceMonths(float $time, int $decimals = 2): string
    {
        // Calculate the times
        // NOTE: Month is assumed 30 days!
        $months   = (int) floor($time / 2592000);
        $days     = (int) floor(($time - ($months * 2592000)) / 86400);
        $hours    = (int) floor(($time - ($months * 2592000) - ($days * 86400)) / 3600);
        $minutes  = (float) number_format(($time - ($months * 2592000) - ($days * 86400) - ($hours * 3600)) / 60, $decimals);
        $seconds  = $minutes - (int) $minutes;
        $minutes  = (int) ($minutes - $seconds);
        $seconds *= 60;

        // Build the time strings
        $months  = static::getMonthString($months);
        $days    = static::getDaysString($days);
        $hours   = static::getHoursString($hours);
        $minutes = static::getMinutesString($minutes);
        $seconds = static::getSecondsString($seconds);

        // Return the difference string
        return $months . ' ' . $days . ' ' . $hours . ' ' . $minutes . ' ' . $seconds;
    }



    /**
     * Returns the difference in times with the pointed precision for weeks
     *
     * @param float  $time
     * @param int    $decimals
     *
     * @return string
     */
    protected static function differenceWeeks(float $time, int $decimals = 2): string
    {
        // Calculate the times
        $weeks    = (int) floor($time / 604800);
        $days     = (int) floor(($time - ($weeks * 604800)) / 86400);
        $hours    = (int) floor(($time - ($weeks * 604800) - ($days * 86400)) / 3600);
        $minutes  = (float) number_format(($time - ($weeks * 604800) - ($days * 86400) - ($hours * 3600)) / 60, $decimals);
        $seconds  = $minutes - (int) $minutes;
        $minutes  = (int) ($minutes - $seconds);
        $seconds *= 60;

        // Build the time strings
        $weeks  = static::getWeeksString($weeks);
        $days    = static::getDaysString($days);
        $hours   = static::getHoursString($hours);
        $minutes = static::getMinutesString($minutes);
        $seconds = static::getSecondsString($seconds);

        // Return the difference string
        return $weeks . ' ' . $days . ' ' . $hours . ' ' . $minutes . ' ' . $seconds;
    }


    /**
     * Returns the difference in times with the pointed precision for days
     *
     * @param float  $time
     * @param int    $decimals
     *
     * @return string
     */
    protected static function differenceDays(float $time, int $decimals = 2): string
    {
        // Calculate the times
        $days     = (int) floor($time / 86400);
        $hours    = (int) floor(($time - ($days * 86400)) / 3600);
        $minutes  = (float) number_format(($time - ($days * 86400) - ($hours * 3600)) / 60, $decimals);
        $seconds  = $minutes - (int) $minutes;
        $minutes  = (int) ($minutes - $seconds);
        $seconds *= 60;

        // Build the time strings
        $days    = static::getDaysString($days);
        $hours   = static::getHoursString($hours);
        $minutes = static::getMinutesString($minutes);
        $seconds = static::getSecondsString($seconds);

        // Return the difference string
        return $days . ' ' . $hours . ' ' . $minutes . ' ' . $seconds;
    }


    /**
     * Returns the difference in times with the pointed precision for hours
     *
     * @param float  $time
     * @param int    $decimals
     *
     * @return string
     */
    protected static function differenceHours(float $time, int $decimals = 2): string
    {
        // Calculate the times
        $hours    = (int) floor($time / 3600);
        $minutes  = (float) number_format(($time - ($hours * 3600)) / 60, $decimals);
        $seconds  = $minutes - (int) $minutes;
        $minutes  = (int) ($minutes - $seconds);
        $seconds *= 60;

        // Build the time strings
        $hours   = static::getHoursString($hours);
        $minutes = static::getMinutesString($minutes);
        $seconds = static::getSecondsString($seconds);

        // Return the difference string
        return $hours . ' ' . $minutes . ' ' . $seconds;
    }


    /**
     * Returns the difference in times with the pointed precision for minutes
     *
     * @param float  $time
     * @param int    $decimals
     *
     * @return string
     */
    protected static function differenceMinutes(float $time, int $decimals = 2): string
    {
        // Calculate the times
        $minutes  = (float) number_format($time / 60, $decimals);
        $seconds  = $minutes - (int) $minutes;
        $minutes  = (int) ($minutes - $seconds);
        $seconds *= 60;

        // Build the time strings
        $minutes = static::getMinutesString($minutes);
        $seconds = static::getSecondsString($seconds);

        // Return the difference string
        return $minutes . ' ' . $seconds;
    }


    /**
     * Returns the difference in times with the pointed precision for minutes
     *
     * @param float  $time
     * @param int    $decimals
     *
     * @return string
     */
    protected static function differenceSeconds(float $time, int $decimals = 2): string
    {
        return static::getSecondsString((float) number_format($time, $decimals));
    }


    /**
     * Returns "... days and hours ago" string.
     *
     * $original should be the original date and time in Unix format
     *
     * @param float $original
     *
     * @return string
     */
    public static function ago(float $original): string
    {
        // Common time periods as an array of arrays
        $periods = static::getPeriods();
        $today   = time();
        $since   = $today - $original; // Find the difference of time between now and the past

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
     * Returns all available periods
     *
     * @return array
     */
    public function getPeriods(): array
    {
        return [
            [
                31536000,
                tr('year'),
            ],
            [
                2592000,
                tr('month'),
            ],
            [
                604800,
                tr('week'),
            ],
            [
                86400,
                tr('day'),
            ],
            [
                3600,
                tr('hour'),
            ],
            [
                60,
                tr('minute'),
            ],
        ];
    }


    /**
     * Format the specified time to 12H or 24H
     *
     * @param float  $time
     * @param int    $format
     * @param string $separator
     *
     * @return string
     */
    public static function format(float $time, int $format = 24, string $separator = ':'): string
    {
        $time = static::validate($time);

        switch ($format) {
            case 12:
                return static::format12Hours($time, $separator);

            case 24:
                return static::format24Hours($time, $separator);

            default:
                throw new OutOfBoundsException(tr('Unknown format ":format" specified', [':format' => $format]));
        }
    }


    /**
     * Format the specified time to 12H
     *
     * @param array  $time
     * @param string $separator
     *
     * @return string
     */
    protected static function format12Hours(array $time, string $separator = ':'): string
    {
        // Go for 12H format
        if ($time['format'] == '12') {
            return $time['time'];
        }

        if ($time['hours'] > 11) {
            $time['hours']  -= 12;
            $time['period'] = 'PM';

        } else {
            $time['period'] = 'AM';
        }

        if ($time['seconds'] === null) {
            return $time['hours'] . $separator . $time['minutes'] . ' ' . $time['period'];
        }

        return $time['hours'] . $separator . $time['minutes'] . $separator . $time['seconds'] . ' ' . $time['period'];
    }


    /**
     * Format the specified time to 12H
     *
     * @param array  $time
     * @param string $separator
     *
     * @return string
     */
    protected static function format24Hours(array $time, string $separator = ':'): string
    {
        // Go for 24H format
        if ($time['format'] == '24') {
            return $time['time'];
        }

        if ($time['period'] == 'PM') {
            $time['hours'] += 12;
        }

        if ($time['seconds'] === null) {
            return $time['hours'] . $separator . $time['minutes'];
        }

        return $time['hours'] . $separator . $time['minutes'] . $separator . $time['seconds'];
    }


    /**
     * Validates the given time.
     * Can check either hh:mm:ss, or hh:mm
     * Can check both 12H or 24H format
     *
     * @param float|string $time
     * @param bool         $format
     * @param string       $separator
     *
     * @return array
     */
    public static function validate(float|string $time, bool $format = false, string $separator = ':'): array
    {
        $time = trim($time);

        // Check for 12 hours format
        if (!$format or ($format = '12')) {
            if (preg_match('/^(0?\d|(?:1(?:0|1)))\s?' . $separator . '\s?((?:0?|[1-5])\d)(?:\s?' . $separator . '\s?((?:0?|[1-5])\d)|)\s*(am|pm)$/i', $time, $matches)) {
                return [
                    'time'    => $matches[1] . $separator . $matches[2] . ($matches[3] ? $separator . $matches[3] : '') . ' ' . strtoupper($matches[4]),
                    'format'  => '12',
                    'hours'   => $matches[1],
                    'minutes' => $matches[2],
                    'seconds' => $matches[3],
                    'period'  => strtoupper($matches[4]),
                ];
            }
        }

        // Check for 24 hours format
        if (!$format or ($format = '24')) {
            if (preg_match('/^((?:0?|1)\d|(?:2[0-3]))\s?' . $separator . '\s?((?:0?|[1-5])\d)(?:\s?' . $separator . '\s?((?:0?|[1-5])\d)|)$/', $time, $matches)) {
                return [
                    'time'    => $matches[1] . $separator . $matches[2] . (isset_get($matches[3]) ? $separator . $matches[3] : ''),
                    'format'  => '24',
                    'hours'   => $matches[1],
                    'minutes' => $matches[2],
                    'seconds' => isset_get($matches[3]),
                ];
            }
        }

        if ($format) {
            // The time format is either not valid at all, or not valid for the specifed 12H or 24H format
            throw new OutOfBoundsException('Specified time ":time" is not a valid ":format" format time', [
                ':time'   => $time,
                ':format' => $format,
            ]);
        }

        // The time format is not valid
        throw new OutOfBoundsException(tr('Specified time ":time" is not a valid time format', [':time' => $time]));
    }
}
