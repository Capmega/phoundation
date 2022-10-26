<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Databases\Sql\Exception\SqlException;


/**
 * Schema class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Table
{
    /**
     * The table name
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * The columns for this table
     *
     * @var array $columns
     */
    protected array $columns = [];

    /**
     * The indices for this table
     *
     * @var array $indices
     */
    protected array $indices = [];



    /**
     * Table constructor
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        if ($name) {
            // Load this table
            $this->load($name);
        }

    }



    /**
     * Returns if the table exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool
    {
        // If this query returns nothing, the table does not exist. If it returns anything, it does exist.
        return (bool) sql()->query('SHOW TABLES LIKE :name', [':name' => $this->name]);
    }



    /**
     * Returns the table name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }



    /**
     * Sets the table name
     *
     * @param string $name
     * @return Table
     */
    public function setName(string $name): Table
    {
        $this->name = $name;
        return $this;
    }



    /**
     * Returns the table columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }



    /**
     * Sets the table columns
     *
     * @note This will clear the current columns array
     * @param string|array $columns
     * @return Table
     */
    public function setColumns(string|array $columns): Table
    {
        $this->columns = [];
        return $this->addColumns($columns);
    }



    /**
     * Add the array of columns to the table
     *
     * @note This will clear the current columns array
     * @param string|array $columns
     * @return Table
     */
    public function addColumns(string|array $columns): Table
    {
        foreach (Arrays::force($columns) as $column) {
            $this->addColumn($column);
        }

        return $this;
    }



    /**
     * Add a single column to the table
     *
     * @note This will clear the current columns array
     * @param string $column
     * @return Table
     */
    public function addColumn(string $column): Table
    {
        $this->columns[] = $column;
        return $this;
    }



    /**
     * Returns the table indices
     *
     * @return array
     */
    public function getIndices(): array
    {
        return $this->indices;
    }



    /**
     * Sets the table indices
     *
     * @note This will clear the current indices array
     * @param string|array $indices
     * @return Table
     */
    public function setIndices(string|array $indices): Table
    {
        $this->indices = [];
        return $this->addIndices($indices);
    }



    /**
     * Add the array of indices to the table
     *
     * @note This will clear the current indices array
     * @param string|array $indices
     * @return Table
     */
    public function addIndices(string|array $indices): Table
    {
        foreach (Arrays::force($indices) as $index) {
            $this->addIndex($index);
        }

        return $this;
    }



    /**
     * Add a single index to the table
     *
     * @note This will clear the current indices array
     * @param string $index
     * @return Table
     */
    public function addIndex(string $index): Table
    {
        $this->indices[] = $index;
        return $this;
    }



    /**
     * Create the specified table
     *
     * @return void
     */
    public function create(): void
    {
        if ($this->exists()) {
            throw new SqlException(tr('Cannot create table ":name", it already exists', [':name' => $this->name]));
        }

        sql()->query($this->getCreateQuery());
    }



    /**
     * Create the SQL query to create the table in the database
     *
     * @return string
     */
    protected function getCreateQuery(): string
    {
        $query   = 'CREATE TABLE `' . $this->name . '` (';
        $query  .= implode(",\n", $this->columns);
        $query  .= implode("\n", $this->columns);
        $query  .= ') ENGINE=InnoDB AUTO_INCREMENT = ' . Config::get('databases.sql.instances.system.auto-increment', 1) . ' DEFAULT CHARSET="' . Config::get('databases.sql.instances.system.charset', 'utf8mb4') . '" COLLATE="' . Config::get('databases.sql.instances.system.collate', 'utf8mb4_general_ci') . '";';

        return $query;
    }



    /**
     * @param string $name
     * @return void
     */
    protected function load(string $name): void
    {
        $this->name = $name;

        // Load columns & indices data
        // TODO Implement
    }
}