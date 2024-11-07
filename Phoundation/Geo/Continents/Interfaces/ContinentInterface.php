<?php

namespace Phoundation\Geo\Continents\Interfaces;

use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;


interface ContinentInterface
{
    /**
     * Returns the general timezone for this continent
     *
     * @return TimezoneInterface
     */
    public function getTimezone(): TimezoneInterface;
}