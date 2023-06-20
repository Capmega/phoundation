<?php

declare(strict_types=1);

namespace Phoundation\Geo\Cities;

use PDOStatement;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Geo\States\State;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Cities class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Cities extends DataList
{
    /**
     * Cities class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null)
    {
        $this->entry_class = City::class;
        $this->table  = 'geo_cities';

        $this->setQuery('SELECT   `id`, `name`, `status`, `created_on` 
                                   FROM     `geo_cities` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($source, $execute);
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
     * @inheritDoc
     */
    public function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }


    /**
     * @inheritDoc
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // TODO: Implement loadDetails() method.
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
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