<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;

use Phoundation\Date\PhoDateInterval;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\PhoDateTimeZone;

interface PhoDateTimeInterface extends \DateTimeInterface
{
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
    public function diff(\DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): PhoDateInterval;


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @param \DateTimeZone|PhoDateTimeZone|string $timezone
     *
     * @return PhoDateTimeInterface
     */
    public function setTimezone(\DateTimeZone|PhoDateTimeZone|string $timezone): static;


    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * @param string|null $format
     *
     * @return string
     */
    public function format(?string $format = null): string;

    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getDayStart(): static;

    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getDayStop(): static;

    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function increaseDay(): static;


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function decreaseDay(): static;
}
