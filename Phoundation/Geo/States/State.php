<?php

declare(strict_types=1);

namespace Phoundation\Geo\States;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryFieldDefinitions;
use Phoundation\Data\DataEntry\Interfaces\DataEntryFieldDefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Http\Html\Components\Input\Select;

/**
 * Class State
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class State extends DataEntry
{
    use DataEntryNameDescription;

    /**
     * State class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'geo state';
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
        return 'geo_states';
    }


    /**
     * Returns the general timezone for this state
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('int', 'timezones_id'));
    }


    /**
     * Returns the continent for this state
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('int', 'continents_id'));
    }


    /**
     * Returns the country for this state
     *
     * @return Country
     */
    public function getCountry(): Country
    {
        return new Country($this->getDataValue('int', 'countries_id'));
    }


    /**
     * Returns an HTML <select> object with all cities available in this state
     *
     * @param string $name
     * @return Select
     */
    public function getHtmlCitiesSelect(string $name = 'cities_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `geo_cities` 
                                          WHERE `states_id` = :states_id AND `status` IS NULL ORDER BY `name`', [
                ':states_id' => $this->getId()
            ])
            ->setName($name)
            ->setNone(tr('Please select a city'))
            ->setEmpty(tr('No cities available'));
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DataEntryFieldDefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DataEntryFieldDefinitionsInterface $field_definitions): void
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