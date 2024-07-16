<?php

declare(strict_types=1);

namespace Phoundation\Date\Interfaces;

use DateTimeZone;

interface DateTimeZoneInterface
{
    /**
     * Returns a PHP DateTimeZone object from this Phoundation DateTimeZone object
     *
     * @return \DateTimeZone
     */
    public function getPhpDateTimeZone(): DateTimeZone;
}
