<?php

/**
 * Class Locale
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

namespace Phoundation\Core\Locale;

use Locale;
use Phoundation\Date\PhoDateFormats;

class PhoLocale extends Locale
{
    /**
     *
     *
     * @return string
     */
    public static function getLocale(): string
    {
        return 'en-CA';
    }


    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public static function getPhpDateTimeFormat(): string
    {
        return 'Y/m/d H:i:s';
    }


    /**
     * Returns the default date/time format for this process used in PHP
     *
     * @return string
     */
    public static function getPhpDateFormat(): string
    {
        return 'Y/m/d';
    }


    /**
     * Returns the default date/time format for this process used in JavaScript
     *
     * @return string
     */
    public static function getJsDateTimeFormat(): string
    {
        return PhoDateFormats::convertPhpToJs(static::getPhpDateTimeFormat());
    }
}
