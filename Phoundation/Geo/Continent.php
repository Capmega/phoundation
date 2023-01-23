<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryNameDescription;


/**
 * Class Continent
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Continent extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Continent class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        self::$entry_name = 'geo continent';
        $this->table      = 'geo_continents';

        parent::__construct($identifier);
    }



    /**
     * Returns the general timezone for this continent
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('timezones_id'));
    }



    /**
     * Load the Continent data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {

    }



    /**
     * Save the Continent data to database
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
    protected function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }
}