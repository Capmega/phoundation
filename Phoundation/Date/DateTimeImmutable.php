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
use Exception;
use Stringable;

class DateTimeImmutable extends \DateTimeImmutable implements Stringable, Interfaces\DateTimeInterface
{
    /**
     * Returns a new DateTime object for today
     *
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function today(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        return new static('today 00:00:00', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object
     *
     * @param Date|DateTime|string                   $datetime
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function new(Date|DateTime|string $datetime = 'now', \DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        $timezone = get_null($timezone);
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        if (is_object($datetime)) {
            // Return a new DateTime object with the specified date in the specified timezone
            return new static($datetime->format('Y-m-d H:i:s.v.u'), $timezone);
        }

        return new static($datetime, $timezone);
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
        switch (strtolower($format)) {
            case 'mysql':
                $format = 'Y-m-d H:i:s';
                break;
        }

        return parent::format($format);
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function tomorrow(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        return new static('tomorrow 00:00:00', DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param \DateTimeZone|DateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function yesterday(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        return new static(' 00:00:00', DateTimeZone::new($timezone));
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s.v');
    }


    /**
     * Returns the difference between two DateTime objects
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateTimeInterface $targetObject
     * @param bool               $absolute
     * @param bool               $roundup
     *
     * @return DateInterval
     */
    public function diff(DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): DateInterval
    {
        return new DateInterval(parent::diff($targetObject, $absolute));
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @return $this
     */
    public function setTimezone(\DateTimeZone|DateTimeZone|string|null $timezone = null): static
    {
        if ($timezone) {
            parent::setTimezone(DateTimeZone::new($timezone)
                                            ->getPhpDateTimeZone());
        }

        return $this;
    }
}
