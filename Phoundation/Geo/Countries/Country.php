<?php

namespace Phoundation\Geo\Countries;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Http\Html\Components\Input\Select;


/**
 * Class Country
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Country extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Country class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'geo country';
        $this->table         = 'geo_countries';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
    }



    /**
     * Returns the general timezone for this country
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('timezones_id'));
    }



    /**
     * Returns the continent for this country
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('continents_id'));
    }



    /**
     * Returns an HTML <select> object with all states available in this country
     *
     * @param string $name
     * @return Select
     */
    public function getHtmlStatesSelect(string $name = 'states_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `geo_states` 
                                          WHERE `countries_id` = :countries_id AND `status` IS NULL ORDER BY `name`', [
                ':countries_id' => $this->getId()
            ])
            ->setName($name)
            ->setNone(tr('Please select a state'))
            ->setEmpty(tr('No state available'));
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