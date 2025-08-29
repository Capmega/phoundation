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

use DateInterval;
use DateMalformedStringException;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Date\Enums\EnumDateFormat;
use Phoundation\Date\Enums\EnumDateTimeSegment;
use Phoundation\Date\Exception\DateIntervalException;
use Phoundation\Date\Exception\DateTimeException;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\Interfaces\PhoDateTimeZoneInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


class PhoDateTime extends DateTime implements Stringable, Interfaces\PhoDateTimeInterface
{
    /**
     * Returns a new DateTime object
     *
     * @param PhoDateTimeInterface|string|float|int|null $datetime
     * @param DateTimeZone|string|null                   $timezone
     */
    public function __construct(PhoDateTimeInterface|string|float|int|null $datetime = 'now', DateTimeZone|string|null $timezone = null)
    {
        // Ensure we have NULL or timezone object for parent constructor
        $timezone = get_null($timezone);
        $datetime = $datetime ?? 'now';

        if (is_string($timezone)) {
            $timezone = new PhoDateTimeZone(trim($timezone));
        }

        if (is_numeric($datetime)) {
            $datetime = PhoDateTime::new('now', $timezone)->setTimestamp((int) $datetime)->format('Y-m-d H:i:s.u');
        }

        // Return Phoundation DateTime object for whatever given $datetime
        try {
            if (is_object($datetime)) {
                // Return a new DateTime object with the specified date in the specified timezone
                parent::__construct($datetime->format('Y-m-d H:i:s.u'), $timezone ?? $datetime->getTimezone());

            } else {
                $normalized = PhoDateTimeFormats::normalizeDate($datetime, '-');
                parent::__construct($normalized, $timezone);
            }

        } catch (Throwable $e) {
            throw new DateTimeException(tr('Failed to create DateTime object for given datetime ":datetime" / timezone ":timezone" because ":e"', [
                ':datetime' => $datetime,
                ':timezone' => $timezone,
                ':e'        => $e->getMessage(),
            ]), $e);
        }
    }


    /**
     * Returns a new DateTime object
     *
     * @param PhoDateTimeInterface|string|float|int|null $datetime
     * @param DateTimeZone|string|null                   $timezone
     *
     * @return static
     */
    public static function new(PhoDateTimeInterface|string|float|int|null $datetime = 'now', DateTimeZone|string|null $timezone = null): static
    {
        return new static($datetime, $timezone);
    }


    /**
     * Returns a new DateTime object, or if the specified datetime is empty, NULL
     *
     * @param PhoDateTimeInterface|string|float|int|null $datetime
     * @param DateTimeZone|string|null                   $timezone
     *
     * @return PhoDateTime|null
     */
    public static function newNull(PhoDateTimeInterface|string|float|int|null $datetime = 'now', DateTimeZone|string|null $timezone = null): ?static
    {
        if (empty($datetime)) {
            return null;
        }

        return new static($datetime, $timezone);
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSource();
    }


    /**
     * Returns a random PhoDateTime between $after and $before
     *
     * @param PhoDateTimeInterface|string|null $after
     * @param PhoDateTimeInterface|string|null $before
     * @param DateTimeZone|string|null         $timezone
     *
     * @return static
     */
    public static function newRandom(PhoDateTimeInterface|string|null $after = '-1 day', PhoDateTimeInterface|string|null $before = 'now', DateTimeZone|string|null $timezone = null): static
    {
        $after  = PhoDateTime::new($after);
        $before = PhoDateTime::new($before);

        if ($before < $after) {
            throw new OutOfBoundsException(tr('The "before" date ":before" must be AFTER the date ":after"', [
                ':before' => $before,
                ':after'  => $after,
            ]));
        }

        return PhoDateTime::new(Numbers::getRandomInt($after->getTimestamp(), $before->getTimestamp()), $timezone);
    }


    /**
     * Returns a new DateTime object
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newToday(DateTimeZone|string|null $timezone = null): static
    {
        return new static('today', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns the source of this PhoDateTime object
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->format('Y-m-d H:i:s');
    }


    /**
     * Returns the source of this PhoDateTime object
     *
     * @param PhoDateTimeInterface|string|float|int|null $datetime
     *
     * @return static
     */
    public function setSource(PhoDateTimeInterface|string|float|int|null $datetime): static
    {
        $datetime = PhoDateTime::new($datetime);

        return $this->setDate($datetime->getYear(), $datetime->getMonth(), $datetime->getDay())
                    ->setTime($datetime->getHour(), $datetime->getMinute(), $datetime->getSecond(), $datetime->getMicroSecond());
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function increaseDay(): static
    {
        return $this->setSource($this->modify('+1 day'));
    }


    /**
     * Increase this PhoDateTime object by a specified number of days
     *
     * @param int $days
     *
     * @return static
     */
    public function increaseByDays(int $days = 1): static
    {
        return $this->setSource($this->modify('+' . $days . ' day'));
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @return static
     */
    public function decreaseDay(): static
    {
        return $this->setSource($this->modify('-1 day'));
    }


    /**
     * Decrease this PhoDateTime object by a specified number of days
     *
     * @param int $days
     *
     * @return static
     */
    public function decreaseByDays(int $days = 1): static
    {
        return $this->setSource($this->modify('-' . $days . ' day'));
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
        if ($days <= 0) {
            if ($days < 0) {
                throw new OutOfBoundsException(tr('Invalid days value ":days" specified, must be 1 or higher', [
                    ':days' => $days,
                ]));
            }

            return $this;
        }

        return $this->setSource($this->modify('+' . $days . ' day'));
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
        if ($days <= 0) {
            if ($days < 0) {
                throw new OutOfBoundsException(tr('Invalid days value ":days" specified, must be 1 or higher', [
                    ':days' => $days,
                ]));
            }

            return $this;
        }

        return $this->setSource($this->modify('-' . $days . ' day'));
    }


    /**
     * Returns a new DateTime object for tomorrow
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newTomorrow(DateTimeZone|string|null $timezone = null): static
    {
        return new static('tomorrow', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for yesterday
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newYesterday(DateTimeZone|string|null $timezone = null): static
    {
        return new static('yesterday', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the first day of this week
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newFirstDayOfWeek(DateTimeZone|string|null $timezone = null): static
    {
        return new static(config()->getString('datetime.week.start', 'monday', true) . ' this week', PhoDateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object for the last day of this week
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public static function newLastDayOfWeek(DateTimeZone|string|null $timezone = null): static
    {
        return new static(config()->getString('datetime.week.stop', 'sunday', true) . ' this week', PhoDateTimeZone::new($timezone));
    }


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
     * L    Whether it's a leap year    1 if it is a leap year, 0 otherwise.
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
     * a    Lowercase Ante meridiem and Post meridiem    am or pm
     * A    Uppercase Ante meridiem and Post meridiem    AM or PM
     * B    Swatch Internet time    000 through 999
     * g    12-hour format of an hour without leading zeros    1 through 12
     * G    24-hour format of an hour without leading zeros    0 through 23
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
     * @param bool                       $compact
     *
     * @return string
     */
    public function format(EnumDateFormat|string|null $format = null, bool $compact = false): string
    {
        return parent::format(static::parseFormat($format, $compact));
    }


    /**
     * Applies specific format strings
     *
     * @param EnumDateFormat|string|null $format
     * @param bool                       $compact
     *
     * @return string
     * @todo Currently the human_* formats come from configuration, maybe this is better coming from locale?
     *
     */
    protected static function parseFormat(EnumDateFormat|string|null $format = null, bool $compact = false): string
    {
        $format = match ($format) {
            EnumDateFormat::user_time       => Session::getLocaleObject()->getTimeFormatPhp(),
            EnumDateFormat::user_date       => Session::getLocaleObject()->getDateFormatPhp(),
            EnumDateFormat::user_datetime   => Session::getLocaleObject()->getDateTimeFormatPhp(),
            EnumDateFormat::human_time      => config()->getString('locale.dates.formats.human.time'    , PhoDateTimeFormats::getDefaultTimeFormatPhp(), true),
            EnumDateFormat::human_date      => config()->getString('locale.dates.formats.human.date'    , PhoDateTimeFormats::getDefaultDateFormatPhp(), true),
            EnumDateFormat::human_datetime  => config()->getString('locale.dates.formats.human.datetime', PhoDateTimeFormats::getDefaultDateFormatPhp(), true),
            EnumDateFormat::iso_date,
            EnumDateFormat::system_date,
            EnumDateFormat::mysql_date      => 'Y-m-d',
            EnumDateFormat::iso_date_time,
            EnumDateFormat::mysql_datetime  => 'Y-m-d>>TIMESEPARATOR<<H:i:s',
            EnumDateFormat::file            => 'ymd-His',
            default                         => $format,
        };

        if ($compact) {
            return str_replace('>>TIMESEPARATOR<<', ' ', str_replace(' ', '', $format));
        }

        return $format;
    }


    /**
     * Will return the specified timezone, or if that is null, the timezone from the specified datetime.
     *
     * If the specified datetime is a string (and as such, contains no timezone information) NULL will be returned
     * instead
     *
     * @param PhoDateTimeInterface|string|null $datetime
     * @param DateTimeZone|string|null         $timezone
     *
     * @return PhoDateTimeZoneInterface|null
     */
    protected static function selectTimezone(PhoDateTimeInterface|string|null $datetime = 'now', DateTimeZone|string|null $timezone = null): ?PhoDateTimeZoneInterface
    {
        if ($datetime instanceof PhoDateTimeInterface) {
            $timezone = new PhoDateTimeZone($timezone ?? $datetime->getTimezone());
        }

        return new PhoDateTimeZone($timezone);
   }


    /**
     * Returns this date time as a human-readable date string
     *
     * @return string
     */
    public function getHumanReadableDate(): string
    {
        return $this->format(EnumDateFormat::user_date);
    }


    /**
     * Returns this date time as a human-readable date-time string
     *
     * @return string
     */
    public function getHumanReadableDateTime(): string
    {
        return $this->format(EnumDateFormat::user_datetime);
    }


    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getNextDay(DateTimeZone|string|null $timezone = null): static
    {
        return PhoDateTime::new($this, static::selectTimezone($this, $timezone))
                          ->increaseDay();
    }


    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getPreviousDay(DateTimeZone|string|null $timezone = null): static
    {
        return PhoDateTime::new($this, static::selectTimezone($this, $timezone))
                          ->decreaseDay();
    }


    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getBeginningOfDay(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-d 00:00:00'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the end of the day for the specified date
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getEndOfDay(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-d 23:59:59.999999'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the first day of this year
     *
     * @param DateTimeZone|string|null  $timezone
     *
     * @return static
     */
    public function getFirstDayOfYear(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-01-01'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the last day of this year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastDayOfYear(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-12-31'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the first day of this month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstDayOfMonth(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-01'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the last day of this month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastDayOfMonth(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-t'),
            static::selectTimezone($this, $timezone)
        );
    }


    /**
     * Returns a new DateTime object for the first
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getFirstPeriodStart(DateTimeZone|string|null $timezone = null): static
    {
        return $this->getFirstDayOfMonth($timezone);
    }


    /**
     * Returns a new DateTime object for
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function getLastPeriodStart(DateTimeZone|string|null $timezone = null): static
    {
        return new static(
            $this->format('Y-m-16'),
            PhoDateTimeZone::new($timezone)
        );
    }


    /**
     * Adds a number of days, months, years, hours, minutes and seconds to a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.add.php
     *
     * @param DateInterval $interval
     * @param bool         $return_new
     *
     * @return static
     */
    public function add(DateInterval $interval, bool $return_new = true): static
    {
        if ($return_new) {
            $return = clone $this;
            return $return->add($interval, false);
        }

        return static::new(parent::add($interval));
    }


    /**
     * Subtracts a number of days, months, years, hours, minutes and seconds from a DateTime object
     *
     * @link https://secure.php.net/manual/en/datetime.sub.php
     *
     * @param DateInterval $interval
     * @param bool         $return_new
     *
     * @return static
     */
    public function sub(DateInterval $interval, bool $return_new = true): static
    {
        if ($return_new) {
            $return = clone $this;
            return $return->sub($interval, false);
        }

        return static::new(parent::sub($interval));
    }


    /**
     * Alter the timestamp of a DateTime object by incrementing or decrementing in a format accepted by strtotime().
     *
     * @link https://secure.php.net/manual/en/datetime.modify.php
     *
     * @param string $modifier
     * @param bool   $return_new
     *
     * @return static
     * @throws DateMalformedStringException
     */
    public function modify(#[LanguageLevelTypeAware(['8.0' => 'string'], default: '')] $modifier, bool $return_new = true): static
    {
        if ($return_new) {
            $return = clone $this;
            return $return->modify($modifier, false);
        }

        return static::new(parent::modify($modifier));
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
     * @return PhoDateInterval
     * @throws DateIntervalException
     */
    public function diff(DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): PhoDateInterval
    {
        // DateInterval doesn't calculate milliseconds / microseconds, do that manually
        $diff    = new PhoDateInterval(parent::diff($targetObject, $absolute), $roundup);
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
            return PhoDateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) previous month
        $start = $datetime->sub(PhoDateInterval::createFromDateString('1 month'));

        return PhoDateTime::new($start->format('Y-m-16 00:00:00'), $datetime->getTimezone());
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
            $start = $datetime->add(PhoDateInterval::createFromDateString('1 month'));

            return PhoDateTime::new($start->format('Y-m-1 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return PhoDateTime::new($datetime->format('Y-m-16 00:00:00'), $datetime->getTimezone());
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
            return PhoDateTime::new($datetime->format('Y-m-16 00:00:00'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return PhoDateTime::new($datetime->format('Y-m-1 00:00:00'), $datetime->getTimezone());
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
            return PhoDateTime::new($datetime->format('Y-m-t 23:59:59.999999'), $datetime->getTimezone());
        }

        // 16 - 3(0|1) this month
        return PhoDateTime::new($datetime->format('Y-m-15 23:59:59.999999'), $datetime->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getMonthStart(): static
    {
        return PhoDateTime::new($this->format('Y-m-1 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getMonthStop(): static
    {
        return PhoDateTime::new($this->format('Y-m-t 23:59:59.999999'), $this->getTimezone());
    }


    /**
     * Returns a new DateTime object for the first day of the current month
     *
     * @return static
     */
    public function getDayStart(): static
    {
        return PhoDateTime::new($this->format('Y-m-d 00:00:00'), $this->getTimezone());
    }


    /**
     * Returns the stop date for the month in which this date is
     *
     * @return static
     */
    public function getDayStop(): static
    {
        return PhoDateTime::new($this->format('Y-m-d 23:59:59.999999'), $this->getTimezone());
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
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isToday(DateTimeZone|string|null $timezone = null): bool
    {
        $today = static::new('today', PhoDateTimeZone::new($timezone ?? $this->getTimezone()))
                       ->format('y-m-d');

        return $this->format('y-m-d') == $today;
    }


    /**
     * Returns true if the current date is tomorrow
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isTomorrow(DateTimeZone|string|null $timezone = null): bool
    {
        $tomorrow = static::new('tomorrow', PhoDateTimeZone::new($timezone ?? $this->getTimezone()))
                          ->format('y-m-d');

        return $this->format('y-m-d') == $tomorrow;
    }


    /**
     * Returns true if the current date is yesterday
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return bool
     */
    public function isYesterday(DateTimeZone|string|null $timezone = null): bool
    {
        $yesterday = static::new('yesterday', PhoDateTimeZone::new($timezone ?? $this->getTimezone()))
                           ->format('y-m-d');

        return $this->format('y-m-d') == $yesterday;
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @param DateTimeZone|PhoDateTimeZone|string $timezone
     *
     * @return static
     */
    public function setTimezone(DateTimeZone|PhoDateTimeZone|string $timezone): static
    {
        if (!$timezone) {
            throw new OutOfBoundsException(tr('Cannot set timezone, no timezone specified'));
        }

        return parent::setTimezone(PhoDateTimeZone::new($timezone)->getPhpDateTimeZone());
    }


    /**
     * Round the current date time object contents to the specified segment
     *
     * @param EnumDateTimeSegment $segment
     *
     * @return static
     */
    public function round(EnumDateTimeSegment $segment): static
    {
        $date = $this->format('Y m d H i s v u');
        $date = explode(' ', $date);

        switch ($segment) {
            case EnumDateTimeSegment::millennium:
                // no break

            case EnumDateTimeSegment::decennium:
                // no break

            case EnumDateTimeSegment::century:
                // no break

            case EnumDateTimeSegment::week:
                // no break

            case EnumDateTimeSegment::microsecond:
                throw new OutOfBoundsException(tr('Cannot round date to requested segment ":segment"', [
                    ':segment' => $segment,
                ]));

            case EnumDateTimeSegment::year:
                $date[1] = 0;
                // no break

            case EnumDateTimeSegment::month:
                $date[2] = 0;
                // no break

            case EnumDateTimeSegment::day:
                $date[3] = 0;
                // no break

            case EnumDateTimeSegment::hour:
                $date[4] = 0;
                // no break

            case EnumDateTimeSegment::minute:
                $date[5] = 0;
                // no break

            case EnumDateTimeSegment::second:
                $date[6] = 0;
                // no break

            case EnumDateTimeSegment::millisecond:
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
     * @param DateTimeZone|PhoDateTimeZone|string|null $timezone
     *
     * @return static
     */
    public function makeCurrentTime(DateTimeZone|PhoDateTimeZone|string|null $timezone = null): static
    {
        $time = PhoDateTime::new('now', $timezone ?? $this->getTimezone())->format('H i s u');
        $time = explode(' ', $time);

        $this->setTime((int) $time[0], (int) $time[1], (int) $time[2], (int) $time[3]);

        return $this;
    }


    /**
     * Returns the current year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getYear(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('Y');
    }


    /**
     * Returns the current month of the year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMonth(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('m');
    }


    /**
     * Returns the current week of the year
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getWeek(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('W');
    }


    /**
     * Returns the current day of the month
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getDay(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('d');
    }


    /**
     * Returns the current hour of the day
     *
     * @note will return the hour in 24 hours format
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getHour(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('H');
    }


    /**
     * Returns the current minute of the hour
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMinute(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('i');
    }


    /**
     * Returns the current second of the minute
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getSecond(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('s');
    }


    /**
     * Returns the current millisecond of the second
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMillisecond(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('v');
    }


    /**
     * Returns the current microsecond of the second
     *
     * @param DateTimeZone|string|null $timezone
     *
     * @return int
     */
    public function getMicroSecond(DateTimeZone|string|null $timezone = null): int
    {
        return (int) PhoDateTime::new($this, $timezone)->format('u');
    }


    /**
     * Adds separators into a date-time string
     *
     * @param string|null $source
     * @param string      $date_separator
     * @param string      $time_separator
     * @param string      $date_time_separator
     *
     * @return string|null
     */
    public static function addDateSeparators(?string $source, string $date_separator = '-', string $time_separator = ':', string $date_time_separator = ' '): ?string
    {
        if (!$source) {
            return null;
        }

        switch (strlen($source)) {
            case 8:
                $return = substr($source, 0, 4) . $date_separator .
                          substr($source, 4, 2) . $date_separator .
                          substr($source, 6, 2);
                break;

            case 12:
                $return = substr($source, 0, 4) . $date_separator .
                          substr($source, 4, 2) . $date_separator .
                          substr($source, 6, 2) . $date_separator .
                          substr($source, 8, 2) . $time_separator .
                          substr($source, 10, 2) . $time_separator
                          . '00';
                break;

            case 14:
                $return = substr($source, 0, 4) . $date_separator .
                          substr($source, 4, 2) . $date_separator .
                          substr($source, 6, 2) . $date_separator .
                          substr($source, 8, 2) . $time_separator .
                          substr($source, 10, 2) . $time_separator .
                          substr($source, 12, 2);

        }

        return $return;
    }


    /**
     * Returns a string representation of how long ago the specified date was, from now
     *
     * @param PhoDate|PhoDateTimeInterface|string|int|null $date
     * @param bool                                $microseconds
     *
     * @return string
     */
    public function getAge(PhoDate|PhoDateTimeInterface|string|int|null $date = null, bool $microseconds = false): string
    {
        if (!is_object($date)) {
            if (is_integer($date)) {
                $timestamp = $date;
                $date      = new PhoDateTime();
                $date->setTimestamp($timestamp);

            } else {
                $date = new PhoDateTime($date);
            }
        }

        $diff = $this->diff($date);

        if ($diff->y) {
            return Strings::plural($diff->y, tr(':count year', [':count' => $diff->y]), tr(':count years', [
                ':count' => $diff->y
            ]));
        }

        if ($diff->m) {
            return Strings::plural($diff->m, tr(':count month', [':count' => $diff->m]), tr(':count months', [
                ':count' => $diff->m
            ]));
        }

        if ($diff->d) {
            return Strings::plural($diff->d, tr(':count day', [':count' => $diff->d]), tr(':count days', [
                ':count' => $diff->d
            ]));
        }

        if ($diff->h) {
            return Strings::plural($diff->h, tr(':count hour', [':count' => $diff->h]), tr(':count hours', [
                ':count' => $diff->h
            ]));
        }

        if ($diff->i) {
            return Strings::plural($diff->i, tr(':count minute', [':count' => $diff->i]), tr(':count minutes', [
                ':count' => $diff->i
            ]));
        }

        if ($diff->s) {
            return Strings::plural($diff->s, tr(':count second', [':count' => $diff->s]), tr(':count seconds', [
                ':count' => $diff->s
            ]));
        }

        if ($microseconds) {
            if (isset($diff->u) and $diff->u) {
                return Strings::plural($diff->s, tr(':count second', [':count' => $diff->s]), tr(':count microseconds', [
                    ':count' => $diff->s
                ]));
            }
        }

        return tr('Right now');
    }
}
