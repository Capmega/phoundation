<?php

declare(strict_types=1);

namespace Phoundation\Geo\Countries;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;

/**
 * Countries class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Countries extends DataList
{
    /**
     * Countries class constructor
     *
     * @param Country|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Country $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Country::class;
        $this->table_name  = 'geo_countries';

        $this->setHtmlQuery('SELECT   `id`, `name`, `status`, `created_on` 
                                   FROM     `geo_states` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @return Table
     */
    public function getHtmlTable(): Table
    {
        $table = parent::getHtmlTable();
        $table->setCheckboxSelectors(true);

        return $table;
    }


    /**
     * Returns an HTML <select> object with all states available in this country
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlCountriesSelect(string $name = 'countries_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `geo_countries` 
                                          WHERE `status` IS NULL ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select a country'))
            ->setEmpty(tr('No countries available'));
    }


    /**
     * @inheritDoc
     */
    protected function load(string|int|null $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}