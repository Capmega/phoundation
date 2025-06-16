<?php

/**
 * TableAlter class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Databases\Sql\Exception\SqlDefinitionNotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


class TableAlter extends SchemaAbstract
{
    /**
     * Sets the table name
     *
     * @param string|null $name
     *
     * @return static
     */
    public function setName(?string $name): static
    {
        if (empty($name)) {
            throw new OutOfBoundsException(tr('Cannot set name of table ":table" to ":name", no name specified', [
                ':table' => $this->name,
                ':name'  => $name
            ]));
        }

        $this->sql->query('RENAME TABLE :from TO :to', [
            ':from' => $this->name,
            ':tp'   => $name,
        ]);

        $this->name = $name;

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
     * @param string $before_after
     *
     * @return static
     */
    public function addColumn(string $column, string $before_after): static
    {
        if (!$column) {
            throw new OutOfBoundsException(tr('No column specified'));
        }

        if (!$before_after) {
            throw new OutOfBoundsException(tr('No after column specified'));
        }

        $column = trim($column);
        $column = Strings::ensureEndsNotWith($column, ',');

        $this->sql->query('ALTER TABLE `' . $this->name . '` 
                           ADD COLUMN ' . $column . ' ' . $before_after);

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

        $this->sql->query('ALTER TABLE ' . $this->name . ' DROP COLUMN `' . $column . '`');

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

        $column        = Strings::ensureStartsNotWith($column       , '`');
        $column        = Strings::ensureEndsNotWith($column         , '`');
        $to_definition = Strings::ensureEndsNotWith($to_definition  , ',');

        $this->sql->query('ALTER TABLE `' . $this->name . '` MODIFY COLUMN `' . $column . '` ' . $to_definition);

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

        $column        = Strings::ensureStartsNotWith($column       , '`');
        $column        = Strings::ensureEndsNotWith($column         , '`');
        $to_definition = Strings::ensureEndsNotWith($to_definition  , ',');

        $this->sql->query('ALTER TABLE `' . $this->name . '` CHANGE COLUMN `' . $column . '` ' . $to_definition);

        return $this;
    }


    /**
     * Rename the specified column
     *
     * @param string $from_name
     * @param string $to_name
     * @param bool   $rename_index If this option is true, and the table contains an index with the same column name,
     *                           the index with the old name will be removed, and an index with the new name will be
     *                           created
     *
     * @return static
     */
    public function renameColumn(string $from_name, string $to_name, bool $rename_index = true): static
    {
        if (!$from_name) {
            throw new OutOfBoundsException(tr('No column specified'));
        }

        if (!$to_name) {
            throw new OutOfBoundsException(tr('No new column definition specified'));
        }

        $from_name = Strings::ensureStartsNotWith($from_name, '`');
        $from_name = Strings::ensureEndsNotWith($from_name, '`');
        $to_name   = Strings::ensureStartsNotWith($to_name, '`');
        $to_name   = Strings::ensureEndsNotWith($to_name, '`');

        $this->sql->query('ALTER TABLE `' . $this->name . '` RENAME COLUMN `' . $from_name . '` TO `' . $to_name . '`');

        if ($rename_index) {
            if ($this->parent->indexExists($from_name)) {
                $this->renameIndex($from_name, $to_name);
            }
        }

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
     * Returns an array with all table definitions
     *
     * @return array
     */
    public function getDefinitions(): array
    {
        return explode(PHP_EOL, $this->sql->getColumn('SHOW CREATE TABLE ' . $this->name, column: 'create table'));
    }


    /**
     * Returns the definition for the specified column
     *
     * @param string      $column
     * @param string|null $filter_extra
     *
     * @return string
     */
    public function getDefinition(string $column, ?string $filter_extra = null): string
    {
        foreach($this->getDefinitions() as $line) {
            if (str_contains($line, $column)) {
                if ($filter_extra) {
                    if (!str_contains($line, $filter_extra)) {
                        continue;
                    }
                }

                // Found a matching line. clean it up, and return it
                $line = trim($line);
                return Strings::ensureEndsNotWith($line, ',') ;
            }
        }

        throw SqlDefinitionNotExistsException::new(tr('No definition found for column ":column" with extra filter ":filter"', [
            ':column' => $column,
            ':filter' => $filter_extra,
        ]));
    }


    /**
     * Renames the index with the specified name
     *
     * @param string $from_name
     * @param string $to_name
     *
     * @return static
     */
    public function renameIndex(string $from_name, string $to_name): static
    {
        $o_definition = $this->getDefinition($from_name, 'KEY');
        $o_definition = str_replace($to_name    , '##########', $o_definition);
        $o_definition = str_replace($from_name  , $to_name    , $o_definition);
        $o_definition = str_replace('##########', $to_name    , $o_definition);

        $this->sql->query('ALTER TABLE ' . $this->name . ' DROP KEY `' . $from_name . '`');
        $this->sql->query('ALTER TABLE ' . $this->name . ' ADD ' . $o_definition);

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
            $index = trim($index);
            $index = Strings::ensureEndsNotWith($index, ',');

            $this->sql->query('ALTER TABLE ' . $this->name . ' 
                               ADD         ' . $index);
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
            $this->sql->query('ALTER TABLE ' . $this->name . ' 
                               DROP KEY   `' . Strings::ensureEndsNotWith(Strings::ensureStartsNotWith($index, '`'), '`') . '`');
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
            $foreign_key = trim($foreign_key);
            $foreign_key = Strings::ensureEndsNotWith($foreign_key, ',');

            $this->sql->query('ALTER TABLE ' . $this->name . ' ADD ' . $foreign_key);
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
            $this->sql->query('ALTER TABLE ' . $this->name . ' DROP FOREIGN KEY `' . Strings::cut($foreign_key, '`', '`', false) . '`');
        }

        return $this;
    }
}
