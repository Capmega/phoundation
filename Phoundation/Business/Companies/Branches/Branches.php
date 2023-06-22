<?php

declare(strict_types=1);

namespace Phoundation\Business\Companies\Branches;

use PDOStatement;
use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


/**
 * Class Branches
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Branches extends DataList
{
    /**
     * Branches class constructor
     */
    public function __construct()
    {
        $this->entry_class = Branch::class;
        $this->table       = 'business_departments';

        $this->setQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_departments` 
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
        // TODO: Implement save() method.
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
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
        return parent::getHtmlSelect()
            ->setName('branches_id')
            ->setNone(tr('Please select a branch'))
            ->setEmpty(tr('No branches available'));
    }
}