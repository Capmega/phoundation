<?php

namespace Phoundation\Geo;

use Phoundation\Data\DataEntry\DataEntry;


/**
 * Class Feature
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Feature
{
    use DataEntry;



    /**
     * Load the Feature data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {

    }



    /**
     * Save the Feature data to database
     *
     * @return void
     */
    protected function save(): void
    {

    }
}