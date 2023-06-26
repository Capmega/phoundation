<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies;

use PDOStatement;
use Phoundation\Business\Customers\Customer;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
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
     */
    public function __construct()
    {
        $this->unique_column = 'seo_name';
        $this->entry_class   = Company::class;
        $this->table         = 'business_companies';

        $this->setQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                               FROM     `business_companies` 
                               WHERE    `status` IS NULL 
                               ORDER BY `name`');

        parent::__construct();
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
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @return SelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', string $key_column = 'id'): SelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column)
            ->setName('companies_id')
            ->setNone(tr('Select a company'))
            ->setEmpty(tr('No companies available'));
    }


    /**
     *
     *
     * @param string|int|null $id_column
     * @return $this
     */
    public function load(?string $id_column = null): static
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
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     *
     *
     * @return static
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}