<?php

namespace Phoundation\Geo\Counties\Interfaces;

use Phoundation\Geo\Continents\Interfaces\ContinentInterface;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;
use Phoundation\Geo\States\Interfaces\StateInterface;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;

interface CountyInterface
{
    /**
     * Returns the general timezone for this county
     *
     * @return TimezoneInterface
     */
    public function getTimezone(): TimezoneInterface;

    /**
     * Returns the continent for this county
     *
     * @return ContinentInterface
     */
    public function getContinent(): ContinentInterface;

    /**
     * Returns the country for this county
     *
     * @return CountryInterface
     */
    public function getCountry(): CountryInterface;

    /**
     * Returns the state for this county
     *
     * @return StateInterface
     */
    public function getState(): StateInterface;
}