<?php

namespace Phoundation\Geo\States;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Http\Html\Components\Input\Select;


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
class State extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * State class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'geo state';
        $this->table         = 'geo_states';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
    }



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
     * Returns an HTML <select> object with all cities available in this state
     *
     * @param string $name
     * @return Select
     */
    public function getHtmlCitiesSelect(string $name = 'cities_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `geo_cities` 
                                          WHERE `states_id` = :states_id AND `status` IS NULL ORDER BY `name`', [
                ':states_id' => $this->getId()
            ])
            ->setName($name)
            ->setNone(tr('Please select a city'))
            ->setEmpty(tr('No cities available'));
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