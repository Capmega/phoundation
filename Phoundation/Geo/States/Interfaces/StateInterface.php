<?php

namespace Phoundation\Geo\States\Interfaces;

use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Continents\Interfaces\ContinentInterface;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Html\Components\Input\InputSelect;

interface StateInterface
{
    /**
     * Returns the general timezone for this state
     *
     * @return TimezoneInterface
     */
    public function getTimezone(): TimezoneInterface;

    /**
     * Returns the continent for this state
     *
     * @return ContinentInterface
     */
    public function getContinent(): ContinentInterface;

    /**
     * Returns the country for this state
     *
     * @return CountryInterface
     */
    public function getCountry(): CountryInterface;

    /**
     * Returns an HTML <select> object with all cities available in this state
     *
     * @param string $name
     *
     * @return InputSelect
     */
    public function getHtmlCitiesSelect(string $name = 'cities_id'): InputSelect;
}