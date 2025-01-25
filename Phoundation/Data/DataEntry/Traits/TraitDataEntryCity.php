<?php

/**
 * Trait DataEntryCity
 *
 * This trait contains methods for DataEntry objects that require GEO city data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Cities\City;
use Phoundation\Geo\Cities\Interfaces\CityInterface;


trait TraitDataEntryCity
{
    /**
     * City object cache
     *
     * @var CityInterface|null $o_city
     */
    protected ?CityInterface $o_city;


    /**
     * Returns the cities_id for this object
     *
     * @return int|null
     */
    public function getCitiesId(): ?int
    {
        return $this->getTypesafe('int', 'cities_id');
    }


    /**
     * Sets the cities_id for this object
     *
     * @param int|null $cities_id
     *
     * @return static
     */
    public function setCitiesId(?int $cities_id): static
    {
        $this->o_city = null;
        return $this->set($cities_id, 'cities_id');
    }


    /**
     * Returns the city for this object
     *
     * @return CityInterface|null
     */
    public function getCityObject(): ?CityInterface
    {
        if (empty($this->o_city)) {
            $this->o_city = City::new($this->getTypesafe('int', 'cities_id'))->loadOrNull();
        }

        return $this->o_city;
    }


    /**
     * Sets the city for this object
     *
     * @param CityInterface|null $o_city
     * @return TraitDataEntryCity
     */
    public function setCityObject(?CityInterface $o_city): static
    {
        $this->setCitiesId($o_city?->getId());

        $this->o_city = $o_city;
        return $this;
    }


    /**
     * Returns the cities_name for this object
     *
     * @return string|null
     */
    public function getCitiesName(): ?string
    {
        return $this->getCityObject()->getName();
    }


    /**
     * Returns the cities_name for this object
     *
     * @param string|null $cities_name
     *
     * @return static
     */
    public function setCitiesName(?string $cities_name): static
    {
        return $this->setCityObject(City::new(['name' => $cities_name])->loadOrNull());
    }
}
