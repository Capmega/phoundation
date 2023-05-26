<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;
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
        return get_null((integer) $this->getDataValue('cities_id'));
    }


    /**
     * Sets the cities_id for this user
     *
     * @param string|int|null $cities_id
     * @return static
     */
    public function setCitiesId(string|int|null $cities_id): static
    {
        if ($cities_id and !is_natural($cities_id)) {
            throw new OutOfBoundsException(tr('Specified cities_id ":id" is not numeric', [
                ':id' => $cities_id
            ]));
        }

        return $this->setDataValue('cities_id', (integer) $cities_id);
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
     * @param City|string|int|null $cities_id
     * @return static
     */
    public function setCity(City|string|int|null $cities_id): static
    {
        if (!is_numeric($cities_id)) {
            $cities_id = City::get($cities_id);
        }

        if (is_object($cities_id)) {
            $cities_id = $cities_id->getId();
        }

        return $this->setDataValue('cities_id', $cities_id);
    }


    /**
     * Returns the states_id for this user
     *
     * @return int|null
     */
    public function getStatesId(): ?int
    {
        return get_null((integer) $this->getDataValue('states_id'));
    }


    /**
     * Sets the states_id for this user
     *
     * @param string|int|null $states_id
     * @return static
     */
    public function setStatesId(string|int|null $states_id): static
    {
        if ($states_id and !is_natural($states_id)) {
            throw new OutOfBoundsException(tr('Specified states_id ":id" is not a natural number', [
                ':id' => $states_id
            ]));
        }

        return $this->setDataValue('states_id', (integer) $states_id);
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
     * @param State|string|int|null $states_id
     * @return static
     */
    public function setState(State|string|int|null $states_id): static
    {
        if (!is_numeric($states_id)) {
            $states_id = State::get($states_id);
        }

        if (is_object($states_id)) {
            $states_id = $states_id->getId();
        }

        return $this->setDataValue('states_id', $states_id);
    }


    /**
     * Returns the countries_id for this user
     *
     * @return int|null
     */
    public function getCountriesId(): ?int
    {
        return get_null((integer) $this->getDataValue('countries_id'));
    }


    /**
     * Sets the countries_id for this user
     *
     * @param string|int|null $countries_id
     * @return static
     */
    public function setCountriesId(string|int|null $countries_id): static
    {
        if ($countries_id and !is_natural($countries_id)) {
            throw new OutOfBoundsException(tr('Specified countries_id ":id" is not a natural number', [
                ':id' => $countries_id
            ]));
        }

        return $this->setDataValue('countries_id', (integer) $countries_id);
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
     * @param Country|string|int|null $countries_id
     * @return static
     */
    public function setCountry(Country|string|int|null $countries_id): static
    {
        if (!is_numeric($countries_id)) {
            $countries_id = Country::get($countries_id);
        }

        if (is_object($countries_id)) {
            $countries_id = $countries_id->getId();
        }

        return $this->setDataValue('countries_id', $countries_id);
    }
}