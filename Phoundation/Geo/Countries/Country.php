<?php

/**
 * Class Country
 *
 *
 *
 * @see       \Phoundation\Data\DataEntry\DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Geo
 */


declare(strict_types=1);

namespace Phoundation\Geo\Countries;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Html\Components\Input\InputSelect;


class Country extends DataEntry
{
    use TraitDataEntryNameDescription;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'geo_countries';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'geo country';
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
     * Returns the general timezone for this country
     *
     * @return Timezone
     */
    public function getTimezone(): Timezone
    {
        return new Timezone($this->getTypesafe('int', 'timezones_id'));
    }


    /**
     * Returns the continent for this country
     *
     * @return Continent
     */
    public function getContinent(): Continent
    {
        return new Continent($this->getTypesafe('int', 'continents_id'));
    }


    /**
     * Returns an HTML <select> object with all states available in this country
     *
     * @param string $name
     *
     * @return InputSelect
     */
    public function getHtmlStatesSelect(string $name = 'states_id'): InputSelect
    {
        return InputSelect::new()
                          ->setConnectorObject($this->getConnectorObject())
                          ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `geo_states` 
                                          WHERE `countries_id` = :countries_id AND `status` IS NULL ORDER BY `name`', [
                              ':countries_id' => $this->getId(),
                          ])
                          ->setName($name)
                          ->setNotSelectedLabel(tr('Select a state'))
                          ->setComponentEmptyLabel(tr('No states available'));
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions;
//            $data = $validator
//                ->select($this->getAlternateValidationField('code'), true)->hasMaxCharacters()->isName()->isQueryResult('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//                ->select($this->getAlternateValidationField('continent'), true)->or('continents_id')->isName()->isQueryResult('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//                ->select($this->getAlternateValidationField('continents_id'), true)->or('continent')->isDbId()->isQueryResult  ('SELECT `id`   FROM `geo_continents` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$continents_id'])
//                ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryResult  ('SELECT `name` FROM `geo_timezone`   WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
//                ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isDbId()->isQueryResult    ('SELECT `id`   FROM `geo_timezone`   WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
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
