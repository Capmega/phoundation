<?php

declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Sql\QueryBuilder;
use Phoundation\Web\Http\Html\Components\Input\Select;
use Phoundation\Web\Http\Html\Components\Table;

/**
 * SshAccounts class
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class SshAccounts extends DataList
{
    /**
     * SshAccounts class constructor
     *
     * @param DataEntry|null $parent
     * @param string|null $id_column
     */
    public function __construct(?DataEntry $parent = null, ?string $id_column = null)
    {
        $this->entry_class = SshAccount::class;
        self::$table       = SshAccounts::getTable();

        $this->setHtmlQuery('SELECT   `id`, `name`, `created_on` 
                                   FROM     `ssh_accounts` 
                                   WHERE    `status` IS NULL 
                                   ORDER BY `name`');
        parent::__construct($parent, $id_column);
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
     * Returns an HTML <select> object with all available ssh_accounts_id
     *
     * @param string $name
     * @return Select
     */
    public static function getHtmlSelect(string $name = 'ssh_accounts_id'): Select
    {
        return Select::new()
            ->setSourceQuery('SELECT    `id`, `name` 
                                          FROM     `ssh_accounts`
                                          WHERE    `status` IS NULL 
                                          ORDER BY `name`')
            ->setName($name)
            ->setNone(tr('Please select an SSH account'))
            ->setEmpty(tr('No SSH accounts available'));
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
        // TODO: Implement save() method.
    }


    /**
     * Load the data for this right list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @return array
     */
    protected function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,name,created_on';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['name' => false];
        }

        // Get column information
        $columns = Arrays::force($columns);
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect('SELECT ' . $columns);
        $builder->addFrom('FROM `ssh_accounts`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('ORDER BY `' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
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