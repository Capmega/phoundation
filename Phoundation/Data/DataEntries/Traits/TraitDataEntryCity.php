<?php

/**
 * Trait TraitDataEntryCity
 *
 * This trait contains methods for DataEntry objects that require a city
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Geo\Cities\City;
use Phoundation\Geo\Cities\Interfaces\CityInterface;



trait TraitDataEntryCity
{
    /**
     * Setup virtual configuration for Cities
     *
     * @return static
     */
    protected function addVirtualConfigurationCities(): static
    {
        return $this->addVirtualConfiguration('cities', City::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the cities_id column
     *
     * @return int|null
     */
    public function getCitiesId(): ?int
    {
        return $this->getVirtualData('cities', 'int', 'id');
    }


    /**
     * Sets the cities_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setCitiesId(?int $id): static
    {
        return $this->setVirtualData('cities', $id, 'id');
    }


    /**
     * Returns the cities_code column
     *
     * @return string|null
     */
    public function getCitiesCode(): ?string
    {
        return $this->getVirtualData('cities', 'string', 'code');
    }


    /**
     * Sets the cities_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setCitiesCode(?string $code): static
    {
        return $this->setVirtualData('cities', $code, 'code');
    }


    /**
     * Returns the cities_name column
     *
     * @return string|null
     */
    public function getCitiesName(): ?string
    {
        return $this->getVirtualData('cities', 'string', 'name');
    }


    /**
     * Sets the cities_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setCitiesName(?string $name): static
    {
        return $this->setVirtualData('cities', $name, 'name');
    }


    /**
     * Returns the City Object
     *
     * @return CityInterface|null
     */
    public function getCityObject(): ?CityInterface
    {
        return $this->getVirtualObject('cities');
    }


    /**
     * Returns the cities_id for this user
     *
     * @param CityInterface|null $_object
     *
     * @return static
     */
    public function setCityObject(?CityInterface $_object): static
    {
        return $this->setVirtualObject('cities', $_object);
    }
}
