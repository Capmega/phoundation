<?php

declare(strict_types=1);

namespace Phoundation\Geo\Counties;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\DataEntryInterface;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\Timezones\Timezone;

/**
 * Class County
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class County extends DataEntry
{
    use DataEntryNameDescription;

    /**
     * County class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null)
    {
        $this->entry_name   = 'geo county';
        $this->unique_field = 'seo_name';

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'geo_counties';
    }


    /**
     * Returns the general timezone for this county
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('int', 'timezones_id'));
    }


    /**
     * Returns the continent for this county
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('int', 'continents_id'));
    }


    /**
     * Returns the country for this county
     *
     * @return Country
     */
    public function getCountry(): Country
    {
        return new Country($this->getDataValue('int', 'countries_id'));
    }


    /**
     * Returns the state for this county
     *
     * @return State
     */
    public function getState(): State
    {
        return new State($this->getDataValue('int', 'states_id'));
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions;

//        throw new UnderConstructionException();
//
//        $data = $validator
//            ->select($this->getAlternateValidationField('code'), true)->hasMaxCharacters()->isName()->isQueryColumn('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//            ->select($this->getAlternateValidationField('continent'), true)->or('continents_id')->isName()->isQueryColumn('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//            ->select($this->getAlternateValidationField('continents_id'), true)->or('continent')->isId()->isQueryColumn  ('SELECT `id`   FROM `geo_continents` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$continents_id'])
//            ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryColumn  ('SELECT `name` FROM `geo_timezone`   WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
//            ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isId()->isQueryColumn    ('SELECT `id`   FROM `geo_timezone`   WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
//            ->noArgumentsLeft($no_arguments_left)
//            ->validate();
//
//        // Ensure the name doesn't exist yet as it is a unique identifier
//        if ($data['name']) {
//            static::notExists($data['name'], $this->getId(), true);
//        }
//
//        return $data;
    }
}