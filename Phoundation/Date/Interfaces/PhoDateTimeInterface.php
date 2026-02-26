<?php

namespace Phoundation\Date\Interfaces;

use DateInterval;
use DateMalformedStringException;
use DateTimeInterface;
use DateTimeZone;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Enums\EnumDateTimeSegment;
use Phoundation\Date\Enums\EnumDateTimeWidth;
use Phoundation\Date\Exception\DateIntervalException;
use Phoundation\Date\PhoDate;
use Phoundation\Date\PhoDateInterval;
use Phoundation\Date\PhoDateTime;
use Phoundation\Date\PhoDateTimeZone;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;

interface PhoDateTimeInterface
{
    /**
     * Returns the source of this PhoDateTime object
     *
     * @return string
     */
    public function getSource(): string;

    /**
     * Returns the source of this PhoDateTime object
     *
     * @param PhoDateTimeInterface|string|float|int|null $datetime
     *
     * @return static
     */
    public function setSource(PhoDateTimeInterface|string|float|int|null $datetime): static;

    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function increaseDay(): static;

    /**
     * Increase this PhoDateTime object by a specified number of days
     *
     * @param int $days
     *
     * @return static
     */
    public function increaseByDays(int $days = 1): static;

    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function decreaseDay(): static;

    /**
     * Decrease this PhoDateTime object by a specified number of days
     *
     * @param int $days
     *
     * @return static
     */
    public function decreaseByDays(int $days = 1): static;

    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param int $days
     *
     * @return static
     */
    public function increaseDays(int $days): static;

    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param int $days
     *
     * @return static
     */
    public function decreaseDays(int $days): static;

    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * Day    ---    ---
     * d    Day of the month, 2 digits with leading zeros    01 to 31
     * D    A textual representation of a day, three letters    Mon through Sun
     * j    Day of the month without leading zeros    1 to 31
     * l    (lowercase 'L') A full textual representation of the day of the week    Sunday through Saturday
     * N    ISO 8601 numeric representation of the day of the week    1 (for Monday) through 7 (for Sunday)
     * S    English ordinal suffix for the day of the month, 2 characters    st, nd, rd or th. Works well with j
     * w    Numeric representation of the day of the week    0 (for Sunday) through 6 (for Saturday)
     * z    The day of the year (starting from 0)    0 through 365
     *
     * Week    ---    ---
     * W    ISO 8601 week number of year, weeks starting on Monday    Example: 42 (the 42nd week in the year)
     *
     * Month    ---    ---
     * F    A full textual representation of a month, such as January or March    January through December
     * m    Numeric representation of a month, with leading zeros    01 through 12
     * M    A short textual representation of a month, three letters    Jan through Dec
     * n    Numeric representation of a month, without leading zeros    1 through 12
     * t    Number of days in the given month    28 through 31
     *
     * Year    ---    ---
     * L    Whether it is a leap year    1 if it is a leap year, 0 otherwise.
     * o    ISO 8601 week-numbering year. This has the same value as Y, except that if the ISO week number (W) belongs
     *      to the previous or next year, that year is used instead.    Examples: 1999 or 2003
     * X    An expanded full numeric representation of a year, at least 4 digits, with - for years BCE, and + for years
     *      CE. Examples: -0055, +0787, +1999, +10191
     * x    An expanded full numeric representation if required, or a standard full numeral representation if possible
     *      (like Y). At least four digits. Years BCE are prefixed with a -. Years beyond (and including) 10000 are
     *      prefixed by a +.    Examples: -0055, 0787, 1999, +10191
     * Y    A full numeric representation of a year, at least 4 digits, with - for years BCE.    Examples: -0055, 0787,
     *      1999, 2003, 10191
     * y    A two digit representation of a year    Examples: 99 or 03
     *
     * Time    ---    ---
     * a    Lowercase Ante meridiem and Post meridiem       am or pm
     * A    Uppercase Ante meridiem and Post meridiem       AM or PM
     * B    Swatch Internet time    000 through 999
     * g    12-hour format of an hour without leading zeros  1 through 12
     * G    24-hour format of an hour without leading zeros  0 through 23
     * h    12-hour format of an hour with leading zeros    01 through 12
     * H    24-hour format of an hour with leading zeros    00 through 23
     * i    Minutes with leading zeros    00 to 59
     * s    Seconds with leading zeros    00 through 59
     * u    Microseconds. Note that date() will always generate 000000 since it takes an int parameter, whereas
     *      DateTimeInterface::format() does support microseconds if an object of type DateTimeInterface was created
     *      with microseconds. Example: 654321
     * v    Milliseconds. Same note applies as for u.    Example: 654
     *
     * Timezone    ---    ---
     * e    Timezone identifier    Examples: UTC, GMT, Atlantic/Azores
     * I    (capital i) Whether or not the date is in daylight saving time.      1 if Daylight Saving Time, 0 otherwise.
     * O    Difference to Greenwich time (GMT) without colon between hours and minutes    Example: +0200
     * P    Difference to Greenwich time (GMT) with colon between hours and minutes    Example: +02:00
     * p    The same as P, but returns Z instead of +00:00 (available as of PHP 8.0.0)    Examples: Z or +02:00
     * T    Timezone abbreviation, if known; otherwise the GMT offset.    Examples: EST, MDT, +05
     * Z    Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of
     *      UTC is always positive.    -43200 through 50400
     *
     * Full Date/Time    ---    ---
     * c    ISO 8601 date    2004-02-12T15:19:21+00:00
     * r    » RFC 2822/» RFC 5322 formatted date    Example: Thu, 21 Dec 2000 16:01:07 +0200
     * U    Seconds since the Unix Epoch (January 1, 1970 00:00:00 GMT)    See also time()
     *
     * @param EnumDateFormat|string|null $format
     * @param EnumDateTimeWidth $width
     *
     * @return string
     */
    public function format(EnumDateFormat|string|null $format = null, EnumDateTimeWidth $width = EnumDateTimeWidth::default): string;

    /**
     * Returns this date time as a human-readable date string
     *
     * @return string
     */
    public function getHumanReadableDate(): string;

    /**
     * Returns this date time as a human-readable date-time string
     *
     * @return string
     */
    public function getHumanReadableDateTime(): string;

    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getNextDay(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getPreviousDay(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getBeginningOfDay(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getEndOfDay(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the first day of this year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstDayOfYear(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the last day of this year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastDayOfYear(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the first day of this month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstDayOfMonth(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the last day of this month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastDayOfMonth(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for the first
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstPeriodBegin(DateTimeZone|string|null $timezone = null): static;

    /**
     * Returns a new DateTime object for
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastPeriodBegin(DateTimeZone|string|null $timezone = null): static;

    /**
     * Adds a number of days, months, years, hours, minutes and seconds to a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.add.php
     *
     * @param DateInterval $interval
     * @param bool $return_new
     *
     * @return static
     */
    public function add(DateInterval $interval, bool $return_new = true): static;

    /**
     * Subtracts a number of days, months, years, hours, minutes and seconds from a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.sub.php
     *
     * @param DateInterval $interval
     * @param bool $return_new
     *
     * @return static
     */
    public function sub(DateInterval $interval, bool $return_new = true): static;

    /**
     * Returns the difference between two DateTime objects
     *
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param DateTimeInterface $targetObject
     * @param bool $absolute
     * @param bool $roundup
     *
     * @return PhoDateInterval
     * @throws DateIntervalException
     */
    public function diff(DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): PhoDateInterval;

    /**
     * Returns a new DateTime object for the first day of the previous monthly period
     *
     * @return static
     */
    public function getPreviousPeriodBegin(): static;

    /**
     * Returns a new DateTime object for the first day of the next monthly period
     *
     * @return static
     */
    public function getNextPeriodBegin(): static;

    /**
     * Returns a new DateTime object for the first day of the current monthly period
     *
     * @return static
     */
    public function getCurrentPeriodBegin(): static;

    /**
     * Returns the stop date for the period in which this date is
     *
     * @return static
     */
    public function getCurrentPeriodEnd(): static;

    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getMonthBegin(): static;

    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getMonthEnd(): static;

    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getDayBegin(): static;

    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getDayEnd(): static;

    /**
     * Returns true if this date is the first day of a period (the 1st or 16th of a month)
     *
     * @return bool
     */
    public function isPeriodBegin(): bool;

    /**
     * Returns true if the current date is today
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isToday(DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date is tomorrow
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isTomorrow(DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date is yesterday
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isYesterday(DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @param DateTimeZone|PhoDateTimeZone|string $timezone
     *
     * @return static
     */
    public function setTimezone(DateTimeZone|PhoDateTimeZone|string $timezone): static;

    /**
     * Round the current date time object contents to the specified segment
     *
     * @param EnumDateTimeSegment $segment
     *
     * @return static
     */
    public function round(EnumDateTimeSegment $segment): static;

    /**
     * Makes this date at the start of the day
     *
     * @return static
     */
    public function makeDayBegin(): static;

    /**
     * Makes this date at the end of the day
     *
     * @return static
     */
    public function makeDayEnd(): static;

    /**
     * Makes this date have the current time
     *
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function makeCurrentTime(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static;

    /**
     * Returns the current year of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getYear(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified year
     *
     * @param int $year The year to use for the new date
     *
     * @return static
     */
    public function setYear(int $year): static;

    /**
     * Returns the current month of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getMonth(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified month
     *
     * @param int $month The month to use for the new date
     *
     * @return static
     */
    public function setMonth(int $month): static;

    /**
     * Returns the current week of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getWeek(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns the current day of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getDay(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified day
     *
     * @param int $day The day to use for the new date
     *
     * @return static
     */
    public function setDay(int $day): static;

    /**
     * Returns the current hour of this datetime
     *
     * @note will return the hour in 24 hours format
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getHour(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified hour
     *
     * @param int $hour The hour to use for the new date
     *
     * @return static
     */
    public function setHour(int $hour): static;

    /**
     * Returns the current minute of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getMinute(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified minute
     *
     * @param int $minute The minute to use for the new date
     *
     * @return static
     */
    public function setMinute(int $minute): static;

    /**
     * Returns the current second of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getSecond(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified second
     *
     * @param int $second The second to use for the new date
     *
     * @return static
     */
    public function setSecond(int $second): static;

    /**
     * Returns the current millisecond of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getMillisecond(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified millisecond
     *
     * @param int $millisecond The millisecond to use for the new date
     *
     * @return static
     */
    public function SetMillisecond(int $millisecond): static;

    /**
     * Returns the current microsecond of this datetime
     *
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return int
     */
    public function getMicroSecond(DateTimeZone|string|null $timezone = null): int;

    /**
     * Returns a new DateTime object with this date, but with the specified microsecond
     *
     * @param int $microsecond The microsecond to use for the new date
     *
     * @return static
     */
    public function SetMicrosecond(int $microsecond): static;

    /**
     * Returns true if the current date has one or more of the specified years
     *
     * @param array|string|int $values One or more of the years(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasYear(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified months
     *
     * @param array|string|int $values One or more of the months(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasMonth(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified weeks
     *
     * @param array|string|int $values One or more of the weeks(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasWeek(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified days
     *
     * @param array|string|int $values One or more of the day(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasDay(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified hour
     *
     * @param array|string|int $values One or more of the hour(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasHour(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified minutes
     *
     * @param array|string|int $values One or more of the minutes(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasMinute(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified seconds
     *
     * @param array|string|int $values One or more of the second(s) that this date object must have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasSecond(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified milliseconds
     *
     * @param array|string|int $values One or more of the millisecond(s) that this date object must
     *                                                  have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasMillisecond(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns true if the current date has one or more of the specified microseconds
     *
     * @param array|string|int $values One or more of the microsecond(s) that this date object must
     *                                                  have
     * @param bool $strict [true] If true will execute strict datatype comparison. If false, will
     *                                                  compare loosely
     * @param DateTimeZone|string|null $timezone [null] If specified, will first convert to the specified timezone
     *
     * @return bool
     */
    public function hasMicroSecond(array|string|int $values, bool $strict = true, DateTimeZone|string|null $timezone = null): bool;

    /**
     * Returns a string representation of how long ago the specified date was, from now or from the specified date
     *
     * @param PhoDate|PhoDateTimeInterface|string|int|null $date
     * @param bool $reverse
     * @param bool $microseconds
     *
     * @return string
     */
    public function getAge(PhoDate|PhoDateTimeInterface|string|int|null $date = null, bool $reverse = false, bool $microseconds = false): string;

    /**
     * Returns the day number where a week should start
     *
     * Return value table:
     *
     * 1 => Sunday
     * 2 => Monday
     * 3 => Tuesday
     * 4 => Wednesday
     * 5 => Thursday
     * 6 => Friday
     * 7 => Saturday
     *
     * @return int
     */
    public static function getWeekBegin(): int;

    /**
     * Returns the PHP date character to use
     *
     * @return string
     */
    public static function getPhpWeekCode(): string;

    /**
     * Returns true if this date is the first day of a period (the 1st or 16th of a month)
     *
     * @return bool
     */
    public function isWeekBegin(): bool;

    /**
     * Returns true if this date is the last day of a period (the 15yh or 16th of a month)
     *
     * @return bool
     */
    public function isWeekEnd(): bool;

    /**
     * Returns true if this date is the last day of a period (the 15yh or 16th of a month)
     *
     * @return bool
     */
    public function isPeriodEnd(): bool;

    /**
     * Returns the name of the day when the week starts
     *
     * Return values:
     *
     * sunday
     * monday
     *
     * @return string
     */
    public static function getWeekBeginDayName(): string;

    /**
     * Returns the 3 character code of the day when the week starts
     *
     * Return values:
     *
     * sun
     * mon
     *
     * @return string
     */
    public static function getWeekBeginDayCode(): string;

    /**
     * Returns the name of the day when the week stops
     *
     * Return values:
     *
     * sunday
     * monday
     *
     * @return string
     */
    public static function getWeekEndDayName(): string;

    /**
     * Returns the 3 character code of the day when the week stops
     *
     * Return values:
     *
     * sun
     * mon
     *
     * @return string
     */
    public static function getWeekEndDayCode(): string;

    /**
     * Returns the (user) configured day number where a week should stop
     *
     * Return value table:
     *
     * 1 => Sunday
     * 2 => Monday
     * 3 => Tuesday
     * 4 => Wednesday
     * 5 => Thursday
     * 6 => Friday
     * 7 => Saturday
     *
     * @return int
     */
    public static function getWeekEnd(): int;

    /**
     * Returns the number of days in the month for the current date
     *
     * @return int
     */
     #[ExpectedValues(values: [28, 29, 30, 31,])]  public function getDaysInMonth(): int;

    /**
     * Returns true if this date is the first day of a month
     *
     * @return bool
     */
    public function isMonthBegin(): bool;

    /**
     * Returns true if this date is the last day of a period (the 15yh or 16th of a month)
     *
     * @return bool
     */
    public function isMonthEnd(): bool;

    /**
     * Returns true if this date is the first day of a quarter (3 months)
     *
     * @return bool
     */
    public function isQuarterBegin(): bool;

    /**
     * Returns true if this date is the last day of a quarter (3 months)
     *
     * @return bool
     */
    public function isQuarterEnd(): bool;

    /**
     * Returns true if this date is the first day of a semester (6 months)
     *
     * @return bool
     */
    public function isSemesterBegin(): bool;

    /**
     * Returns true if this date is the last day of a semester (6 months)
     *
     * @return bool
     */
    public function isSemesterEnd(): bool;

    /**
     * Returns true if this date is the first day of a year
     *
     * @return bool
     */
    public function isYearBegin(): bool;

    /**
     * Returns true if this date is the last day of a year
     *
     * @return bool
     */
    public function isYearEnd(): bool;

    /**
     * Returns true if this date is on a weekend (Saturday or Sunday)
     *
     * @return bool
     */
    public function isInWeekend(): bool;

    /**
     * Returns an array containing all months on which a quarter can begin
     *
     * Returns [1, 4, 7, 10]
     *
     * @return array
     */
    public static function getQuarterBeginMonths(): array;

    /**
     * Returns an array containing all months on which a quarter can begin
     *
     * Returns [3, 6, 9, 12]
     *
     * @return array
     */
    public static function getQuarterEndMonths(): array;

    /**
     * Updated this date to be the previous beginning of a quarter
     *
     * If the previous quarter is in the previous year, the year will automatically be decreased by 1 as well
     *
     * @return static
     */
    public function makePreviousQuarterBegin(): static;

    /**
     * Updated this date to be the next beginning of a quarter
     *
     * If the next quarter is in the next year, the year will automatically be incremented by 1 as well
     *
     * @return static
     */
    public function makeNextQuarterBegin(): static;

    /**
     * Updated this date to be the previous end of a quarter
     *
     * If the previous quarter is in the previous year, the year will automatically be decreased by 1 as well
     *
     * @return static
     */
    public function makePreviousQuarterEnd(): static;

    /**
     * Updated this date to be the next end of a quarter
     *
     * If the next quarter is in the next year, the year will automatically be incremented by 1 as well
     *
     * @return static
     */
    public function makeNextQuarterEnd(): static;

    /**
     * Returns an array containing all months on which a semester can begin
     *
     * Returns [1, 7]
     *
     * @return array
     */
    public static function getSemesterBeginMonths(): array;

    /**
     * Returns an array containing all months on which a semester can begin
     *
     * Returns [6, 12]
     *
     * @return array
     */
    public static function getSemesterEndMonths(): array;

    /**
     * Updated this date to be the previous beginning of a semester
     *
     * If the previous semester is in the previous year, the year will automatically be decreased by 1 as well
     *
     * @return static
     */
    public function makePreviousSemesterBegin(): static;

    /**
     * Updated this date to be the next beginning of a semester
     *
     * If the next semester is in the next year, the year will automatically be incremented by 1 as well
     *
     * @return static
     */
    public function makeNextSemesterBegin(): static;

    /**
     * Updated this date to be the previous end of a semester
     *
     * If the previous semester is in the previous year, the year will automatically be decreased by 1 as well
     *
     * @return static
     */
    public function makePreviousSemesterEnd(): static;

    /**
     * Updated this date to be the next end of a semester
     *
     * If the next semester is in the next year, the year will automatically be incremented by 1 as well
     *
     * @return static
     */
    public function makeNextSemesterEnd(): static;

    /**
     * Returns an array containing all months on which a year can begin
     *
     * Returns [1]
     *
     * @return array
     */
    public static function getYearBeginMonths(): array;

    /**
     * Returns an array containing all months on which a year can begin
     *
     * Returns [12]
     *
     * @return array
     */
    public static function getYearEndMonths(): array;

    /**
     * Updated this date to be the previous beginning of a year
     *
     * If the previous year is in the previous year, the year will automatically be decreased by 1 as well
     *
     * @return static
     */
    public function makePreviousYearBegin(): static;

    /**
     * Updated this date to be the next beginning of a year
     *
     * If the next year is in the next year, the year will automatically be incremented by 1 as well
     *
     * @return static
     */
    public function makeNextYearBegin(): static;

    /**
     * Updated this date to be the previous end of a year
     *
     * If the previous year is in the previous year, the year will automatically be decreased by 1 as well
     *
     * @return static
     */
    public function makePreviousYearEnd(): static;

    /**
     * Updated this date to be the next end of a year
     *
     * If the next year is in the next year, the year will automatically be incremented by 1 as well
     *
     * @return static
     */
    public function makeNextYearEnd(): static;

    /**
     * Returns a PHP DateTimeInterface class object
     *
     * @return DateTimeInterface
     */
    public function getDateTimeObject(): DateTimeInterface;
}
