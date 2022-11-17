<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry;



/**
 * Class City
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class City
{
    use DataEntry;



    /**
     * Returns the general timezone for this city
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('timezones_id'));
    }



    /**
     * Returns the continent for this city
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('continents_id'));
    }



    /**
     * Returns the country for this city
     *
     * @return Country
     */
    public function getCountry(): Country
    {
        return new Country($this->getDataValue('countries_id'));
    }



    /**
     * Returns the state for this city
     *
     * @return State
     */
    public function getState(): State
    {
        return new State($this->getDataValue('states_id'));
    }



    /**
     * Returns the county for this city
     *
     * @return County
     */
    public function getCounty(): County
    {
        return new County($this->getDataValue('counties_id'));
    }



    /**
     * Load the City data from database
     *
     * @param int $identifier
     * @return void
     */
    protected function load(int $identifier): void
    {

    }



    /**
     * Save the City data to database
     *
     * @return void
     */
    protected function save(): void
    {

    }
}