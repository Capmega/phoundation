<?php

declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Core\Strings;
use Phoundation\Utils\Json;
use Stringable;


/**
 * Class DateInterval
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class DateInterval extends \DateInterval implements Stringable
{
    /**
     * Number of years
     * @var int
     */
    public $y;

    /**
     * Number of months
     * @var int
     */
    public $m;

    /**
     * Number of days
     * @var int
     */
    public $d;

    /**
     * Number of hours
     * @var int
     */
    public $h;

    /**
     * Number of minutes
     * @var int
     */
    public $i;

    /**
     * Number of seconds
     * @var int
     */
    public $s;

    /**
     * Number of microseconds
     * @since 7.1.0
     * @var float
     */
    public $f;

    /**
     * Is 1 if the interval is inverted and 0 otherwise
     * @var int
     */
    public $invert;

    /**
     * Total number of days the interval spans. If this is unknown, days will be FALSE.
     * @var int|false
     */
    public $days;

    /**
     * @var string
     */
    public $from_string;

    /**
     * DateInterval constructor
     *
     * @param \DateInterval|DateInterval|array|string|int $date_interval
     * @param bool $round_up
     * @throws \Exception
     */
    public function __construct(\DateInterval|DateInterval|array|string|int $date_interval, bool $round_up = true)
    {
        if (is_string($date_interval)) {
            $date_interval = parent::__construct($date_interval);
        }

        if (is_int($date_interval)) {
            $date_interval = DateTime::new($date_interval . ' seconds')->diff(DateTime::new());
        }

        // Copy all properties
        foreach ($date_interval as $key => $value) {
            $this->$key = $value;
        }

        if ($round_up) {
            $this->roundUp();
        }
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString() {
        return Json::encode($this->__toArray());
    }


    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return array
     */
    public function __toArray(): array
    {
        return (array) $this;
    }


    /**
     * Returns a new DateTime object
     *
     * @param DateInterval|array|string|int $date_interval
     * @return static
     */
    public static function new(DateInterval|array|string|int $date_interval, bool $round_up = true): static
    {
        return new static($date_interval, $round_up);
    }


    /**
     * Returns the amount of years for this date interval
     *
     * @return int
     */
    public function getTotalYears(): int {
        return $this->y;
    }


    /**
     * Returns the amount of months for this date interval
     *
     * @return int
     */
    public function getTotalMonths(): int {
        return ($this->y * 12) + $this->m;
    }


    /**
     * Returns the amount of weeks for this date interval
     *
     * @return int
     */
    public function getTotalWeeks(): int {
        return ($this->y * 52) + $this->w;
    }


    /**
     * Returns the amount of days for this date interval
     *
     * @return int
     */
    public function getTotalDays(): int {
        return ($this->y * 365) + ($this->m * 30);
    }


    /**
     * Returns the amount of hours for this date interval
     *
     * @return int
     */
    public function getTotalHours(): int {
        return ($this->getTotalDays() * 24) + $this->h;
    }


    /**
     * Returns the amount of minutes for this date interval
     *
     * @return int
     */
    public function getTotalMinutes(): int {
        return ($this->getTotalHours() * 60) + $this->i;
    }


    /**
     * Returns the amount of seconds for this date interval
     *
     * @return int
     */
    public function getTotalSeconds(): int {
        return ($this->getTotalMinutes() * 60) + $this->s;
    }


    /**
     * Rounds up the microseconds to whole seconds
     *
     * @return $this
     */
    public function roundUp(): static
    {
        // PHP can make shitty rounding issues in the microseconds range, try and fix those
        if ($this->f < 0.5) {
            // Too small, ignore it
            $this->f = 0;

        } else {
            $this->f = 0;
            $this->s++;

            // Limit seconds to 60
            if ($this->s >= 60) {
                $this->s = 0;
                $this->i++;

                // Limit minutes to 60
                if ($this->i >= 60) {
                    $this->i = 0;
                    $this->h++;

                    // Limit hours to 24
                    if ($this->h >= 24) {
                        $this->h = 0;
                        $this->d++;

                        // Limit days to 30
                        if ($this->d >= 30) {
                            $this->d = 0;
                            $this->m++;

                            // Limit months to 12
                            if ($this->m >= 12) {
                                $this->m = 0;
                                $this->y++;
                            }
                        }
                    }
                }
            }
        }

        return $this;
    }


    /**
     * Returns this DateInterval data in human readable format
     *
     * @return string
     */
    public function getHumanReadable(bool $short = false): string
    {
        $return = [];

        if ($short) {
            if ($this->y) {
                $return[] = $this->y . 'Y';
            }

            if ($this->m) {
                $return[] = $this->m . 'M';
            }

            if ($this->d) {
                $return[] = $this->d . 'D';
            }

            if ($this->h) {
                $return[] = $this->h . 'h';
            }

            if ($this->i) {
                $return[] = $this->i . 'm';
            }

            if ($this->s) {
                $return[] = $this->s . 's';
            }

            if ($this->f) {
                $return[] = $this->f . 'ms';
            }

            $return = implode('', $return);

        } else {
            if ($this->y) {
                $return[] = $this->y . Strings::plural($this->y, ' year', ' years');
            }

            if ($this->m) {
                $return[] = $this->m . Strings::plural($this->m, ' month', ' months');
            }

            if ($this->d) {
                $return[] = $this->d . Strings::plural($this->d, ' day', ' days');
            }

            if ($this->h) {
                $return[] = $this->h . Strings::plural($this->h, ' hour', ' hours');
            }

            if ($this->i) {
                $return[] = $this->i . Strings::plural($this->i, ' minute', ' minutes');
            }

            if ($this->s) {
                $return[] = $this->s . Strings::plural($this->s, ' second', ' seconds');
            }

            if ($this->f) {
                $return[] = $this->f . Strings::plural($this->f, ' millisecond', ' milliseconds');
            }

            $return = implode(' ', $return);
        }

        return $return;
    }
}