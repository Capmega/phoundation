<?php

namespace Phoundation\Business\Customers;

use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataList\DataList;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Customers class
 *
 *
 *
 * @see \Phoundation\Data\DataList\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Customers extends DataList
{
    /**
     * Users class constructor
     *
     * @param Company|null $parent
     * @param string|null $id_column
     */
    public function __construct(Company|null $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Customer::class;
        $this->setHtmlQuery('SELECT `id`, `name`, `code`, `status`, `created_on` FROM `business_customers`');
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