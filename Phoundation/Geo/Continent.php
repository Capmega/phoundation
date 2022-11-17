<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry;
use Phoundation\Date\Time;


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
class Continent
{
    use DataEntry;



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
     * @param int $identifier
     * @return void
     */
    protected function load(int $identifier): void
    {

    }



    /**
     * Save the Continent data to database
     *
     * @return void
     */
    protected function save(): void
    {

    }
}