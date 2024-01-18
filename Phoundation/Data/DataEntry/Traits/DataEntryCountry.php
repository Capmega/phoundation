<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Geo\Cities\City;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;


/**
 * Trait DataEntryCountry
 *
 * This trait contains methods for DataEntry objects that require GEO country data
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCountry
{
    /**
     * Returns the countries_id for this user
     *
     * @return int|null
     */
    public function getCountriesId(): ?int
    {
        return $this->getSourceColumnValue('int', 'countries_id');
    }


    /**
     * Sets the countries_id for this user
     *
     * @param int|null $countries_id
     * @return static
     */
    public function setCountriesId(?int $countries_id): static
    {
        return $this->setSourceValue('countries_id', $countries_id);
    }


    /**
     * Returns the countries_id for this user
     *
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        $countries_id = $this->getSourceColumnValue('int', 'countries_id');

        if ($countries_id) {
            return new Country($countries_id);
        }

        return null;
    }


    /**
     * Returns the countries_name for this user
     *
     * @return string|null
     */
    public function getCountriesName(): ?string
    {
        return $this->getSourceColumnValue('string', 'countries_name');
    }


    /**
     * Sets the countries_name for this user
     *
     * @param string|null $countries_name
     * @return static
     */
    public function setCountriesName(?string $countries_name): static
    {
        return $this->setSourceValue('countries_name', $countries_name);
    }
}
