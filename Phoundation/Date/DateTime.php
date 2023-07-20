<?php

declare(strict_types=1);

namespace Phoundation\Date;

use DateTimeInterface;
use Exception;
use Phoundation\Date\Enums\DateTimeSegment;
use Phoundation\Date\Enums\Interfaces\DateTimeSegmentInterface;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;


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
        return $this->format('Y-m-d H:i:s.v.u');
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
        if (is_object($datetime)) {
            // Return a new DateTime object with the specified date in the specified timezone
            parent::__construct($datetime->format('Y-m-d H:i:s.vu'), $timezone);
        }

        parent::__construct($datetime, $timezone);
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
            return new static($datetime->format('Y-m-d H:i:s.vu'), $timezone);
        }

        return new static($datetime, $timezone);
    }


    /**
     * Returns the difference between two DateTime objects
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param DateTimeInterface $targetObject
     * @param bool $absolute
     * @return DateInterval
     */
    public function diff($targetObject, $absolute = false): DateInterval
    {
        return new DateInterval(parent::diff($targetObject, $absolute));
    }


    /**
     * Returns a new DateTime object
     *
     * @param \DateTimeZone|string|null $timezone
     * @return static
     * @throws Exception
     */
    public static function today(\DateTimeZone|string|null $timezone = null): static
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
    public static function tomorrow(\DateTimeZone|string|null $timezone = null): static
    {
        return new static('tomorrow', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param Date|DateTime|string $datetime
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     * @return static
     * @throws Exception
     */
    public static function yesterday(Date|DateTime|string $datetime = 'yesterday 00:00:00', \DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        return new static('yesterday', DateTimeZone::new($timezone));
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
                throw new OutOfBoundsException(tr('Cannot round date to requested segment ":segment"', [
                    ':segment' => $segment
                ]));

            case DateTimeSegment::year:
                $date[0] = 0;
                // no break

            case DateTimeSegment::month:
                $date[1] = 0;
                // no break

            case DateTimeSegment::day:
                $date[2] = 0;
                // no break

            case DateTimeSegment::hour:
                $date[3] = 0;
                // no break

            case DateTimeSegment::minute:
                $date[4] = 0;
                // no break

            case DateTimeSegment::second:
                $date[5] = 0;
                // no break

            case DateTimeSegment::millisecond:
                $date[6] = 0;
                // no break

            case DateTimeSegment::microsecond:
                $date[7] = 0;
        }

        $this->setDate((int) $date[0], (int) $date[1], (int) $date[2]);
        $this->setTime((int) $date[3], (int) $date[4], (int) $date[5], (int) $date[7]);

        return $this;
    }
}
