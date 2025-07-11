<?php

/**
 * Class PhoDateTime
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date;

use DateTimeInterface;
use DateTimeZone;
use Exception;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Stringable;


class PhoDateTimeImmutable extends \DateTimeImmutable implements Stringable, Interfaces\PhoDateTimeInterface
{
    /**
     * Returns a new DateTime object for today
     *
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function today(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        return new static('today 00:00:00', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object
     *
     * @param PhoDate|PhoDateTimeInterface|string      $datetime
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function new(PhoDate|PhoDateTimeInterface|string $datetime = 'now', DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        $timezone = get_null($timezone);

        if (is_string($timezone)) {
            $timezone = new PhoDateTimeZone($timezone);
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
            case EnumDateFormat::mysql_datetime:
                $format = 'Y-m-d H:i:s';
                break;
        }

        return parent::format($format);
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function tomorrow(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        return new static('tomorrow 00:00:00', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     * @throws Exception
     */
    public static function yesterday(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        return new static(' 00:00:00', PhoDateTimeZone::new($timezone));
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
     * @return PhoDateInterval
     */
    public function diff(DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): PhoDateInterval
    {
        return new PhoDateInterval(parent::diff($targetObject, $absolute));
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function setTimezone(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        if ($timezone) {
            parent::setTimezone(PhoDateTimeZone::new($timezone)
                                               ->getPhpDateTimeZone());
        }

        return $this;
    }


    /**
     * Returns a new DateTimeImmutable object for the start of this day
     *
     * @return static
     */
    public function getDayStart(): static
    {
        return PhoDateTimeImmutable::new($this->format('Y-m-d 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns a new DateTimeImmutable object for the end of this day
     *
     * @return static
     */
    public function getDayStop(): static
    {
        return PhoDateTimeImmutable::new($this->format('Y-m-d 23:59:59.999999'), $this->getTimezone());
    }


    public function increaseDay(): static
    {
        throw UnderConstructionException::new(tr('This method is under construction'));
        // TODO: Implement increaseDay() method.
    }


    public function decreaseDay(): static
    {
        throw UnderConstructionException::new(tr('This method is under construction'));
        // TODO: Implement decreaseDay() method.
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
        throw UnderConstructionException::new();
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
        throw UnderConstructionException::new();
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


    public function getNextDay(DateTimeZone|string|null $timezone = null): static
    {
        // TODO: Implement getNextDay() method.
    }


    public function getPreviousDay(DateTimeZone|string|null $timezone = null): static
    {
        // TODO: Implement getPreviousDay() method.
    }
}
