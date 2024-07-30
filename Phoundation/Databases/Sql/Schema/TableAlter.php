<?php

/**
 * TableAlter class
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

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;

class TableAlter extends SchemaAbstract
{
    /**
     * Sets the table name
     *
     * @param string $name
     *
     * @return static
     */
    public function setName(string $name): static
    {
        $this->sql->query('RENAME TABLE :from TO :to', [
            ':from' => $this->database,
            ':tp'   => $name,
        ]);
        $this->database = $name;

        return $this;
    }


    /**
     * Add the array of columns to the table
     *
     * @note This will clear the current columns array
     *
     * @param string|array $columns
     * @param string       $after
     *
     * @return static
     */
    public function addColumns(string|array $columns, string $after): static
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
     *
     * @param string $column
     * @param string $after
     *
     * @return static
     */
    public function addColumn(string $column, string $after): static
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('No column specified'));
        }
        if (!$after) {
            throw new OutOfBoundsException(tr('No after column specified'));
        }
        $this->sql->query('ALTER TABLE `' . $this->database . '` ADD COLUMN ' . Strings::ensureEndsNotWith($column, ',') . ' ' . $after);

        return $this;
    }


    /**
     * Drop the specified column from the table
     *
     * @param string $column
     *
     * @return static
     */
    public function dropColumn(string $column): static
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('No column specified'));
        }
        $column = Strings::ensureStartsNotWith($column, '`');
        $column = Strings::ensureEndsNotWith($column, '`');
        $this->sql->query('ALTER TABLE ' . $this->database . ' DROP COLUMN `' . $column . '`');

        return $this;
    }


    /**
     * Modify the specified column from the table
     *
     * @param string $column
     * @param string $to_definition
     *
     * @return static
     */
    public function modifyColumn(string $column, string $to_definition): static
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('No column specified'));
        }
        if (!$to_definition) {
            throw new OutOfBoundsException(tr('No new column definition specified'));
        }
        $column = Strings::ensureStartsNotWith($column, '`');
        $column = Strings::ensureEndsNotWith($column, '`');
        $this->sql->query('ALTER TABLE `' . $this->database . '` MODIFY COLUMN `' . $column . '` ' . $to_definition);

        return $this;
    }


    /**
     * Change the specified column from the table
     *
     * @param string $column
     * @param string $to_definition
     *
     * @return static
     */
    public function changeColumn(string $column, string $to_definition): static
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('No column specified'));
        }
        if (!$to_definition) {
            throw new OutOfBoundsException(tr('No new column definition specified'));
        }
        $column = Strings::ensureStartsNotWith($column, '`');
        $column = Strings::ensureEndsNotWith($column, '`');
        $this->sql->query('ALTER TABLE `' . $this->database . '` CHANGE COLUMN `' . $column . '` ' . $to_definition);

        return $this;
    }


    /**
     * Rename the specified column
     *
     * @param string $from_column
     * @param string $to_column
     *
     * @return static
     */
    public function renameColumn(string $from_column, string $to_column): static
    {
        if (!$from_column) {
            throw new OutOfBoundsException(tr('No column specified'));
        }

        if (!$to_column) {
            throw new OutOfBoundsException(tr('No new column definition specified'));
        }

        $from_column = Strings::ensureStartsNotWith($from_column, '`');
        $from_column = Strings::ensureEndsNotWith($from_column, '`');
        $to_column   = Strings::ensureStartsNotWith($to_column, '`');
        $to_column   = Strings::ensureEndsNotWith($to_column, '`');

        $this->sql->query('ALTER TABLE `' . $this->database . '` RENAME COLUMN `' . $from_column . '` TO `' . $to_column . '`');

        return $this;
    }


    /**
     * Add the array of indices to the table
     *
     * @param string|array $indices
     *
     * @return static
     */
    public function addIndices(string|array $indices): static
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
     * @return static
     */
    public function addIndex(string $index): static
    {
        if ($index) {
            $this->sql->query('ALTER TABLE ' . $this->database . ' ADD ' . Strings::ensureEndsNotWith($index, ','));
        }

        return $this;
    }


    /**
     * Drop the specified index from the table
     *
     * @param string $index
     *
     * @return static
     */
    public function dropIndex(string $index): static
    {
        if ($index) {
            $this->sql->query('ALTER TABLE ' . $this->database . ' DROP KEY `' . Strings::ensureEndsNotWith(Strings::ensureStartsNotWith($index, '`'), '`') . '`');
        }

        return $this;
    }


    /**
     * Add the array of foreign_keys to the table
     *
     * @param string|array $foreign_keys
     *
     * @return static
     */
    public function addForeignKeys(string|array $foreign_keys): static
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
     * @return static
     */
    public function addForeignKey(string $foreign_key): static
    {
        if ($foreign_key) {
            $this->sql->query('ALTER TABLE ' . $this->database . ' ADD ' . $foreign_key);
        }

        return $this;
    }


    /**
     * Drop the specified foreign_key from the table
     *
     * @param string $foreign_key
     *
     * @return static
     */
    public function dropForeignKey(string $foreign_key): static
    {
        if ($foreign_key) {
            $this->sql->query('ALTER TABLE ' . $this->database . ' DROP FOREIGN KEY `' . Strings::ensureEndsNotWith(Strings::ensureStartsNotWith($foreign_key, '`'), '`') . '`');
        }

        return $this;
    }
}
