<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Departments;

use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 *  Class Departments
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Departments extends DataList
{
    /**
     * Departments class constructor
     *
     * @param Company|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Company $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Department::class;
        $this->table       = 'business_departments';

        $this->setHtmlQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_departments` 
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
    public function save(): bool
    {
        // TODO: Implement save() method.
    }


    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        // TODO: Implement getHtmlSelect() method.
    }
}