<?php

/**
 * Class County
 *
 *
 *
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Geo
 */


declare(strict_types=1);

namespace Phoundation\Geo\Counties;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Continents\Interfaces\ContinentInterface;
use Phoundation\Geo\Counties\Interfaces\CountyInterface;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;
use Phoundation\Geo\States\Interfaces\StateInterface;
use Phoundation\Geo\States\State;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;
use Phoundation\Geo\Timezones\Timezone;


class County extends DataEntry implements CountyInterface
{
    use TraitDataEntryNameDescription;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'geo_counties';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return 'geo county';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Returns the general timezone for this county
     *
     * @return TimezoneInterface
     */
    public function getTimezone(): TimezoneInterface
    {
        return new Timezone($this->getTypesafe('int', 'timezones_id'));
    }


    /**
     * Returns the continent for this county
     *
     * @return ContinentInterface
     */
    public function getContinent(): ContinentInterface
    {
        return new Continent($this->getTypesafe('int', 'continents_id'));
    }


    /**
     * Returns the country for this county
     *
     * @return CountryInterface
     */
    public function getCountry(): CountryInterface
    {
        return new Country($this->getTypesafe('int', 'countries_id'));
    }


    /**
     * Returns the state for this county
     *
     * @return StateInterface
     */
    public function getState(): StateInterface
    {
        return new State($this->getTypesafe('int', 'states_id'));
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     *
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions;

        return $this;
//        throw new UnderConstructionException();
//
//        $data = $validator
//            ->select($this->getAlternateValidationField('code'), true)->hasMaxCharacters()->isName()->isQueryResult('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//            ->select($this->getAlternateValidationField('continent'), true)->or('continents_id')->isName()->isQueryResult('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//            ->select($this->getAlternateValidationField('continents_id'), true)->or('continent')->isDbId()->isQueryResult  ('SELECT `id`   FROM `geo_continents` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$continents_id'])
//            ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryResult  ('SELECT `name` FROM `geo_timezone`   WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
//            ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isDbId()->isQueryResult    ('SELECT `id`   FROM `geo_timezone`   WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
//            ->noArgumentsLeft($no_arguments_left)
//            ->validate();
//
//        // Ensure the name doesn't exist yet as it is a unique identifier
//        if ($data['name']) {
//            static::notExists(['name' => $data['name']], $this->getId(), true);
//        }
//
//        return $data;
    }
}
