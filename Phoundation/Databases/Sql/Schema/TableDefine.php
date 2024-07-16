<?php

/**
 * TableDefine class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Log\Log;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;

class TableDefine extends SchemaAbstract
{
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
     * Clears the table columns
     *
     * @return TableDefine
     */
    public function clearColumns(): TableDefine
    {
        $this->columns = [];

        return $this;
    }


    /**
     * Sets the table columns
     *
     * @note This will clear the current columns array
     *
     * @param string|array $columns
     *
     * @return TableDefine
     */
    public function setColumns(string|array $columns): TableDefine
    {
        $this->columns = [];

        return $this->addColumns($columns);
    }


    /**
     * Add the array of columns to the table
     *
     * @note This will clear the current columns array
     *
     * @param string|array $columns
     *
     * @return TableDefine
     */
    public function addColumns(string|array $columns): TableDefine
    {
        foreach (Arrays::force($columns) as $column) {
            if (!$column) {
                // Quietly drop empty columns
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
     *
     * @param string $column
     *
     * @return TableDefine
     */
    public function addColumn(string $column): TableDefine
    {
        if ($column) {
            $this->columns[] = $column;
        }

        return $this;
    }


    /**
     * Clears the table indices
     *
     * @return TableDefine
     */
    public function clearIndices(): TableDefine
    {
        $this->indices = [];

        return $this;
    }


    /**
     * Sets the table indices
     *
     * @note This will clear the current indices array
     *
     * @param string|array $indices
     *
     * @return TableDefine
     */
    public function setIndices(string|array $indices): TableDefine
    {
        $this->indices = [];

        return $this->addIndices($indices);
    }


    /**
     * Add the array of indices to the table
     *
     * @param string|array $indices
     *
     * @return TableDefine
     */
    public function addIndices(string|array $indices): TableDefine
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
     * @param string $index
     *
     * @return TableDefine
     */
    public function addIndex(string $index): TableDefine
    {
        $this->indices[] = $index;

        return $this;
    }


    /**
     * Clears the table foreign_keys
     *
     * @return TableDefine
     */
    public function clearForeignKeys(): TableDefine
    {
        $this->foreign_keys = [];

        return $this;
    }


    /**
     * Sets the table foreign_keys
     *
     * @note This will clear the current foreign_keys array
     *
     * @param string|array $foreign_keys
     *
     * @return TableDefine
     */
    public function setForeignKeys(string|array $foreign_keys): TableDefine
    {
        $this->foreign_keys = [];

        return $this->addForeignKeys($foreign_keys);
    }


    /**
     * Add the array of foreign_keys to the table
     *
     * @param string|array $foreign_keys
     *
     * @return TableDefine
     */
    public function addForeignKeys(string|array $foreign_keys): TableDefine
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
     * @param string $foreign_key
     *
     * @return TableDefine
     */
    public function addForeignKey(string $foreign_key): TableDefine
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
        if ($this->parent->exists()) {
            throw new SqlException(tr('Cannot create table ":name", it already exists', [':name' => $this->name]));
        }

        // Prepare indices and FKs
        $indices      = implode(",\n", $this->indices) . "\n";
        $foreign_keys = implode(",\n", $this->foreign_keys) . "\n";

        // Build and execute query
        $query = 'CREATE TABLE `' . $this->name . '` (';
        $query .= Strings::ensureEndsNotWith(trim(implode(",\n", $this->columns)), ',');

        if ($this->indices) {
            $query .= ",\n" . Strings::ensureEndsNotWith(trim($indices), ',') . "\n";
        }

        if ($this->foreign_keys) {
            $query .= ",\n" . Strings::ensureEndsNotWith(trim($foreign_keys), ',') . "\n";
        }

        $query .= ') ENGINE=InnoDB AUTO_INCREMENT = ' . Config::get('databases.sql.connectors.system.auto-increment', 1) . ' DEFAULT CHARSET="' . Config::get('databases.sql.connectors.system.charset', 'utf8mb4') . '" COLLATE="' . Config::get('databases.sql.connectors.system.collate', 'utf8mb4_general_ci') . '";';

        Log::warning(tr('Creating table ":table" in database ":database" for SQL instance ":instance"', [
            ':table'    => $this->name,
            ':instance' => $this->sql->getConnector(),
            ':database' => $this->sql->getDatabase(),
        ]), 3);

        $this->sql->query($query);
        $this->parent->reload();
    }


    /**
     * Sets the table name
     *
     * @param string $name
     *
     * @return static
     */
    protected function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
}
