<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Sql;


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
     * The SQL interface
     *
     * @var Sql $sql
     */
    protected Sql $sql;

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
     * The foreign keys for this table
     *
     * @var array $foreign_keys
     */
    protected array $foreign_keys = [];



    /**
     * Table constructor
     *
     * @param Sql $sql
     * @param string|null $name
     */
    public function __construct(Sql $sql, ?string $name = null)
    {
        $this->sql = $sql;

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
        return (bool) sql()->get('SHOW TABLES LIKE :name', [':name' => $this->name]);
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
            if (!$column) {
                // Quietly drop empty colums
                continue;
            }

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
            if (!$index) {
                // Quietly drop empty indices
                continue;
            }
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
     * Returns the table foreign_keys
     *
     * @return array
     */
    public function getForeignKeys(): array
    {
        return $this->foreign_keys;
    }



    /**
     * Sets the table foreign_keys
     *
     * @note This will clear the current foreign_keys array
     * @param string|array $foreign_keys
     * @return Table
     */
    public function setForeignKeys(string|array $foreign_keys): Table
    {
        $this->foreign_keys = [];
        return $this->addForeignKeys($foreign_keys);
    }



    /**
     * Add the array of foreign_keys to the table
     *
     * @note This will clear the current foreign_keys array
     * @param string|array $foreign_keys
     * @return Table
     */
    public function addForeignKeys(string|array $foreign_keys): Table
    {
        foreach (Arrays::force($foreign_keys) as $foreign_key) {
            if (!$foreign_key) {
                // Quietly drop empty foreign keys
                continue;
            }
            $this->addForeignKey($foreign_key);
        }

        return $this;
    }



    /**
     * Add a single foreign_key to the table
     *
     * @note This will clear the current foreign_keys array
     * @param string $foreign_key
     * @return Table
     */
    public function addForeignKey(string $foreign_key): Table
    {
        $this->foreign_keys[] = $foreign_key;
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

        // Prepare incides and FKs
        $indices      = implode(",\n", $this->indices) . "\n";
        $foreign_keys = implode(",\n", $this->foreign_keys) . "\n";

        // Build and execute query
        $query  = 'CREATE TABLE `' . $this->name . '` (';
        $query .= implode(",\n", $this->columns);

        if ($this->indices) {
            $query .= ",\n" . $indices . "\n";
        }

        if ($this->foreign_keys) {
            $query .= ",\n" . $foreign_keys . "\n";
        }

        $query .= ') ENGINE=InnoDB AUTO_INCREMENT = ' . Config::get('databases.sql.instances.system.auto-increment', 1) . ' DEFAULT CHARSET="' . Config::get('databases.sql.instances.system.charset', 'utf8mb4') . '" COLLATE="' . Config::get('databases.sql.instances.system.collate', 'utf8mb4_general_ci') . '";';

        sql()->query($query);
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