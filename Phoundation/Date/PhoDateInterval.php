<?php

/**
 * Class PhoDateInterval
 *
 *
 *
 * @see       https://www.php.net/manual/en/dateinterval.construct.php
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date;

use Exception;
use Phoundation\Date\Exception\DateIntervalException;
use Phoundation\Date\Interfaces\PhoDateIntervalInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Stringable;


class PhoDateInterval extends \DateInterval implements Stringable, PhoDateIntervalInterface
{
    /**
     * Number of years
     *
     * @var int
     */
    public $y;

    /**
     * Number of months
     *
     * @var int
     */
    public $m;

    /**
     * Number of days
     *
     * @var int
     */
    public $d;

    /**
     * Number of hours
     *
     * @var int
     */
    public $h;

    /**
     * Number of minutes
     *
     * @var int
     */
    public $i;

    /**
     * Number of seconds
     *
     * @var int
     */
    public $s;

    /**
     * Number of microseconds
     *
     * @since 7.1.0
     * @var float
     */
    public $f;

    /**
     * Number of microseconds
     *
     * @since 7.1.0
     * @var float
     */
    public $u;

    /**
     * Is 1 if the interval is inverted and 0 otherwise
     *
     * @var int
     */
    public $invert;

    /**
     * Total number of days the interval spans. If this is unknown, days will be FALSE.
     *
     * @var int|false
     */
    public $days;

    /**
     * If the DateInterval object was created by DateInterval::createFromDateString(), then this property's value will
     * be true
     *
     * @var bool $from_string
     */
    public bool $from_string;

    /**
     * The string used as argument to DateInterval::createFromDateString().
     *
     * @var string $date_string
     */
    public string $date_string;


    /**
     * DateInterval constructor
     *
     * @note If the $date_interval was specified as an integer, it will be interpreted as seconds
     *
     * @param \DateInterval|PhoDateInterval|array|string|float|int $date_interval
     * @param bool                                                 $round_up
     *
     * @throws DateIntervalException
     */
    public function __construct(\DateInterval|PhoDateInterval|array|string|float|int $date_interval, bool $round_up = true)
    {
        if (is_string($date_interval)) {
            try {
                parent::__construct($date_interval);
                return;

            } catch (Exception $e) {
                throw new DateIntervalException($e);
            }

        } elseif (is_int($date_interval)) {
            // Diff will always give a tiny number of micro/milliseconds difference. Since we are on seconds resolution
            // here, we can round that off
            $round_up      = not_null($round_up, true);
            $date_interval = PhoDateTime::new($date_interval . ' seconds')->diff(PhoDateTime::new());

            if ($date_interval->f > 500) {
                // Dude, WTF PHP? go to -1s + 1000ms?
                $date_interval->s++;
            }

            $date_interval->f = 0;
            $date_interval->u = 0;

        } elseif (is_float($date_interval)) {
            // DateTime does not accept fractional seconds, create it with seconds and manually set milli/microseconds
            $seconds       = (int) floor($date_interval);
            $microseconds  = (int) round(($date_interval - $seconds) * 1_000_000);
            $milliseconds  = (int) round($microseconds / 1_000);
            $microseconds  = $microseconds - ($milliseconds * 1000);
            $date_interval = PhoDateTime::new($seconds . ' seconds')->diff(PhoDateTime::new());

            $date_interval->f = $milliseconds;
            $date_interval->u = $microseconds;

            $round_up         = not_null($round_up, false);
        }

        // Copy all properties
        foreach ($date_interval as $key => $value) {
            $this->$key = $value;
        }

        if ($round_up) {
            $this->roundUp();
        }

        // WTF is days here? We have y m >>d<< h i s  etc.. What is days doing, PHP? DON'T USE DAYS!
        $this->days = null;
    }


    /**
     * Returns a new DateTime object
     *
     * @param PhoDateInterval|array|string|float|int $date_interval
     * @param bool                                   $round_up
     *
     * @return static
     * @throws Exception
     */
    public static function new(PhoDateInterval|array|string|float|int $date_interval, bool $round_up = true): static
    {
        return new static($date_interval, $round_up);
    }


    /**
     * Rounds up the microseconds to whole seconds
     *
     * @return static
     */
    public function roundUp(): static
    {
        // PHP can make shitty rounding issues in the microseconds range, try and fix those
        if ($this->f < 0.5) {
            // Too small, ignore it
            $this->f = 0;

        } else {
            $this->f = 0;
            $this->u = 0;
            $this->s++;

            // Limit seconds to 60
            if ($this->s >= 60) {
                $this->s = 0;
                $this->i++;

                // Limit minutes to 60
                if ($this->i >= 60) {
                    $this->i = 0;
                    $this->h++;

                    // Limit hours to 24
                    if ($this->h >= 24) {
                        $this->h = 0;
                        $this->d++;

                        // Limit days to 30
                        if ($this->d >= 30) {
                            $this->d = 0;
                            $this->m++;

                            // Limit months to 12
                            if ($this->m >= 12) {
                                $this->m = 0;
                                $this->y++;
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getInterval();
    }


    /**
     * Returns the interval to generate this DateInterval object
     *
     * @return string
     */
    public function getInterval(): string
    {
        $date = null;
        $time = null;

        // Create the date interval string
        if ($this->y) {
            $date .= $this->y . 'Y';
        }

        if ($this->m) {
            $date .= $this->m . 'M';
        }

        if ($this->d) {
            $date .= $this->d . 'D';
        }

        // Create the time interval string
        if ($this->h) {
            $time .= $this->h . 'H';
        }

        if ($this->i) {
            $time .= $this->i . 'M';
        }

        if ($this->s) {
            $time .= $this->s . 'S';
        }

        if ($time) {
            $time = 'T' . $time;
        }

        $return = 'P' . $date . $time;

        if ($return === 'P') {
            // Interval is zero
            return 'PT0S';
        }

        return $return;
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return array
     */
    public function __toArray(): array
    {
        return (array) $this;
    }


    /**
     * Returns the number of years for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalYears(bool $round_down = false): int
    {
        $total = $this->y;

        if (!$round_down and ($this->getTotalMonths() > 6)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns the number of months for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalMonths(bool $round_down = false): int
    {
        $total = ($this->y * 12) + $this->m;

        if (!$round_down and ($this->getTotalDays() > 15)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns the number of days for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalDays(bool $round_down = false): int
    {
        $total = $this->d;

        if (!$round_down and ($this->h > 12)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns the number of weeks for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalWeeks(bool $round_down = false): int
    {
        if ($round_down) {
            return (int) floor($this->d / 7);
        }

        return (int) round($this->d / 7);
    }


    /**
     * Returns the total number of hours and minute fraction for this date interval
     *
     * @return float
     */
    public function getTotalHoursWithFraction(): float
    {
        $minutes  = $this->getTotalMinutes();
        $hours    = (int) floor($minutes / 60);
        $fraction = $minutes - ($hours * 60);
        $fraction = $fraction / 60;

        return $hours + $fraction;
    }


    /**
     * Returns the total number of minutes for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalMinutes(bool $round_down = false): int
    {
        $total = ($this->getTotalHours(true) * 60) + $this->i;

        if (!$round_down and ($this->s > 30)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns the total number of hours for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalHours(bool $round_down = false): int
    {
        $total = ($this->d * 24) + $this->h;

        if (!$round_down and ($this->i > 30)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns the number of microseconds for this date interval
     *
     * @return int
     */
    public function getTotalMicroSeconds(): int
    {
        return ($this->getTotalMilliSeconds(true) * 1000) + (int) $this->u;
    }


    /**
     * Returns the number of milliseconds for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalMilliSeconds(bool $round_down = false): int
    {
        $total = ($this->getTotalSeconds(true) * 1000) + (int) $this->f;

        if (!$round_down and ($this->u > 500)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns the number of seconds for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalSeconds(bool $round_down = false): int
    {
        $total = ($this->getTotalMinutes(true) * 60) + $this->s;

        if (!$round_down and ($this->f > 500)) {
            $total++;
        }

        return $total;
    }


    /**
     * Returns this DateInterval data in human-readable format
     *
     * @param string|null $round
     * @param string|null $limit
     *
     * @return string
     */
    public function getHumanReadableShort(?string $round = null, ?string $limit = null): string
    {
        return static::getHumanReadable([
            'y' => 'y',
            'm' => 'm',
            'd' => 'd',
            'h' => 'h',
            'i' => 'm',
            's' => 's',
            'f' => 'ms',
            'u' => 'us',
        ], '', $round, $limit);
    }


    /**
     * Returns this DateInterval data in human-readable format
     *
     * @param array       $units
     * @param string      $separator
     * @param string|null $round
     * @param string|null $limit
     *
     * @return string
     */
    protected function getHumanReadable(array $units, string $separator, ?string $round = null, ?string $limit = null): string
    {
        $round    = static::getRoundFactor($round);
        $limit    = static::getLimitFactor($limit);
        $return   = [];
        $interval = clone $this; // Do not work on THIS interval or next operations may work with borked data

        if ($interval->y) {
            if ($limit < 7) {
                $interval->m += ($interval->y * 12);

            } else {
                $return[] = $interval->y . $units['y'];
            }
        }

        if ($interval->m and ($round < 7)) {
            if ($limit < 6) {
                $interval->d += ($interval->m * 30);

            } else {
                $return[] = $interval->m . $units['m'];
            }
        }

        if ($interval->d and ($round < 6)) {
            if ($limit < 5) {
                $interval->h += ($interval->d * 24);

            } else {
                $return[] = $interval->d . $units['d'];
            }
        }

        if ($interval->h and ($round < 5)) {
            if ($limit < 4) {
                $interval->i += ($interval->h * 60);

            } else {
                $return[] = $interval->h . $units['h'];
            }
        }

        if ($interval->i and ($round < 4)) {
            if ($limit < 3) {
                $interval->s += ($interval->i * 60);

            } else {
                $return[] = $interval->i . $units['i'];
            }
        }

        if ($interval->s and ($round < 3)) {
            if ($limit < 2) {
                $interval->f += ($interval->s * 1000);

            } else {
                $return[] = $interval->s . $units['s'];
            }
        }

        if ($interval->f and ($round < 2)) {
            if ($limit < 2) {
                $interval->u += ($interval->f * 1000);

            } else {
                $return[] = $interval->f . $units['f'];
            }
        }

        if ($interval->u and ($round < 1)) {
            $return[] = $interval->u . $units['u'];
        }

        return implode($separator, $return);
    }


    /**
     * Returns the rounding factor for the specified rounding string
     *
     * @param string|null $round
     *
     * @return int
     */
    protected static function getRoundFactor(?string $round): int
    {
        return match (strtolower((string) $round)) {
            'y', 'year', 'years'                   => 7,
            'm', 'month', 'months'                 => 6,
            'd', 'day', 'days'                     => 5,
            'h', 'hour', 'hours'                   => 4,
            'i', 'minute', 'minutes'               => 3,
            's', 'second', 'seconds'               => 2,
            'f', 'millisecond', 'milliseconds'     => 1,
            'u', 'microsecond', 'microseconds', '' => 0,
            default                                => throw new OutOfBoundsException(tr('Invalid rounding factor ":round" specified', [
                ':round' => $round,
            ])),
        };
    }


    /**
     * Returns the rounding factor for the specified rounding string
     *
     * @param string|null $limit
     *
     * @return int
     */
    public static function getLimitFactor(?string $limit): int
    {
        return match (strtolower((string) $limit)) {
            ''                 => 8,
            'y', 'year'        => 7,
            'm', 'month'       => 6,
            'd', 'day'         => 5,
            'h', 'hour'        => 4,
            'i', 'minute'      => 3,
            's', 'second'      => 2,
            'f', 'millisecond' => 1,
            default            => throw new OutOfBoundsException(tr('Invalid limiting factor ":limit" specified', [
                ':limit' => $limit,
            ])),
        };
    }


    /**
     * Returns this DateInterval data in human-readable format
     *
     * @param string|null $round
     * @param string|null $limit
     *
     * @return string
     */
    public function getHumanReadableLong(?string $round = null, ?string $limit = null): string
    {
        return static::getHumanReadable([
            'y' => ' ' . Strings::plural($this->y, tr('year'), tr('years')),
            'm' => ' ' . Strings::plural($this->m, tr('month'), tr('months')),
            'd' => ' ' . Strings::plural($this->d, tr('day'), tr('days')),
            'h' => ' ' . Strings::plural($this->h, tr('hour'), tr('hours')),
            'i' => ' ' . Strings::plural($this->i, tr('minute'), tr('minutes')),
            's' => ' ' . Strings::plural($this->s, tr('second'), tr('seconds')),
            'f' => ' ' . Strings::plural($this->f, tr('millisecond'), tr('milliseconds')),
            'u' => ' ' . Strings::plural($this->f, tr('microsecond'), tr('microseconds')),
        ], ', ', $round, $limit);
    }


    /**
     * Checks that the contents of this object are valid
     *
     * @return static
     *
     * @throws OutOfBoundsException
     */
    public function checkValid(): static
    {
        // Microseconds
        if ($this->u) {
            if ($this->u < 0) {
                throw new OutOfBoundsException(ts('The amount of microseconds ":value" in this object is invalid, it must be 0 or higher', [
                    ':value' => $this->u,
                ]));
            }

        } else {
            // Microseconds
            if ($this->f < 0) {
                throw new OutOfBoundsException(ts('The amount of milliseconds ":value" in this object is invalid, it must be 0 or higher', [
                    ':value' => $this->u,
                ]));
            }
        }

        // Seconds
        if ($this->s < 0) {
            throw new OutOfBoundsException(ts('The amount of seconds ":value" is invalid, it must be 0 or higher', [
                ':value' => $this->s,
            ]));
        }

        // Minutes
        if ($this->i < 0) {
            throw new OutOfBoundsException(ts('The amount of minutes ":value" is invalid, it must be 0 or higher', [
                ':value' => $this->i,
            ]));
        }

        // Hours
        if ($this->h < 0) {
            throw new OutOfBoundsException(ts('The amount of hours ":value" is invalid, it must be 0 or higher', [
                ':value' => $this->h,
            ]));
        }

        // Days — overflow into months (approximate, 30-day months)
        if ($this->d < 0) {
            throw new OutOfBoundsException(ts('The amount of days ":value" is invalid, it must be 0 or higher', [
                ':value' => $this->d,
            ]));
        }

        // Months
        if ($this->m < 0) {
            throw new OutOfBoundsException(ts('The amount of months ":value" is invalid, it must be 0 or higher', [
                ':value' => $this->m,
            ]));
        }

        // Years
        if ($this->y < 0) {
            throw new OutOfBoundsException(ts('The amount of years ":value" is invalid, it must be 0 or higher', [
                ':value' => $this->y,
            ]));
        }

        return $this;
    }


    /**
     * Normalizes the data in this object by ensuring none of the values "overflows"
     *
     * For example, 60 seconds is 1 minute, which should be the maximum value. If the value is 70, minutes should be increased by 1 and seconds should become 10
     *
     * @note Will also check that all values are positive, and throw a Pho OutOfBounds exception if not
     *
     * @param int|null $days_in_month [30] The amount of days that are assumed to be in a month. Defaults to 30
     *
     * @return static
     *
     * @throws OutOfBoundsException
     */
    public function normalize(?int $days_in_month = 30): static
    {
        $this->checkValid();

        // Microseconds
        if ($this->u) {
            if ($this->u > 999999) {
                $this->s += (int) ($this->u / 1_000_000);
                $this->u  = fmod($this->u, 1_000_000);
            }

        } else {
            // Microseconds
            if ($this->f > 999999) {
                $this->s += (int) ($this->f / 1_000);
                $this->f  = fmod($this->f, 1_000);
            }
        }

        // Seconds
        if ($this->s > 59) {
            $this->i += (int) ($this->s / 60);
            $this->s  = fmod($this->s, 60);
        }

        // Minutes
        if ($this->i > 59) {
            $this->h += (int) ($this->i / 60);
            $this->i  = fmod($this->i, 60);
        }

        // Hours
        if ($this->h > 23) {
            $this->d += (int) ($this->h / 24);
            $this->h  = fmod($this->h, 24);
        }

        // Days — overflow into months (approximate, 30-day months)
        if ($this->d > 30) {
            $this->m += (int) ($this->d / $days_in_month);
            $this->d  = fmod($this->d, $days_in_month);
        }

        if ($this->m > 11) {
            $this->y += (int) ($this->m / 12);
            $this->m  = fmod($this->m, 12);
        }

        return $this;
    }


    /**
     * Returns true if this diff spans more than a year
     *
     * @return bool
     */
    public function isMoreThanAYear(): bool
    {
        $this->normalize();

        return match ($this->y) {
            0       => false,
            1       => ($this->m or $this->d or $this->h or $this->i or $this->s or $this->f or $this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than a month
     *
     * @return bool
     */
    public function isMoreThanAMonth(): bool
    {
        $this->normalize();

        if ($this->isMoreThanAYear()) {
            return true;
        }

        return match ($this->m) {
            0       => false,
            1       => ($this->d or $this->h or $this->i or $this->s or $this->f or $this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than a week
     *
     * @return bool
     */
    public function isMoreThanAWeek(): bool
    {
        $this->normalize();

        if ($this->isMoreThanAMonth()) {
            return true;
        }

        if ($this->d < 6) {
            return true;
        }

        if ($this->d > 7) {
            return true;
        }

        return ($this->h or $this->i or $this->s or $this->f or $this->u);
    }


    /**
     * Returns true if this diff spans more than a day
     *
     * @return bool
     */
    public function isMoreThanADay(): bool
    {
        $this->normalize();

        if ($this->isMoreThanAMonth()) {
            return true;
        }

        return match ($this->d) {
            0       => false,
            1       => ($this->h or $this->i or $this->s or $this->f or $this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than an hour
     *
     * @return bool
     */
    public function isMoreThanAnHour(): bool
    {
        $this->normalize();

        if ($this->isMoreThanADay()) {
            return true;
        }

        return match ($this->h) {
            0       => false,
            1       => ($this->i or $this->s or $this->f or $this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than a minute
     *
     * @return bool
     */
    public function isMoreThanAMinute(): bool
    {
        $this->normalize();

        if ($this->isMoreThanAnHour()) {
            return true;
        }

        return match ($this->i) {
            0       => false,
            1       => ($this->s or $this->f or $this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than a second
     *
     * @return bool
     */
    public function isMoreThanASecond(): bool
    {
        $this->normalize();

        if ($this->isMoreThanAMinute()) {
            return true;
        }

        return match ($this->s) {
            0       => false,
            1       => ($this->f or $this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than a millisecond
     *
     * @return bool
     */
    public function isMoreThanAMilliSecond(): bool
    {
        $this->normalize();

        if ($this->isMoreThanASecond()) {
            return true;
        }

        return match ($this->f) {
            0       => false,
            1       => ($this->u),
            default => true,
        };
    }


    /**
     * Returns true if this diff spans more than a microsecond
     *
     * @return bool
     */
    public function isMoreThanAMicroSecond(): bool
    {
        $this->normalize();

        if ($this->isMoreThanAMilliSecond()) {
            return true;
        }

        return match ($this->u) {
            0, 1    => false,
            default => true,
        };
    }
}
