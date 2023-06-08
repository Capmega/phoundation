<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Employees;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;

/**
 * Class Employees
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Employees extends DataList
{
    /**
     * Employees class constructor
     *
     * @param DataEntry|null $parent
     * @param string|null $id_column
     */
    public function __construct(?DataEntry $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Employee::class;
        self::$table       = Employee::getTable();

        $this->setHtmlQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_employees` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
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
        return $this;
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}