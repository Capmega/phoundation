<?php

namespace Phoundation\Date;

use Exception;

/**
 * Class DateTime
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
class DateTime extends \DateTime
{
    /**
     * Returns a new DateTime object
     *
     * @param $datetime
     * @param $timezone
     * @return DateTime
     * @throws Exception
     */
    public static function new(
        #[LanguageLevelTypeAware(['8.0' => 'string'], default: '')] $datetime = 'now',
        #[LanguageLevelTypeAware(['8.0' => 'DateTimeZone|null'], default: 'DateTimeZone')] $timezone = null
    ): DateTime
    {
        return new DateTime($datetime, $timezone);
    }


    /**
     * Returns this DateTime object as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->format();
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
            case 'mysql':
                $format = 'Y-m-d H:i:s';
                break;
        }

        return parent::format($format);
    }
}