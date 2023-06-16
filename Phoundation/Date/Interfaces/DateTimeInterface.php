<?php

namespace Phoundation\Date\Interfaces;

use DateTimeZone;


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
interface DateTimeInterface extends \DateTimeInterface, \Stringable
{
    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @return $this
     */
    public function setTimezone(DateTimeZone|string|null $timezone = null): static;

    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * @param string|null $format
     * @return string
     */
    public function format(?string $format = null): string;
}