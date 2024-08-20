<?php

/**
 * Class DateTimeFormats
 *
 * PHP / Javascript date time format handling
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Date
 */


declare(strict_types=1);

namespace Phoundation\Date;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Utils\Config;


class DateTimeFormats extends DateFormats
{
    /**
     * The default date formats
     *
     * @var array $defaults
     */
    protected static array $defaults = [
        'd-m-Y H:i:s',
        'Y-m-d H:i:s',
    ];


    /**
     * Returns the supported PHP date format strings
     *
     * @return IteratorInterface
     */
    public static function getSupportedPhp(): IteratorInterface
    {
        return Config::getIterator('locale.formats.datetime', static::$defaults);
    }
}
