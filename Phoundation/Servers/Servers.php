<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use PDOStatement;
use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;


/**
 * Servers class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Servers extends DataList
{
    /**
     * Servers class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null)
    {
        $this->entry_class = Server::class;
        $this->table       = 'servers';

        $this->setQuery('SELECT   `id`, `name`, `code`, `email`, `status`, `created_on` 
                                   FROM     `servers` 
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
     * Returns an HTML select component object containing the entries in this list
     *
     * @return SelectInterface
     */
    public function getHtmlSelect(): SelectInterface
    {
        return Select::new()
            ->setSourceQuery('SELECT    `id`, `name` 
                                          FROM     `servers`
                                          WHERE    `status` IS NULL 
                                          ORDER BY `name`')
            ->setName('servers_id')
            ->setNone(tr('Please select a server'))
            ->setEmpty(tr('No servers available'));
    }


    /**
     * @inheritDoc
     */
    public function load(?string $id_column = null): static
    {
        $this->source = sql()->list('SELECT `servers`.`id`, `servers`.`hostname`, `servers`.`created_on`, `servers`.`status` 
                                   FROM     `servers` 
                                   WHERE    `servers`.`status` IS NULL
                                   ORDER BY `servers`.`hostname`' . sql()->getLimit());

        // The keys contain the ids...
        $this->source = array_flip($this->source);
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }


    /**
     * Load the data for this right list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,hostname,port,created_on';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['hostname' => false];
        }

        // Get column information
        $columns = Arrays::force($columns);
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect($columns);
        $builder->addFrom('`servers`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
        }

        // Build filters
        foreach ($filters as $key => $value){
            switch ($key) {
                case 'deleted':
                    $no_delete = true;
            }
        }

        if (isset($no_delete)) {
            $builder->addWhere('`status` IS NULL');
        }

        return sql()->list($builder->getQuery(), $builder->getExecute());
    }
}