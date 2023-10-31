<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Cities\City;


/**
 * Trait DataEntryCity
 *
 * This trait contains methods for DataEntry objects that require GEO city data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCity
{
    /**
     * Returns the cities_id for this user
     *
     * @return int|null
     */
    public function getCitiesId(): ?int
    {
        return $this->getSourceFieldValue('int', 'cities_id');
    }


    /**
     * Sets the cities_id for this user
     *
     * @param int|null $cities_id
     * @return static
     */
    public function setCitiesId(?int $cities_id): static
    {
        return $this->setSourceValue('cities_id', $cities_id);
    }


    /**
     * Returns the cities_id for this user
     *
     * @return City|null
     */
    public function getCity(): ?City
    {
        $cities_id = $this->getSourceFieldValue('int', 'cities_id');

        if ($cities_id) {
            return new City($cities_id);
        }

        return null;
    }


    /**
     * Returns the cities_name for this user
     *
     * @return string|null
     */
    public function getCitiesName(): ?string
    {
        return $this->getSourceFieldValue('string', 'cities_name') ?? City::new($this->getCitiesId(), 'id')?->getName();
    }


    /**
     * Sets the cities_name for this user
     *
     * @param string|null $cities_name
     * @return static
     */
    public function setCitiesName(?string $cities_name): static
    {
        return $this->setSourceValue('cities_name', $cities_name);
    }
}
