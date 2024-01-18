<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;

use Phoundation\Date\DateInterval;
use Phoundation\Date\DateTimeZone;


/**
 * interface DateTimeInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Date
 */
interface DateTimeInterface extends \DateTimeInterface
{
    /**
     * Returns the difference between two DateTime objects
     * @link https://secure.php.net/manual/en/datetime.diff.php
     *
     * @param \DateTimeInterface $targetObject
     * @param bool $absolute
     * @param bool $roundup
     * @return DateInterval
     */
    public function diff(\DateTimeInterface $targetObject, bool $absolute = false, bool $roundup = true): DateInterval;

    /**
     * Returns a new DateTime object with the specified timezone
     *
     * @return \DateTimeInterface
     */
    public function setTimezone(\DateTimeZone|DateTimeZone|string|null $timezone = null): static;

    /**
     * Wrapper around the PHP Datetime but with support for named formats, like "mysql"
     *
     * @param string|null $format
     * @return string
     */
    public function format(?string $format = null): string;
}
