<?php

namespace Phoundation\Geo\Features;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;


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
class Feature extends DataEntry
{
    use DataEntryNameDescription;



    /**
     * Features class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name  = 'geo feature';
        $this->table         = 'geo_features';
        $this->unique_column = 'seo_name';

        parent::__construct($identifier);
    }



    /**
     * @inheritDoc
     */
    protected function setKeys(): void
    {
        // TODO: Implement setKeys() method.
    }
}