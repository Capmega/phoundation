<?php

namespace Phoundation\Business\Providers;

use Phoundation\Data\DataList\DataList;



/**
 * Providers class
 *
 *
 *
 * @see \Phoundation\Data\DataList\DataList
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
     * @param Provider|null $parent
     * @param string|null $id_column
     */
    public function __construct(?Provider $parent = null, ?string $id_column = null)
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