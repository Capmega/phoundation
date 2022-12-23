<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataEntryNameDescription;



/**
 * Class Timezone
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Timezone extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Timezone class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        self::$entry_name = 'geo timezone';
        $this->table      = 'geo_timezones';

        parent::__construct($identifier);
    }



    /**
     * Load the Timezone data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {

    }



    /**
     * Save the Timezone data to database
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