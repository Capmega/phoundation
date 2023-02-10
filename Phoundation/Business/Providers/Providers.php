<?php

namespace Phoundation\Business\Providers;

use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Providers class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Providers extends DataList
{
    /**
     * Providers class constructor
     *
     * @param Company|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Company $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Provider::class;
        $this->table_name  = 'business_providers';

        $this->setHtmlQuery('SELECT   `id`, `name`, `code`, `email`, `status`, `created_on` 
                                   FROM     `business_providers` 
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
     * Returns an HTML <select> object with all available providers
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'providers_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT    `id`, `name` 
                                          FROM     `business_providers`
                                          WHERE    `status` IS NULL 
                                          ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select a provider'))
            ->setEmpty(tr('No providers available'));
    }



    /**
     * @inheritDoc
     */
     protected function load(bool|string|null $id_column = false): static
    {
        // TODO: Implement load() method.
    }



    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }

    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}