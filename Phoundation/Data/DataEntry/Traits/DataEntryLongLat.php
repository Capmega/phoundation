<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Cities\Longitude;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;

/**
 * Trait DataEntryLongLat
 *
 * This trait contains methods for DataEntry objects that require longitude and latitude data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryLongLat
{
    /**
     * Returns the longitude for this entry
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->getDataValue('longitude');
    }


    /**
     * Sets the longitude for this entry
     *
     * @param float|null $longitude
     * @return static
     */
    public function setLongitude(float|null $longitude): static
    {
        return $this->setDataValue('longitude', $longitude);
    }
    
    
    
    /**
     * Returns the latitude for this entry
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->getDataValue('latitude');
    }


    /**
     * Sets the latitude for this entry
     *
     * @param float|null $latitude
     * @return static
     */
    public function setLatitude(float|null $latitude): static
    {
        return $this->setDataValue('latitude', $latitude);
    }
}