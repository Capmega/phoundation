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
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
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
     */
    public function __construct()
    {
        $this->setQuery('SELECT   `id`, `name`, `code`, `email`, `status`, `created_on` 
                                   FROM     `servers` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'servers';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryClass(): string
    {
        return Server::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return 'seo_name';
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
            ->setName('servers_id')
            ->setNone(tr('Select a server'))
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