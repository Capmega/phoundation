<?php

declare(strict_types=1);

namespace Phoundation\Business\Providers;

use PDOStatement;
use Phoundation\Business\Companies\Company;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Providers class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Providers extends DataList
{
    /**
     * Providers class constructor
     */
    public function __construct()
    {
        $this->entry_class = Provider::class;
        $this->table       = 'business_providers';

        $this->setQuery('SELECT   `id`, `name`, `code`, `email`, `status`, `created_on` 
                                   FROM     `business_providers` 
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
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        return parent::getHtmlSelect()
            ->setName('providers_id')
            ->setNone(tr('Please select a provider'))
            ->setEmpty(tr('No providers available'));
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

    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }
}