<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use Phoundation\Business\Customers\Customer;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Class Companies
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Companies extends DataList
{
    /**
     * Companies class constructor
     *
     * @param Customer|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Customer $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Company::class;
        $this->table       = 'business_companies';

        $this->setHtmlQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_companies` 
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
            ->setSourceQuery('SELECT `id`, `name` 
                                          FROM  `business_companies` 
                                          WHERE `status` IS NULL ORDER BY `name`')
            ->setName('companies_id')
            ->setNone(tr('Please select a company'))
            ->setEmpty(tr('No companies available'));
    }


    /**
     *
     *
     * @param string|int|null $id_column
     * @return $this
     */
    protected function load(string|int|null $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     *
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     *
     *
     * @return bool
     */
    public function save(): bool
    {
        // TODO: Implement save() method.
    }
}