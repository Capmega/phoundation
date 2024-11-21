<?php

/**
 * Class PhoDateTime
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date;

use DateInterval;
use DateMalformedStringException;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Phoundation\Core\Sessions\SessionConfig;
use Phoundation\Date\Enums\DateTimeSegment;
use Phoundation\Date\Exception\DateIntervalException;
use Phoundation\Date\Exception\DateTimeException;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\Interfaces\PhoDateTimeZoneInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


class PhoDateTime extends DateTime implements Stringable, Interfaces\PhoDateTimeInterface
{
    /**
     * Returns a new DateTime object
     *
     * @param PhoDateTimeInterface|string|int|null $datetime
     * @param DateTimeZone|string|null   $timezone
     */
    public function __construct(PhoDateTimeInterface|string|int|null $datetime = 'now', DateTimeZone|string|null $timezone = null)
    {
        // Ensure we have NULL or timezone object for parent constructor
        $timezone = get_null($timezone);
        $datetime = $datetime ?? 'now';

        if (is_string($timezone)) {
            $timezone = new PhoDateTimeZone(trim($timezone));
        }

        if (is_int($datetime)) {
            $datetime = PhoDateTime::new('now', $timezone)->setTimestamp($datetime)->format('Y-m-d H:i:s.u');
        }

        // Return Phoundation DateTime object for whatever given $datetime
        try {
            if (is_object($datetime)) {
                // Return a new DateTime object with the specified date in the specified timezone
                parent::__construct($datetime->format('Y-m-d H:i:s.u'), $timezone ?? $datetime->getTimezone());

            } else {
                $normalized = PhoDateFormats::normalizeDate($datetime, '-');
                parent::__construct($normalized, $timezone);
            }

        } catch (Throwable $e) {
            throw new DateTimeException(tr('Failed to create DateTime object for given datetime ":datetime" / timezone ":timezone" because ":e"', [
                ':datetime' => $datetime,
                ':timezone' => $timezone,
                ':e'        => $e->getMessage(),
            ]), $e);
        }
    }


    /**
     * Returns a new DateTime object
     *
     * @param PhoDateTimeInterface|string|int|null $datetime
     * @param DateTimeZone|string|null   $timezone
     *
     * @return static
     */
    public static function new(PhoDateTimeInterface|string|int|null $datetime = 'now', DateTimeZone|string|null $timezone = null): static
    {
        return new static($datetime, $timezone);
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }


    /**
     * Returns a new DateTime object
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newToday(DateTimeZone|string|null $timezone = null): static
    {
        return new static('today', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function increaseDay(): static
    {
        $this->modify('+1 day');
        return $this;
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function decreaseDay(): static
    {
        $this->modify('-1 day');
        return $this;
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param int $days
     *
     * @return static
     */
    public function increaseDays(int $days): static
    {
        if ($days <= 0) {
            if ($days < 0) {
                throw new OutOfBoundsException(tr('Invalid days value ":days" specified, must be 1 or higher', [
                    ':days' => $days,
                ]));
            }

            return $this;
        }

        $this->modify('+' . $days . ' day');
        return $this;
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param int $days
     *
     * @return static
     */
    public function decreaseDays(int $days): static
    {
        if ($days <= 0) {
            if ($days < 0) {
                throw new OutOfBoundsException(tr('Invalid days value ":days" specified, must be 1 or higher', [
                    ':days' => $days,
                ]));
            }

            return $this;
        }

        $this->modify('-' . $days . ' day');
        return $this;
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newTomorrow(DateTimeZone|string|null $timezone = null): static
    {
        return new static('tomorrow', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newYesterday(DateTimeZone|string|null $timezone = null): static
    {
        return new static('yesterday', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this week
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newFirstDayOfWeek(DateTimeZone|string|null $timezone = null): static
    {
        return new static(SessionConfig::getString('datetime.week.start', 'monday') . ' this week', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this week
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newLastDayOfWeek(DateTimeZone|string|null $timezone = null): static
    {
        return new static(SessionConfig::getString('datetime.week.stop', 'sunday') . ' this week', PhoDateTimeZone::new($timezone));
    }


    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * @param string|null $format
     *
     * @return string
     */
    public function format(?string $format = null): string
    {
        return parent::format(static::parseFormat($format));
    }


    /**
     * Applies specific format strings
     *
     * @param string|null $format
     *
     * @return string
     */
    protected static function parseFormat(?string $format = null): string
    {
        switch (strtolower($format)) {
            case 'human_time':
                return SessionConfig::getString('locale.dates.formats.human.time', 'H:i:s');

            case 'human_date':
                return SessionConfig::getString('locale.dates.formats.human.date', PhoDateFormats::getDefaultPhp());

            case 'human_datetime':
                // no break

            case 'human_date_time':
                return SessionConfig::getString('locale.dates.formats.human.datetime', PhoDateTimeFormats::getDefaultPhp());

            case 'iso_date':
                return 'd-m-Y';

            case 'iso_date_time':
                return 'Y-m-d H:i:s';

            case 'mysql':
                $format = 'Y-m-d H:i:s';
                break;

            case 'file':
                $format = 'ymd-His';
                break;
        }

        return $format;
    }


    /**
     * Will return the specified timezone, or if that is null, the timezone from the specified datetime.
     *
     * If the specified datetime is a string (and as such, contains no timezone information) NULL will be returned
     * instead
     *
     * @param PhoDateTimeInterface|string|null $datetime
     * @param DateTimeZone|string|null         $timezone
     *
     * @return PhoDateTimeZoneInterface|null
     */
    protected static function selectTimezone(PhoDateTimeInterface|string|null $datetime = 'now', DateTimeZone|string|null $timezone = null): ?PhoDateTimeZoneInterface
    {
        if ($datetime instanceof PhoDateTimeInterface) {
            $timezone = new PhoDateTimeZone($timezone ?? $datetime->getTimezone());
        }

        return new PhoDateTimeZone($timezone);
   }


    /**
     * Returns this date time as a human-readable date string
     *
     * @return string
     */
    public function getHumanReadableDate(): string
    {
        return $this->format('human_date');
    }


    /**
     * Returns this date time as a human-readable date-time string
     *
     * @return string
     */
    public function getHumanReadableDateTime(): string
    {
        return $this->format('human_date_time');
    }


    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getBeginningOfDay(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-d 00:00:00'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getEndOfDay(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-d 23:59:59.999999'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the first day of this year
     *
     * @param DateTimeZone|string|null  $timezone
     *
     * @return static
     */
    public function getFirstDayOfYear(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-01-01'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the last day of this year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastDayOfYear(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-12-31'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the first day of this month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstDayOfMonth(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-01'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the last day of this month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastDayOfMonth(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-t'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the first
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstPeriodStart(DateTimeZone|string|null $timezone = null): static
    {
        return $this->getFirstDayOfMonth($timezone);
    }


    /**
     * Returns a new DateTime object for
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastPeriodStart(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-16'),
            PhoDateTimeZone::new($timezone)
        );
    }


    /**
     * Adds a number of days, months, years, hours, minutes and seconds to a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param DateInterval $interval
     *
     * @return DateTime
     */
    public function add(DateInterval $interval): DateTime
    {
        return new PhoDateTime(parent::add($interval));
    }


    /**
     * Subtracts a number of days, months, years, hours, minutes and seconds from a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param DateInterval $interval
     *
     * @return DateTime
     */
    public function sub(DateInterval $interval): DateTime
    {
        return new PhoDateTime(parent::sub($interval));
    }


    /**
     * Returns the difference between two DateTime objects
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param DateTimeInterface $targetObject
     * @param bool              $absolute
     * @param bool              $roundup
     *
     * @return PhoDateInterval
     * @throws DateIntervalException
     */
    public function diff(DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): PhoDateInterval
    {
        // DateInterval doesn't calculate milliseconds / microseconds, do that manually
        $diff    = new PhoDateInterval(parent::diff($targetObject, $absolute), $roundup);
        $diff->u = (int) $this->format('u') - (int) $targetObject->format('u');

        if ($diff->u < 0) {
            if ($diff->u < -10) {
                // Negative microseconds, subtract a second and convert negative microseconds
                $diff->s--;
                $diff->u = 1_000_000 + $diff->u;

            } else {
                // This is likely a small offset from PHP DateInterval object, ignore it
                $diff->u = 0;
            }
        }

        $diff->f = round($diff->u / 1000);
        $diff->u = $diff->u - ($diff->f * 1000);

        return $diff;
    }


    /**
     * Returns a new DateTime object for the first day of the previous monthly period
     *
     * @return static
     */
    public function getPreviousPeriodStart(): static
    {
        $datetime = static::new($this);
        $date_day = $datetime->format('d');

        if ($date_day >= 16) {
            // 1 - 15 this month
            return PhoDateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) previous month
        $start = $datetime->sub(PhoDateInterval::createFromDateString('1 month'));

        return PhoDateTime::new($start->format('Y-m-16 00:00:00'), $datetime->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the next monthly period
     *
     * @return static
     */
    public function getNextPeriodStart(): static
    {
        $datetime = static::new($this);
        $date_day = $datetime->format('d');

        if ($date_day >= 16) {
            // 1 - 15 next month
            $start = $datetime->add(PhoDateInterval::createFromDateString('1 month'));

            return PhoDateTime::new($start->format('Y-m-1 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return PhoDateTime::new($datetime->format('Y-m-16 00:00:00'), $datetime->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current monthly period
     *
     * @return static
     */
    public function getCurrentPeriodStart(): static
    {
        $datetime = static::new($this);
        $date_day = $datetime->format('d');

        if ($date_day >= 16) {
            // 15-30 this month
            return PhoDateTime::new($datetime->format('Y-m-16 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return PhoDateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
    }


    /**
     * Returns the stop date for the period in which this date is
     *
     * @return static
     */
    public function getCurrentPeriodStop(): static
    {
        $datetime = static::new($this);
        $date_day = $datetime->format('d');

        if ($date_day >= 16) {
            // 15-30 this month
            return PhoDateTime::new($datetime->format('Y-m-t 23:59:59.999999'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return PhoDateTime::new($datetime->format('Y-m-15 23:59:59.999999'), $datetime->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getMonthStart(): static
    {
        return PhoDateTime::new($this->format('Y-m-1 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getMonthStop(): static
    {
        return PhoDateTime::new($this->format('Y-m-t 23:59:59.999999'), $this->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getDayStart(): static
    {
        return PhoDateTime::new($this->format('Y-m-d 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getDayStop(): static
    {
        return PhoDateTime::new($this->format('Y-m-d 23:59:59.999999'), $this->getTimezone());
    }


    /**
     * Returns true if this date is the first day of a period (the 1st or 16th of a month)
     *
     * @return bool
     */
    public function isPeriodStart(): bool
    {
        return in_array($this->format('d'), ['1', '16']);
    }


    /**
     * Returns true if the current date is today
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isToday(DateTimeZone|string|null $timezone = null): bool
    {
        $today = static::new('today', PhoDateTimeZone::new($timezone ?? $this->getTimezone()))
                       ->format('y-m-d');

        return $this->format('y-m-d') == $today;
    }


    /**
     * Returns true if the current date is tomorrow
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isTomorrow(DateTimeZone|string|null $timezone = null): bool
    {
        $tomorrow = static::new('tomorrow', PhoDateTimeZone::new($timezone ?? $this->getTimezone()))
                          ->format('y-m-d');

        return $this->format('y-m-d') == $tomorrow;
    }


    /**
     * Returns true if the current date is yesterday
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isYesterday(DateTimeZone|string|null $timezone = null): bool
    {
        $yesterday = static::new('yesterday', PhoDateTimeZone::new($timezone ?? $this->getTimezone()))
                           ->format('y-m-d');

        return $this->format('y-m-d') == $yesterday;
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @param DateTimeZone|PhoDateTimeZone|string $timezone
     *
     * @return static
     */
    public function setTimezone(DateTimeZone|PhoDateTimeZone|string $timezone): static
    {
        if (!$timezone) {
            throw new OutOfBoundsException(tr('Cannot set timezone, no timezone specified'));
        }

        return parent::setTimezone(PhoDateTimeZone::new($timezone)->getPhpDateTimeZone());
    }


    /**
     * Round the current date time object contents to the specified segment
     *
     * @param DateTimeSegment $segment
     *
     * @return static
     */
    public function round(DateTimeSegment $segment): static
    {
        $date = $this->format('Y m d H i s v u');
        $date = explode(' ', $date);

        switch ($segment) {
            case DateTimeSegment::millennium:
                // no break

            case DateTimeSegment::decennium:
                // no break

            case DateTimeSegment::century:
                // no break

            case DateTimeSegment::week:
                // no break

            case DateTimeSegment::microsecond:
                throw new OutOfBoundsException(tr('Cannot round date to requested segment ":segment"', [
                    ':segment' => $segment,
                ]));

            case DateTimeSegment::year:
                $date[1] = 0;
                // no break

            case DateTimeSegment::month:
                $date[2] = 0;
                // no break

            case DateTimeSegment::day:
                $date[3] = 0;
                // no break

            case DateTimeSegment::hour:
                $date[4] = 0;
                // no break

            case DateTimeSegment::minute:
                $date[5] = 0;
                // no break

            case DateTimeSegment::second:
                $date[6] = 0;
                // no break

            case DateTimeSegment::millisecond:
                $date[7] = 0;
        }

        $this->setDate((int) $date[0], (int) $date[1], (int) $date[2]);
        $this->setTime((int) $date[3], (int) $date[4], (int) $date[5], (int) $date[7]);

        return $this;
    }


    /**
     * Makes this date at the start of the day
     *
     * @return static
     */
    public function makeDayStart(): static
    {
        $date = $this->format('Y m d');
        $date = explode(' ', $date);

        $this->setDate((int) $date[0], (int) $date[1], (int) $date[2]);
        $this->setTime(0, 0, 0, 0);

        return $this;
    }


    /**
     * Makes this date at the end of the day
     *
     * @return static
     */
    public function makeDayEnd(): static
    {
        $date = $this->format('Y m d');
        $date = explode(' ', $date);

        $this->setDate((int) $date[0], (int) $date[1], (int) $date[2]);
        $this->setTime(23, 59, 59, 999999);

        return $this;
    }


    /**
     * Makes this date have the current time
     *
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function makeCurrentTime(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        $time = PhoDateTime::new('now', $timezone ?? $this->getTimezone())->format('H i s u');
        $time = explode(' ', $time);

        $this->setTime((int) $time[0], (int) $time[1], (int) $time[2], (int) $time[3]);

        return $this;
    }


    /**
     * Returns the current year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getYear(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('Y');
    }


    /**
     * Returns the current month of the year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMonth(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('m');
    }


    /**
     * Returns the current week of the year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getWeek(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('W');
    }


    /**
     * Returns the current day of the month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getDay(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('d');
    }


    /**
     * Returns the current hour of the day
     *
     * @note will return the hour in 24 hours format
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getHour(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('H');
    }


    /**
     * Returns the current minute of the hour
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMinute(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('i');
    }


    /**
     * Returns the current second of the minute
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getSecond(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('s');
    }


    /**
     * Returns the current millisecond of the second
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMillisecond(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('v');
    }


    /**
     * Returns the current microsecond of the second
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMicroSecond(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('u');
    }


    /**
     * Returns a string representation of how long ago the specified date was, from now
     *
     * @param PhoDate|PhoDateTimeInterface|string|int|null $date
     * @param bool                                $microseconds
     *
     * @return string
     */
    public function getAge(PhoDate|PhoDateTimeInterface|string|int|null $date = null, bool $microseconds = false): string
    {
        if (!is_object($date)) {
            if (is_integer($date)) {
                $timestamp = $date;
                $date      = new PhoDateTime();
                $date->setTimestamp($timestamp);

            } else {
                $date = new PhoDateTime($date);
            }
        }

        $diff = $this->diff($date);

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
