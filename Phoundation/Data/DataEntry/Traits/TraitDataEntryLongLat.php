<?php

/**
 * Trait TraitDataEntryLongLat
 *
 * This trait contains methods for DataEntry objects that require longitude and latitude data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryLongLat
{
    /**
     * Returns the longitude for this entry
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->getTypesafe('float', 'longitude');
    }


    /**
     * Sets the longitude for this entry
     *
     * @param float|null $longitude
     *
     * @return static
     */
    public function setLongitude(float|null $longitude): static
    {
        return $this->set($longitude, 'longitude');
    }


    /**
     * Returns the latitude for this entry
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->getTypesafe('float', 'latitude');
    }


    /**
     * Sets the latitude for this entry
     *
     * @param float|null $latitude
     *
     * @return static
     */
    public function setLatitude(float|null $latitude): static
    {
        return $this->set($latitude, 'latitude');
    }
}
