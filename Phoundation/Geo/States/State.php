<?php

/**
 * Class State
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Geo
 */


declare(strict_types=1);

namespace Phoundation\Geo\States;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Geo\Continents\Continent;
use Phoundation\Geo\Continents\Interfaces\ContinentInterface;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\Countries\Interfaces\CountryInterface;
use Phoundation\Geo\States\Interfaces\StateInterface;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Web\Html\Components\Input\InputSelect;


class State extends DataEntry implements StateInterface
{
    use TraitDataEntryNameDescription;

    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'geo_states';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return 'geo state';
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
     * Returns the general timezone for this state
     *
     * @return TimezoneInterface
     */
    public function getTimezoneObject(): TimezoneInterface
    {
        return new Timezone($this->getTypesafe('int', 'timezones_id'));
    }


    /**
     * Returns the continent for this state
     *
     * @return ContinentInterface
     */
    public function getContinentObject(): ContinentInterface
    {
        return new Continent($this->getTypesafe('int', 'continents_id'));
    }


    /**
     * Returns the country for this state
     *
     * @return CountryInterface
     */
    public function getCountryObject(): CountryInterface
    {
        return new Country($this->getTypesafe('int', 'countries_id'));
    }


    /**
     * Returns an HTML <select> object with all cities available in this state
     *
     * @param string $name
     *
     * @return InputSelect
     */
    public function getHtmlCitiesSelect(string $name = 'cities_id'): InputSelect
    {
        return InputSelect::new()
                          ->setConnectorObject($this->getConnectorObject())
                          ->setSourceQuery('SELECT   `id`, `name` 
                                            FROM     `geo_cities` 
                                            WHERE    `states_id` = :states_id 
                                            AND     (`status` IS NULL OR `status` != "deleted")
                                            ORDER BY `name`', [
                                                ':states_id' => $this->getId(false),
                          ])
                          ->setName($name)
                          ->setNotSelectedLabel(tr('Select a city'))
                          ->setComponentEmptyLabel(tr('No cities available'));
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $_definitions): static
    {
        $_definitions;

        return $this;
//        throw new UnderConstructionException();
//
//        $data = $_validator
//            ->select($this->getAlternateValidationField('code'), true)->hasMaxCharacters()->isName()->isQueryResult('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//            ->select($this->getAlternateValidationField('continent'), true)->or('continents_id')->isName()->isQueryResult('SELECT `name` FROM `geo_continents` WHERE `name` = :name AND `status` IS NULL', [':name' => '$continent'])
//            ->select($this->getAlternateValidationField('continents_id'), true)->or('continent')->isDbId()->isQueryResult  ('SELECT `id`   FROM `geo_continents` WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$continents_id'])
//            ->select($this->getAlternateValidationField('timezone'), true)->or('timezones_id')->isName()->isQueryResult  ('SELECT `name` FROM `geo_timezone`   WHERE `name` = :name AND `status` IS NULL', [':name' => '$timezone'])
//            ->select($this->getAlternateValidationField('timezones_id'), true)->or('timezone')->isDbId()->isQueryResult    ('SELECT `id`   FROM `geo_timezone`   WHERE `id`   = :id   AND `status` IS NULL', [':id'   => '$timezones_id'])
//            ->noArgumentsLeft($no_arguments_left)
//            ->validate();
//
//        // Ensure the name does not exist yet as it is a unique identifier
//        if ($data['name']) {
//            static::notExists(['name' => $data['name']], $this->getId(), true);
//        }
//
//        return $data;
    }
}
