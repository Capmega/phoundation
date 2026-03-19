<?php

namespace Phoundation\Date\Interfaces;

use Phoundation\Exception\OutOfBoundsException;

interface PhoDateIntervalInterface
{
    /**
     * Rounds up the microseconds to whole seconds
     *
     * @return static
     */
    public function roundUp(): static;

    /**
     * Returns the interval to generate this DateInterval object
     *
     * @return string
     */
    public function getInterval(): string;

    /**
     * Returns the number of years for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalYears(bool $round_down = false): int;

    /**
     * Returns the number of months for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalMonths(bool $round_down = false): int;

    /**
     * Returns the number of days for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalDays(bool $round_down = false): int;

    /**
     * Returns the number of weeks for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalWeeks(bool $round_down = false): int;

    /**
     * Returns the total number of hours and minute fraction for this date interval
     *
     * @return float
     */
    public function getTotalHoursWithFraction(): float;

    /**
     * Returns the total number of minutes for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalMinutes(bool $round_down = false): int;

    /**
     * Returns the total number of hours for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalHours(bool $round_down = false): int;

    /**
     * Returns the number of microseconds for this date interval
     *
     * @return int
     */
    public function getTotalMicroSeconds(): int;

    /**
     * Returns the number of milliseconds for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalMilliSeconds(bool $round_down = false): int;

    /**
     * Returns the number of seconds for this date interval
     *
     * @param bool $round_down
     *
     * @return int
     */
    public function getTotalSeconds(bool $round_down = false): int;

    /**
     * Returns this DateInterval data in human-readable format
     *
     * @param string|null $round
     * @param string|null $limit
     *
     * @return string
     */
    public function getHumanReadableShort(?string $round = null, ?string $limit = null): string;

    /**
     * Returns this DateInterval data in human-readable format
     *
     * @param string|null $round
     * @param string|null $limit
     *
     * @return string
     */
    public function getHumanReadableLong(?string $round = null, ?string $limit = null): string;

    /**
     * Checks that the contents of this object are valid
     *
     * @return static
     *
     * @throws OutOfBoundsException
     */
    public function checkValid(): static;

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
    public function normalize(?int $days_in_month = 30): static;

    /**
     * Returns true if this diff spans more than a year
     *
     * @return bool
     */
    public function isMoreThanAYear(): bool;

    /**
     * Returns true if this diff spans more than a month
     *
     * @return bool
     */
    public function isMoreThanAMonth(): bool;

    /**
     * Returns true if this diff spans more than a week
     *
     * @return bool
     */
    public function isMoreThanAWeek(): bool;

    /**
     * Returns true if this diff spans more than a day
     *
     * @return bool
     */
    public function isMoreThanADay(): bool;

    /**
     * Returns true if this diff spans more than an hour
     *
     * @return bool
     */
    public function isMoreThanAnHour(): bool;

    /**
     * Returns true if this diff spans more than an minute
     *
     * @return bool
     */
    public function isMoreThanAMinute(): bool;

    /**
     * Returns true if this diff spans more than a second
     *
     * @return bool
     */
    public function isMoreThanASecond(): bool;

    /**
     * Returns true if this diff spans more than a millisecond
     *
     * @return bool
     */
    public function isMoreThanAMilliSecond(): bool;

    /**
     * Returns true if this diff spans more than a microsecond
     *
     * @return bool
     */
    public function isMoreThanAMicroSecond(): bool;
}