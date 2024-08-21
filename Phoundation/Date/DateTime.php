<?php

/**
 * Class DateTime
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

use DateTimeInterface;
use MongoDB\Exception\UnsupportedException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\SessionConfig;
use Phoundation\Core\Sessions\Session;
use Phoundation\Date\Enums\DateTimeSegment;
use Phoundation\Date\Exception\DateIntervalException;
use Phoundation\Date\Exception\DateTimeException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


class DateTime extends \DateTime implements Stringable, Interfaces\DateTimeInterface
{
    /**
     * Returns a new DateTime object
     *
     * @param DateTime|string|int|null  $datetime
     * @param \DateTimeZone|string|null $timezone
     */
    public function __construct(DateTime|string|int|null $datetime = 'now', \DateTimeZone|string|null $timezone = null)
    {
        // Ensure we have NULL or timezone object for parent constructor
        $timezone = get_null($timezone);
        $datetime = $datetime ?? 'now';

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        if (is_int($datetime)) {
            $datetime = DateTime::new('now', $timezone)->setTimestamp($datetime)->format('Y-m-d H:i:s.u');
        }

        // Return Phoundation DateTime object for whatever given $datetime
        try {
            if (is_object($datetime)) {
                // Return a new DateTime object with the specified date in the specified timezone
                parent::__construct($datetime->format('Y-m-d H:i:s.u'), $timezone ?? $datetime->getTimezone());

            } else {
                $normalized = DateFormats::normalizeDate($datetime, '-');
                parent::__construct($normalized, $timezone);
            }

        } catch (Throwable $e) {
            throw new DateTimeException(tr('Failed to create DateTime object for given $datetime ":datetime" / timezone ":timezone" because ":e"', [
                ':datetime' => $datetime,
                ':timezone' => $timezone,
                ':e'        => $e->getMessage(),
            ]), $e);
        }
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
            case 'human_readable_date':
                return SessionConfig::getString('locale.dates.formats.human.date', DateFormats::getDefaultPhp());

            case 'human_readable_date_time':
                return SessionConfig::getString('locale.dates.formats.human.datetime', DateTimeFormats::getDefaultPhp());

            case 'iso_date':
                return 'd-m-Y';

            case 'iso_date_time':
                return 'Y-m-d H:i:s';

            case 'mysql':
                $format = 'Y-m-d H:i:s';
                break;
        }

        return $format;
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
     * Returns this date time as a human-readable date string
     *
     * @return string
     */
    public function getHumanReadableDate(): string
    {
        return $this->format('human_readable_date');
    }


    /**
     * Returns this date time as a human-readable date-time string
     *
     * @return string
     */
    public function getHumanReadableDateTime(): string
    {
        return $this->format('human_readable_date_time');
    }


    /**
     * Returns a new DateTime object for the end of the day of the specified date
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getBeginningOfDay(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): static
    {
        return new static(static::new($datetime)->format('Y-m-d 00:00:00'), DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object
     *
     * @param DateTime|string|int|null  $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function new(DateTime|string|int|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): static
    {
        return new static($datetime, $timezone);
    }


    /**
     * Returns a new DateTime object for the end of the day of the specified date
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getEndOfDay(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): static
    {
        return new static(static::new($datetime)->format('Y-m-d 23:59:59.999999'), DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getToday(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('today', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getTomorrow(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('tomorrow', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getYesterday(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('yesterday', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this year
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getFirstDayOfYear(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-01-01', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this year
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getLastDayOfYear(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-12-31', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this month
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getLastDayOfMonth(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-m-t', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getFirstDayOfWeek(\DateTimeZone|string|null $timezone = null): static
    {
        return new static(SessionConfig::getString('datetime.week.start', 'monday') . ' this week', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getLastDayOfWeek(\DateTimeZone|string|null $timezone = null): static
    {
        return new static(SessionConfig::getString('datetime.week.stop', 'sunday') . ' this week', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getFirstPeriodStart(\DateTimeZone|string|null $timezone = null): static
    {
        return static::getFirstDayOfMonth();
    }


    /**
     * Returns a new DateTime object for the first day of this month
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getFirstDayOfMonth(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-m-01', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function getLastPeriodStart(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-m-15', DateTimeZone::new($timezone));
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s.u');
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
     * @return DateInterval
     * @throws DateIntervalException
     */
    public function diff(DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): DateInterval
    {
        // DateInterval doesn't calculate milliseconds / microseconds, do that manually
        $diff    = new DateInterval(parent::diff($targetObject, $absolute), $roundup);
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
            return DateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) previous month
        $start = $datetime->sub(DateInterval::createFromDateString('1 month'));

        return DateTime::new($start->format('Y-m-16 00:00:00'), $datetime->getTimezone());
    }


    /**
     * Subtracts an number of days, months, years, hours, minutes and seconds from a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateInterval $interval
     *
     * @return \DateTime
     */
    public function sub(\DateInterval $interval): \DateTime
    {
        return new DateTime(parent::sub($interval));
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
            $start = $datetime->add(DateInterval::createFromDateString('1 month'));

            return DateTime::new($start->format('Y-m-1 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return DateTime::new($datetime->format('Y-m-16 00:00:00'), $datetime->getTimezone());
    }


    /**
     * Adds an number of days, months, years, hours, minutes and seconds to a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateInterval $interval
     *
     * @return \DateTime
     */
    public function add(\DateInterval $interval): \DateTime
    {
        return new DateTime(parent::add($interval));
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
            return DateTime::new($datetime->format('Y-m-16 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return DateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
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
            return DateTime::new($datetime->format('Y-m-t 23:59:59.999999'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return DateTime::new($datetime->format('Y-m-15 23:59:59.999999'), $datetime->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getMonthStart(): static
    {
        return DateTime::new($this->format('Y-m-1 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getMonthStop(): static
    {
        return DateTime::new($this->format('Y-m-t 23:59:59.999999'), $this->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getDayStart(): static
    {
        return DateTime::new($this->format('Y-m-d 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getDayStop(): static
    {
        return DateTime::new($this->format('Y-m-d 23:59:59.999999'), $this->getTimezone());
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
     * @param \DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isToday(\DateTimeZone|string|null $timezone = null): bool
    {
        $today = static::new('today', DateTimeZone::new($timezone ?? $this->getTimezone()))
                       ->format('y-m-d');

        return $this->format('y-m-d') == $today;
    }


    /**
     * Returns true if the current date is tomorrow
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isTomorrow(\DateTimeZone|string|null $timezone = null): bool
    {
        $tomorrow = static::new('tomorrow', DateTimeZone::new($timezone ?? $this->getTimezone()))
                          ->format('y-m-d');

        return $this->format('y-m-d') == $tomorrow;
    }


    /**
     * Returns true if the current date is yesterday
     *
     * @param \DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isYesterday(\DateTimeZone|string|null $timezone = null): bool
    {
        $yesterday = static::new('yesterday', DateTimeZone::new($timezone ?? $this->getTimezone()))
                           ->format('y-m-d');

        return $this->format('y-m-d') == $yesterday;
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @return static
     */
    public function setTimezone(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        if ($timezone) {
            parent::setTimezone(DateTimeZone::new($timezone)->getPhpDateTimeZone());
        }

        return $this;
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
     * @return static
     */
    public function makeCurrentTime(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        $time = DateTime::new('now', $timezone ?? $this->getTimezone())->format('H i s u');
        $time = explode(' ', $time);

        $this->setTime((int) $time[0], (int) $time[1], (int) $time[2], (int) $time[3]);

        return $this;
    }


    /**
     * Returns the current year
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getYear(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('Y');
    }


    /**
     * Returns the current month of the year
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getMonth(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('m');
    }


    /**
     * Returns the current week of the year
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getWeek(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('W');
    }


    /**
     * Returns the current day of the month
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getDay(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('d');
    }


    /**
     * Returns the current hour of the day
     *
     * @note will return the hour in 24 hours format
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getHour(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('H');
    }


    /**
     * Returns the current minute of the hour
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getMinute(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('i');
    }


    /**
     * Returns the current second of the minute
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getSecond(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('s');
    }


    /**
     * Returns the current millisecond of the second
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getMillisecond(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('v');
    }


    /**
     * Returns the current microsecond of the second
     *
     * @param DateTime|string|null      $datetime
     * @param \DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public static function getMicroSecond(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): int
    {
        return (int) DateTime::new($datetime, $timezone)->format('u');
    }
}
