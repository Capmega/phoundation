<?php

/**
 * Trait TraitDataEntryCountry
 *
 * This trait contains methods for DataEntry objects that require a country
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;


trait TraitDataEntryCountry
{
    /**
     * Setup virtual configuration for Countries
     *
     * @return static
     */
    protected function addVirtualConfigurationCountries(): static
    {
        return $this->addVirtualConfiguration('countries', Country::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the countries_id column
     *
     * @return int|null
     */
    public function getCountriesId(): ?int
    {
        return $this->getVirtualData('countries', 'int', 'id');
    }


    /**
     * Sets the countries_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setCountriesId(?int $id): static
    {
        return $this->setVirtualData('countries', $id, 'id');
    }


    /**
     * Returns the countries_code column
     *
     * @return string|null
     */
    public function getCountriesCode(): ?string
    {
        return $this->getVirtualData('countries', 'string', 'code');
    }


    /**
     * Sets the countries_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setCountriesCode(?string $code): static
    {
        return $this->setVirtualData('countries', $code, 'code');
    }


    /**
     * Returns the countries_name column
     *
     * @return string|null
     */
    public function getCountriesName(): ?string
    {
        return $this->getVirtualData('countries', 'string', 'name');
    }


    /**
     * Sets the countries_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setCountriesName(?string $name): static
    {
        return $this->setVirtualData('countries', $name, 'name');
    }


    /**
     * Returns the Country Object
     *
     * @return CountryInterface|null
     */
    public function getCountryObject(): ?CountryInterface
    {
        return $this->getVirtualObject('countries');
    }


    /**
     * Returns the countries_id for this user
     *
     * @param CountryInterface|null $o_object
     *
     * @return static
     */
    public function setCountryObject(?CountryInterface $o_object): static
    {
        return $this->setVirtualObject('countries', $o_object);
    }
}
