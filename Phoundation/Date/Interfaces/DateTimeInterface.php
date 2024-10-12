<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;

use Phoundation\Date\DateInterval;
use Phoundation\Date\DateTime;
use Phoundation\Date\DateTimeZone;

interface DateTimeInterface extends \DateTimeInterface
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
     * @return DateInterval
     */
    public function diff(\DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): DateInterval;


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @param \DateTimeZone|DateTimeZone|string $timezone
     *
     * @return DateTimeInterface
     */
    public function setTimezone(\DateTimeZone|DateTimeZone|string $timezone): static;


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
}
