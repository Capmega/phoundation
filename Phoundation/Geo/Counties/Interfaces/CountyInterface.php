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
    public function getTimezoneObject(): TimezoneInterface;

    /**
     * Returns the continent for this county
     *
     * @return ContinentInterface
     */
    public function getContinentObject(): ContinentInterface;

    /**
     * Returns the country for this county
     *
     * @return CountryInterface
     */
    public function getCountryObject(): CountryInterface;

    /**
     * Returns the state for this county
     *
     * @return StateInterface
     */
    public function getStateObject(): StateInterface;
}