<?php

declare(strict_types=1);

namespace Phoundation\Date;

use DateTimeInterface;
use MongoDB\Exception\UnsupportedException;
use Phoundation\Core\Sessions\Session;
use Phoundation\Date\Enums\DateTimeSegment;
use Phoundation\Date\Enums\Interfaces\DateTimeSegmentInterface;
use Phoundation\Date\Exception\DateIntervalException;
use Phoundation\Date\Exception\DateTimeException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * Class DateTime
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class DateTime extends \DateTime implements Stringable, Interfaces\DateTimeInterface
{
    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString() {
        return $this->format('Y-m-d H:i:s.u');
    }


    /**
     * Returns a new DateTime object
     *
     * @param DateTime|string|null $datetime
     * @param \DateTimeZone|string|null $timezone
     */
    public function __construct(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null)
    {
        // Ensure we have NULL or timezone object for parent constructor
        $timezone = get_null($timezone);
        $datetime = $datetime ?? 'now';

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        // Return Phoundation DateTime object for whatever given $datetime
        try {
            if (is_object($datetime)) {
                // Return a new DateTime object with the specified date in the specified timezone
                parent::__construct($datetime->format('Y-m-d H:i:s.u'), $timezone ?? $datetime->getTimezone());

            } else {
                parent::__construct($datetime, $timezone);
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
     * Returns a new DateTime object
     *
     * @param DateTime|string|null $datetime
     * @param \DateTimeZone|string|null $timezone
     * @return static
     */
    public static function new(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): static
    {
        return new static($datetime, $timezone);
    }


    /**
     * Returns the difference between two DateTime objects
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param DateTimeInterface $targetObject
     * @param bool $absolute
     * @param bool $roundup
     * @return DateInterval
     * @throws DateIntervalException
     */
    public function diff(\DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): DateInterval
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
     * Subtracts an number of days, months, years, hours, minutes and seconds from a DateTime object
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateInterval $interval
     * @return \DateTime
     */
    public function sub(\DateInterval $interval): \DateTime
    {
        return new DateTime(parent::sub($interval));
    }


    /**
     * Adds an number of days, months, years, hours, minutes and seconds to a DateTime object
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateInterval $interval
     * @return \DateTime
     */
    public function add(\DateInterval $interval): \DateTime
    {
        return new DateTime(parent::add($interval));
    }


    /**
     * Returns a new DateTime object for the end of the day of the specified date
     *
     * @param DateTime|string|null $datetime
     * @param \DateTimeZone|string|null $timezone
     * @return static
     */
    public static function getBeginningOfDay(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): static
    {
        return new static(static::new($datetime)->format('Y-m-d 00:00:00'), DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the end of the day of the specified date
     *
     * @param DateTime|string|null $datetime
     * @param \DateTimeZone|string|null $timezone
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
     * @return static
     */
    public static function getLastDayOfYear(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-12-31', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this month
     *
     * @param \DateTimeZone|string|null $timezone
     * @return static
     */
    public static function getFirstDayOfMonth(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-m-01', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this month
     *
     * @param \DateTimeZone|string|null $timezone
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
     * @return static
     */
    public static function getFirstDayOfWeek(\DateTimeZone|string|null $timezone = null): static
    {
        return new static(Session::getConfig()->getString('datetime.week.start', 'monday') . ' this week', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     * @return static
     */
    public static function getLastDayOfWeek(\DateTimeZone|string|null $timezone = null): static
    {
        return new static(Session::getConfig()->getString('datetime.week.stop', 'sunday') . ' this week', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     * @return static
     */
    public static function getFirstPeriodStart(\DateTimeZone|string|null $timezone = null): static
    {
        return static::getFirstDayOfMonth();
    }


    /**
     * Returns a new DateTime object for the last day of this week
     *
     * @param \DateTimeZone|string|null $timezone
     * @return static
     */
    public static function getLastPeriodStart(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('Y-m-15', DateTimeZone::new($timezone));
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
    public function getCurrentMonthStart(): static
    {
        $datetime = static::new($this);
        $date_day = $datetime->format('d');

        return DateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getCurrentMonthStop(): static
    {
        $datetime = static::new($this);
        $max      = $datetime->format('t');

        return DateTime::new($datetime->format('Y-m-t 23:59:59.999999'), $datetime->getTimezone());
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
     * @return bool
     */
    public function isToday(\DateTimeZone|string|null $timezone = null): bool
    {
        return $this->format('y-m-d') == static::new('today', DateTimeZone::new($timezone ?? $this->getTimezone()))->format('y-m-d');
    }


    /**
     * Returns true if the current date is tomorrow
     *
     * @param \DateTimeZone|string|null $timezone
     * @return bool
     */
    public function isTomorrow(\DateTimeZone|string|null $timezone = null): bool
    {
        return $this->format('y-m-d') == static::new('tomorrow', DateTimeZone::new($timezone ?? $this->getTimezone()))->format('y-m-d');
    }


    /**
     * Returns true if the current date is yesterday
     *
     * @param \DateTimeZone|string|null $timezone
     * @return bool
     */
    public function isYesterday(\DateTimeZone|string|null $timezone = null): bool
    {
        return $this->format('y-m-d') == static::new('yesterday', DateTimeZone::new($timezone ?? $this->getTimezone()))->format('y-m-d');
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @return $this
     */
    public function setTimezone(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        if ($timezone) {
            parent::setTimezone(DateTimeZone::new($timezone)->getPhpDateTimeZone());
        }

        return $this;
    }


    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * @param string|null $format
     * @return string
     */
    public function format(?string $format = null): string
    {
        switch (strtolower($format)) {
            case 'mysql':
                $format = 'Y-m-d H:i:s';
                break;
        }

        return parent::format($format);
    }


    /**
     * Round the current date time object contents to the specified segment
     *
     * @param DateTimeSegmentInterface $segment
     * @return $this
     */
    public function round(DateTimeSegmentInterface $segment): static
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
                    ':segment' => $segment
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function makeCurrentTime(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        $time = DateTime::new('now', $timezone ?? $this->getTimezone())->format('H i s u');
        $time = explode(' ', $time);

        $this->setTime((int) $time[0], (int) $time[1], (int) $time[2], (int) $time[3]);
        return $this;
    }


    /**
     * Returns the date time format from PHP to JS
     *
     * @param string $php_date_format
     * @return string
     */
    public static function convertPhpToJsFormat(string $php_date_format): string
    {
        throw new UnderConstructionException();
    }


    /**
     * Returns the date time format from JS to PHP
     *
     * @param string $js_format
     * @return string
     * @throws OutOfBoundsException|UnsupportedException
     */
    public static function convertJsToPhpFormat(string $js_format): string
    {
        show($js_format);
        $php_format = $js_format;
        $lookup     = [
                'M'         => ['php'      => 'n'],
                'Mo'        => ['php'      => 'n',
                                'callback' => function (&$value) {
                                     $value = $value . Strings::ordinalIndicator($value);
                                 }],
                'MM'        => ['php'      => 'm'],
                'MMM'       => ['php'      => 'M'],
                'MMMM'      => ['php'      => 'F'],
                'Q'         => null,
                'Qo'        => null,
                'D'         => ['php' => 'j'],
                'Do'        => ['php' => 'jS'],
                'DD'        => ['php' => 'd'],
                'DDD'       => null,
                'DDDo'      => null,
                'DDDD'      => null,
                'd'         => null,
                'do'        => null,
                'dd'        => null,
                'ddd'       => null,
                'dddd'      => null,
                'e'         => null,
                'E'         => null,
                'w'         => null,
                'wo'        => null,
                'ww'        => null,
                'W'         => null,
                'Wo'        => null,
                'WW'        => null,
                'YY'        => null,
                'YYYY'      => ['php' => 'Y'],
                'YYYYYY'    => null,
                'Y'         => null,
                'y'         => null,
                'N'         => null,
                'NN'        => null,
                'NNN'       => null,
                'NNNN'      => null,
                'NNNNN'     => null,
                'gg'        => null,
                'gggg'      => null,
                'GG'        => null,
                'GGGG'      => null,
                'A'         => null,
                'a'         => null,
                'H'         => ['php' => 'G'],
                'HH'        => ['php' => 'H'],
                'h'         => ['php' => 'g'],
                'hh'        => ['php' => 'h'],
                'k'         => null,
                'kk'        => null,
                'm'         => null,
                'mm'        => ['php' => 'i'],
                's'         => null,
                'ss'        => ['php' => 's'],
                'S'         => null,
                'SS'        => null,
                'SSS'       => null,
                'SSSS'      => null,
                'SSSSS'     => null,
                'SSSSSS'    => null,
                'SSSSSSS'   => null,
                'SSSSSSSS'  => null,
                'SSSSSSSSS' => null,
                'z'         => null,
                'zz'        => null,
                'Z'         => null,
                'ZZ'        => null,
                'X'         => null,
                'x'         => null,
        ];

        // Get all javascript matches
        preg_match_all('/([a-z])+/i', $js_format, $matches);

        if (empty($matches)) {
            throw new OutOfBoundsException(tr('Failed to convert Javascript date time format string ":format" to PHP', [
                ':format' => $js_format
            ]));
        }

        $matches = $matches[0];
        $matches = Arrays::sortByValueLength($matches);

        foreach ($matches as $match) {
            if (!array_key_exists($match, $lookup)) {
                throw new OutOfBoundsException(tr('Unknown Javascript date time format string identifier ":identifier" encountered in Javascript date time format string ":format"', [
                    ':identifier' => $match,
                    ':format'     => $js_format
                ]));
            }

            if ($lookup[$match] === null) {
                throw new UnsupportedException(tr('Javascript date time format string identifier ":identifier" encountered in Javascript date time format string ":format" is currently not supported', [
                    ':identifier' => $match,
                    ':format'     => $js_format
                ]));
            }

            $php_format = str_replace($match, $lookup[$match]['php'], $php_format);
        }

        return $php_format;
    }
}
