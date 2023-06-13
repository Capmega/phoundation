<?php

declare(strict_types=1);

namespace Phoundation\Geo\Countries;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Http\Html\Components\Input\Select;


/**
 * Class Country
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Country extends DataEntry
{
    use DataEntryNameDescription;

    /**
     * Country class constructor
     *
     * @param DataEntry|string|int|null $identifier
     */
    public function __construct(DataEntry|string|int|null $identifier = null)
    {
        $this->entry_name   = 'geo country';
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
     * Returns the general timezone for this country
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getDataValue('int', 'timezones_id'));
    }


    /**
     * Returns the continent for this country
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getDataValue('int', 'continents_id'));
    }


    /**
     * Returns an HTML <select> object with all states available in this country
     *
     * @param string $name
     * @return Select
     */
    public function getHtmlStatesSelect(string $name = 'states_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `geo_states` 
                                          WHERE `countries_id` = :countries_id AND `status` IS NULL ORDER BY `name`', [
                ':countries_id' => $this->getId()
            ])
            ->setName($name)
            ->setNone(tr('Please select a state'))
            ->setEmpty(tr('No states available'));
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions;

//            $data = $validator
//                ->select($this->getAlternateValidationField('code'), true)->hasMaxCharacters()->isName()->isQueryColumn('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//                ->select($this->getAlternateValidationField('continent'), true)->or('continents_id')->isName()->isQueryColumn('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//                ->select($this->getAlternateValidationField('continents_id'), true)->or('continent')->isId()->isQueryColumn  ('SELECT `id`   FROM `geo_continents` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$continents_id'])
//                ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryColumn  ('SELECT `name` FROM `geo_timezone`   WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
//                ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isId()->isQueryColumn    ('SELECT `id`   FROM `geo_timezone`   WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
//                ->noArgumentsLeft($no_arguments_left)
//                ->validate();

//            'continents_id' bigint DEFAULT NULL,
//            'timezones_id' bigint DEFAULT NULL,
//            'code' varchar(2) DEFAULT NULL,
//            'iso_alpha2' char(2) DEFAULT NULL,
//            'iso_alpha3' char(3) DEFAULT NULL,
//            'iso_numeric' char(3) DEFAULT NULL,
//            'fips_code' varchar(3) DEFAULT NULL,
//            'tld' varchar(2) DEFAULT NULL,
//            'currency' varchar(3) DEFAULT NULL,
//            'currency_name' varchar(20) DEFAULT NULL,
//            'phone' varchar(10) CHARACTER SET latin1 DEFAULT NULL,
//            'postal_code_format' varchar(100) DEFAULT NULL,
//            'postal_code_regex' varchar(255) DEFAULT NULL,
//            'languages' varchar(200) DEFAULT NULL,
//            'neighbours' varchar(100) DEFAULT NULL,
//            'equivalent_fips_code' varchar(10) DEFAULT NULL,
//            'latitude' decimal(10,7) DEFAULT NULL,
//            'longitude' decimal(10,7) DEFAULT NULL,
//            'alternate_names' varchar(4000) DEFAULT NULL,
//            'name' varchar(200) NOT NULL,
//            'seo_name' varchar(200) NOT NULL,
//            'capital' varchar(200) DEFAULT NULL,
//            'areainsqkm' double DEFAULT NULL,
//            'population' int DEFAULT NULL,
    }
}