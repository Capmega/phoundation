<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Employees;

use PDOStatement;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 * Class Employees
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employees extends DataList
{
    /**
     * Employees class constructor
     */
    public function __construct()
    {
        $this->entry_class = Employee::class;
        $this->table       = 'business_employees';

        $this->setQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_employees` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct();
    }


    /**
     * @inheritDoc
     */
     public function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
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
            ->setName('employees_id')
            ->setNone(tr('Select a employee'))
            ->setEmpty(tr('No employees available'));
    }
}