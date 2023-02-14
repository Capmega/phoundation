<?php

namespace Phoundation\Geo\Timezones;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


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
        static::$entry_name  = 'geo timezone';
        $this->table         = 'geo_timezones';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
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