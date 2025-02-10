<?php

namespace Phoundation\Geo\Cities\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Geo\Continents\Interfaces\ContinentInterface;
use Phoundation\Geo\Counties\Interfaces\CountyInterface;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;
use Phoundation\Geo\States\Interfaces\StateInterface;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;

interface CityInterface extends DataEntryInterface
{
    /**
     * Returns the general timezone for this city
     *
     * @return TimezoneInterface
     */
    public function getTimezone(): TimezoneInterface;

    /**
     * Returns the continent for this city
     *
     * @return ContinentInterface
     */
    public function getContinent(): ContinentInterface;

    /**
     * Returns the country for this city
     *
     * @return CountryInterface
     */
    public function getCountry(): CountryInterface;

    /**
     * Returns the state for this city
     *
     * @return StateInterface
     */
    public function getState(): StateInterface;

    /**
     * Returns the county for this city
     *
     * @return CountyInterface
     */
    public function getCounty(): CountyInterface;
}
