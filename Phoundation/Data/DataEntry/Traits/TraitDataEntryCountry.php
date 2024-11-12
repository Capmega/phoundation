<?php

/**
 * Trait DataEntryCountry
 *
 * This trait contains methods for DataEntry objects that require GEO country data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;


trait TraitDataEntryCountry
{
    /**
     * Country object cache
     *
     * @var CountryInterface|null $o_country
     */
    protected ?CountryInterface $o_country;


    /**
     * Returns the countries_id for this object
     *
     * @return int|null
     */
    public function getCountriesId(): ?int
    {
        return $this->getTypesafe('int', 'countries_id');
    }


    /**
     * Sets the countries_id for this object
     *
     * @param int|null $countries_id
     *
     * @return static
     */
    public function setCountriesId(?int $countries_id): static
    {
        $this->o_country = null;
        return $this->set($countries_id, 'countries_id');
    }


    /**
     * Returns the country for this object
     *
     * @return CountryInterface|null
     */
    public function getCountryObject(): ?CountryInterface
    {
        if (empty($this->o_country)) {
            $this->o_country = Country::loadOrNull($this->getTypesafe('int', 'countries_id'));
        }

        return $this->o_country;
    }


    /**
     * Sets the country for this object
     *
     * @param CountryInterface|null $o_country
     * @return TraitDataEntryCountry
     */
    public function setCountryObject(?CountryInterface $o_country): static
    {
        $this->setCountriesId($o_country?->getId());

        $this->o_country = $o_country;
        return $this;
    }


    /**
     * Returns the countries_name for this object
     *
     * @return string|null
     */
    public function getCountriesName(): ?string
    {
        return $this->getCountryObject()->getName();
    }


    /**
     * Returns the countries_name for this object
     *
     * @param string|null $countries_name
     *
     * @return static
     */
    public function setCountriesName(?string $countries_name): static
    {
        return $this->setCountryObject(Country::loadOrNull(['name' => $countries_name]));
    }
}
