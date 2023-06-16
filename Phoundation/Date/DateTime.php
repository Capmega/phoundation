<?php

declare(strict_types=1);

namespace Phoundation\Date;

use DateTimeInterface;
use DateTimeZone;
use Exception;
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
class DateTime extends \DateTime implements DateTimeInterface, Stringable
{
    /**
     * Returns this DateTime object as a string in ISO 8601 format without switching timezone
     *
     * @return string
     */
    public function __toString() {
        return $this->format('Y-m-d H:i:s.v');
    }


    /**
     * Returns a new DateTime object
     *
     * @param Date|DateTime|string $datetime
     * @param DateTimeZone|string|null $timezone
     * @return static
     * @throws Exception
     */
    public static function new(Date|DateTime|string $datetime = 'now', DateTimeZone|string|null $timezone = null): static
    {
        if (is_object($datetime)) {
            return $datetime;
        }

        return new DateTime($datetime, \Phoundation\Date\DateTimeZone::new($timezone));
    }


    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @return $this
     */
    public function setTimezone(DateTimeZone|string|null $timezone = null): static
    {
        parent::setTimezone(\Phoundation\Date\DateTimeZone::new($timezone)->getPhpDateTimeZone());
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
}