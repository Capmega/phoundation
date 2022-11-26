<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataEntryNameDescription;


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
        self::$entry_name = 'geo country';
        $this->table      = 'geo_countries';

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
     * Load the Country data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {

    }



    /**
     * Save the Country data to database
     *
     * @return static
     */
    public function save(): static
    {
        return $this;
    }



    /**
     * Set the keys for this DataEntry
     *
     * @return void
     */
    protected function setColumns(): void
    {
        // TODO: Implement setKeys() method.
    }
}