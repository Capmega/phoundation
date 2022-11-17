<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry;



/**
 * Class State
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class State
{
    use DataEntry;



    /**
     * Returns the general timezone for this state
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('timezones_id'));
    }



    /**
     * Returns the continent for this state
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('continents_id'));
    }



    /**
     * Returns the country for this state
     *
     * @return Country
     */
    public function getCountry(): Country
    {
        return new Country($this->getDataValue('countries_id'));
    }



    /**
     * Load the State data from database
     *
     * @param int $identifier
     * @return void
     */
    protected function load(int $identifier): void
    {

    }



    /**
     * Save the State data to database
     *
     * @return void
     */
    protected function save(): void
    {

    }
}