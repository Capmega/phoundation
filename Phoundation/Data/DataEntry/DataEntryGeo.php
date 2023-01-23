<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Geo\Cities\City;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;



/**
 * Trait DataEntryGeo
 *
 * This trait contains methods for DataEntry objects that require GEO data (country, state and city)
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryGeo
{
    /**
     * Returns the cities_id for this user
     *
     * @return int|null
     */
    public function getCitiesId(): ?int
    {
        return $this->getDataValue('cities_id');
    }



    /**
     * Sets the cities_id for this user
     *
     * @param int|null $cities_id
     * @return static
     */
    public function setCitiesId(?int $cities_id): static
    {
        return $this->setDataValue('cities_id', $cities_id);
    }



    /**
     * Returns the cities_id for this user
     *
     * @return City|null
     */
    public function getCity(): ?City
    {
        $cities_id = $this->getDataValue('cities_id');

        if ($cities_id) {
            return new City($cities_id);
        }

        return null;
    }



    /**
     * Sets the cities_id for this user
     *
     * @param City|null $city
     * @return static
     */
    public function setCity(?City $city): static
    {
        if (is_object($city)) {
            $city = $city->getId();
        }

        return $this->setDataValue('cities_id', $city);
    }



    /**
     * Returns the states_id for this user
     *
     * @return int|null
     */
    public function getStatesId(): ?int
    {
        return $this->getDataValue('states_id');
    }



    /**
     * Sets the states_id for this user
     *
     * @param int|null $states_id
     * @return static
     */
    public function setStatesId(?int $states_id): static
    {
        return $this->setDataValue('states_id', $states_id);
    }



    /**
     * Returns the state for this user
     *
     * @return State|null
     */
    public function getState(): ?State
    {
        $states_id = $this->getDataValue('states_id');

        if ($states_id) {
            return new State($states_id);
        }

        return null;
    }



    /**
     * Sets the state for this user
     *
     * @param State|null $state
     * @return static
     */
    public function setState(?State $state): static
    {
        if (is_object($state)) {
            $state = $state->getId();
        }

        return $this->setDataValue('states_id', $state);
    }



    /**
     * Returns the countries_id for this user
     *
     * @return int|null
     */
    public function getCountriesId(): ?int
    {
        return $this->getDataValue('countries_id');
    }



    /**
     * Sets the countries_id for this user
     *
     * @param int|null $country
     * @return static
     */
    public function setCountriesId(?int $country): static
    {
        return $this->setDataValue('countries_id', $country);
    }



    /**
     * Returns the countries_id for this user
     *
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        $countries_id = $this->getDataValue('countries_id');

        if ($countries_id) {
            return new Country($countries_id);
        }

        return null;
    }



    /**
     * Sets the countries_id for this user
     *
     * @param Country|null $country
     * @return static
     */
    public function setCountry(?Country $country): static
    {
        if (is_object($country)) {
            $country = $country->getId();
        }

        return $this->setDataValue('countries_id', $country);
    }
}