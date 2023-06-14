<?php

declare(strict_types=1);

namespace Phoundation\Business\Customers;

use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Customers class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Customers extends DataList
{
    /**
     * Customers class constructor
     *
     * @param Company|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Company $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Customer::class;
        self::$table       = Customer::getTable();

        $this->setHtmlQuery('SELECT   `id`, `name`, `code`, `email`, `status`, `created_on` 
                                   FROM     `business_customers` 
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
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        return Select::new()
            ->setSourceQuery('SELECT    `id`, `name` 
                                          FROM     `business_customers`
                                          WHERE    `status` IS NULL 
                                          ORDER BY `name`')
            ->setName('customers_id')
            ->setNone(tr('Please select a customer'))
            ->setEmpty(tr('No customers available'));
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
    public function save(): static
    {
        // TODO: Implement save() method.
    }

    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}