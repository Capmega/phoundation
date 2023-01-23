<?php

namespace Phoundation\Business\Companies\Branches;

use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataList\DataList;



/**
 * Class Branches
 *
 *
 *
 * @see \Phoundation\Data\DataList\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Companies
 */
class Branches extends DataList
{
    /**
     * Branches class constructor
     *
     * @param Company|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Company $parent = null, ?string $id_column = null)
    {
        $this->entry_class = Branch::class;
        $this->table_name  = 'business_branches';

        $this->setHtmlQuery('SELECT   `id`, `name`, `email`, `status`, `created_on` 
                                   FROM     `business_departments` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
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



    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}