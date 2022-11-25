<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Sql;



/**
 * TableAlter class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class TableAlter extends SchemaAbstract
{
    /**
     * Sets the table name
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->sql->query('RENAME TABLE :from TO :to', [
            ':from' => $this->name,
            ':tp'   => $name
        ]);

        $this->name = $name;
        return $this;
    }



    /**
     * Add the array of columns to the table
     *
     * @note This will clear the current columns array
     * @param string|array $columns
     * @param string $after
     * @return TableAlter
     */
    public function addColumns(string|array $columns, string $after): TableAlter
    {
        foreach (Arrays::force($columns) as $column) {
            if (!$column) {
                // Quietly drop empty columns
                continue;
            }

            $this->addColumn($column, $after);
        }

        return $this;
    }



    /**
     * Add a single column to the table
     *
     * @note This will clear the current columns array
     * @param string $column
     * @param string $after
     * @return TableAlter
     */
    public function addColumn(string $column, string $after): TableAlter
    {
        if ($column) {
            $this->sql->query('ALTER TABLE ' . $this->name .  ' ADD COLUMN ' . $column . ' ' . $after);
        }

        return $this;
    }



    /**
     * Add the array of indices to the table
     *
     * @param string|array $indices
     * @return TableAlter
     */
    public function addIndices(string|array $indices): TableAlter
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
     * @return TableAlter
     */
    public function addIndex(string $index): TableAlter
    {
        if ($index) {
            $this->sql->query('ALTER TABLE ' . $this->name .  ' ADD ' . $index);
        }

        return $this;
    }



    /**
     * Add the array of foreign_keys to the table
     *
     * @param string|array $foreign_keys
     * @return TableAlter
     */
    public function addForeignKeys(string|array $foreign_keys): TableAlter
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
     * @return TableAlter
     */
    public function addForeignKey(string $foreign_key): TableAlter
    {
        if ($foreign_key) {
            $this->sql->query('ALTER TABLE ' . $this->name .  ' ADD ' . $foreign_key);
        }
        return $this;
    }

}