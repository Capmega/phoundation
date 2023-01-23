<?php

namespace Phoundation\Geo\Counties;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\Timezones\Timezone;


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
class County extends DataEntry
{
    use DataEntryNameDescription;



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
     * Set the form keys for this DataEntry
     *
     * @return void
     */
    protected function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }
}