<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry\DataEntry;


/**
 * Class County
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class County
{
    use DataEntry;



    /**
     * Returns the general timezone for this county
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('timezones_id'));
    }



    /**
     * Returns the continent for this county
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('continents_id'));
    }



    /**
     * Returns the country for this county
     *
     * @return Country
     */
    public function getCountry(): Country
    {
        return new Country($this->getDataValue('countries_id'));
    }



    /**
     * Returns the state for this county
     *
     * @return State
     */
    public function getState(): State
    {
        return new State($this->getDataValue('states_id'));
    }



    /**
     * Load the County data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {

    }



    /**
     * Save the County data to database
     *
     * @return void
     */
    protected function save(): void
    {

    }
}