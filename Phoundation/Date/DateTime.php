<?php

declare(strict_types=1);

namespace Phoundation\Date;

use DateTimeInterface;
use Exception;
use Phoundation\Date\Enums\DateTimeSegment;
use Phoundation\Date\Enums\Interfaces\DateTimeSegmentInterface;
use Phoundation\Date\Exception\DateTimeException;
use Phoundation\Exception\OutOfBoundsException;
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
     * @throws Exception
     */
    public function __construct(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null)
    {
        // Ensure we have NULL or datetimezone object for parent constructor
        $timezone = get_null($timezone);

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        // Return Phoundation DateTime object for whatever given $datetime
        try {
            if (is_object($datetime)) {
                // Return a new DateTime object with the specified date in the specified timezone
                parent::__construct($datetime->format('Y-m-d H:i:s.u'), $timezone);
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
     * @throws Exception
     */
    public static function new(DateTime|string|null $datetime = 'now', \DateTimeZone|string|null $timezone = null): static
    {
        if (is_object($datetime)) {
            // Return a new DateTime object with the specified date in the specified timezone
            return new static($datetime->format('Y-m-d H:i:s.u'), $timezone);
        }

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
     * @throws Exception
     */
    public function diff($targetObject, $absolute = false, bool $roundup = true): DateInterval
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
     * Subtracts an amount of days, months, years, hours, minutes and seconds from a DateTime object
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateInterval $interval
     * @return \DateTime
     * @throws Exception
     */
    public function sub(\DateInterval $interval): \DateTime
    {
        return new DateTime(parent::sub($interval));
    }


    /**
     * Adds an amount of days, months, years, hours, minutes and seconds to a DateTime object
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateInterval $interval
     * @return \DateTime
     * @throws Exception
     */
    public function add(\DateInterval $interval): \DateTime
    {
        return new DateTime(parent::add($interval));
    }


    /**
     * Returns a new DateTime object
     *
     * @param \DateTimeZone|string|null $timezone
     * @return static
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public static function getYesterday(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('yesterday', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object
     *
     * @param \DateTimeZone|string|null $timezone
     * @return bool
     * @throws Exception
     */
    public function isToday(\DateTimeZone|string|null $timezone = null): bool
    {
        return $this->format('y-m-d') == static::new('today', DateTimeZone::new($timezone ?? $this->getTimezone()))->format('y-m-d');
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param \DateTimeZone|string|null $timezone
     * @return bool
     * @throws Exception
     */
    public function isTomorrow(\DateTimeZone|string|null $timezone = null): bool
    {
        return $this->format('y-m-d') == static::new('tomorrow', DateTimeZone::new($timezone ?? $this->getTimezone()))->format('y-m-d');
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param \DateTimeZone|string|null $timezone
     * @return bool
     * @throws Exception
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
     * @throws Exception
     */
    public function makeCurrentTime(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        $time = DateTime::new('now', $timezone ?? $this->getTimezone())->format('H i s u');
        $time = explode(' ', $time);

        $this->setTime((int) $time[0], (int) $time[1], (int) $time[2], (int) $time[3]);
        return $this;
    }
}
