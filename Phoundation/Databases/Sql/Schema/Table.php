<?php

/**
 * Table class
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
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Databases\Sql\Schema\Interfaces\TableInterface;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;


class Table extends SchemaAbstract implements TableInterface
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
    protected array $columns;

    /**
     * The foreign keys for this table
     *
     * @var array $foreign_keys
     */
    protected array $foreign_keys = [];

    /**
     * The indices for this table
     *
     * @var array $indices
     */
    protected array $indices = [];


    /**
     * Table constructor
     *
     * @param string                $name
     * @param Sql                   $sql
     * @param SchemaAbstract|Schema $parent
     */
    public function __construct(string $name, Sql $sql, SchemaAbstract|Schema $parent)
    {
        parent::__construct($name, $sql, $parent);

        if ($name) {
            // Load this table
            $this->load($name);
        }
    }


    /**
     * Load the table parameters from the database
     *
     * @return void
     */
    protected function load(): void
    {
        // Load columns & indices data
        // TODO Implement
    }


    /**
     * Define and create the table
     *
     * @return TableDefine
     */
    public function define(): TableDefine
    {
        return new TableDefine($this->name, $this->sql, $this);
    }


    /**
     * Define and create the table
     *
     * @return TableAlter
     */
    public function alter(): TableAlter
    {
        return new TableAlter($this->name, $this->sql, $this);
    }


    /**
     * Renames this table
     *
     * @param string $table_name
     *
     * @return void
     */
    public function rename(string $table_name): void
    {
        sql()->query('RENAME TABLE `' . $this->name . '` TO `' . $table_name . '`');
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
     * Will drop this table
     *
     * @return static
     */
    public function drop(): static
    {
        Log::warning(tr('Dropping table ":table" in database ":database" for SQL instance ":instance"', [
            ':table'    => $this->name,
            ':instance' => $this->sql->getConnector(),
            ':database' => $this->sql->getDatabase(),
        ]), 3);

        sql()->query('DROP TABLES IF EXISTS `' . $this->name . '`');

        return $this;
    }


    /**
     * Will truncate this table
     *
     * @return void
     */
    public function truncate(): void
    {
        Log::warning(tr('Truncating table :table', [':table' => $this->name]));
        sql()->query('TRUNCATE `' . $this->name . '`');
    }


    /**
     * Returns the number of records in this table
     *
     * @return int
     */
    public function getCount(): int
    {
        return sql()->getInteger('SELECT COUNT(*) as `count` FROM `' . $this->name . '`');
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
     * Returns true if the specified column exists in this table
     *
     * @param string $column
     * @param bool   $cache
     *
     * @return bool
     */
    public function columnExists(string $column, bool $cache = true): bool
    {
        return $this->getColumns($cache)->keyExists($column);
    }


    /**
     * Returns the table columns
     *
     * @param bool $cache
     *
     * @return IteratorInterface
     */
    public function getColumns(bool $cache = true): IteratorInterface
    {
        if (!$cache) {
            unset($this->columns);
        }

        if (empty($this->columns)) {
            $columns = [];
            $results = sql()->listKeyValues('DESCRIBE `' . $this->name . '`');

            foreach ($results as $result) {
                $columns[$result['field']] = Arrays::lowercaseKeys($result);
            }

            $this->columns = $columns;
        }

        return Iterator::new($this->columns);
    }


    /**
     * Returns true if the specified foreign key exists in this table
     *
     * @param string $key
     * @param bool   $cache
     *
     * @return bool
     */
    public function foreignKeyExists(string $key, bool $cache = true): bool
    {
        return $this->getForeignKeys($cache)->keyExists($key);
    }


    /**
     * Returns the table foreign_keys
     *
     * @param bool $cache
     *
     * @return IteratorInterface
     */
    public function getForeignKeys(bool $cache = true): IteratorInterface
    {
        if (!$cache) {
            unset($this->foreign_keys);
        }

        if (empty($this->foreign_keys)) {
            $results = sql()->listKeyValues('SHOW CREATE TABLE `' . $this->name . '`');
            $results = $results[$this->name]['create table'];

            // Parse all foreign keys from the resulting query
            do {
                $foreign_key = Strings::cut($results, 'CONSTRAINT ', ',');

                if (!$foreign_key) {
                    break;
                }

                preg_match_all('/`?([a-z_]+)`?\s+FOREIGN\s+KEY\s+\(`?([a-z_]+)`?\)\s+REFERENCES\s+`?([a-z_]+)`?\s+\(`?([a-z_]+)`?\)/i', $foreign_key, $matches);

                $this->foreign_keys[$matches[1][0]] = [
                    'key'              => $matches[1][0],
                    'column'           => $matches[2][0],
                    'reference_table'  => $matches[3][0],
                    'reference_column' => $matches[4][0]
                ];

                $results = Strings::from($results, 'CONSTRAINT ');
                $results = Strings::from($results, ',');

            } while (true);
        }

        return Iterator::new($this->foreign_keys);
    }


    /**
     * Returns true if the specified index exists in this table
     *
     * @param string $key
     * @param bool   $cache
     *
     * @return bool
     */
    public function indexExists(string $key, bool $cache = true): bool
    {
        return $this->getIndices($cache)->keyExists($key);
    }


    /**
     * Returns the table indices
     *
     * @param bool $cache
     *
     * @return IteratorInterface
     */
    public function getIndices(bool $cache = true): IteratorInterface
    {
        if (!$cache) {
            unset($this->indices);
        }

        if (empty($this->indices)) {
            $indices = [];
            $results = sql()->listKeyValues('DESCRIBE `' . $this->name . '`');

            foreach ($results as $result) {
                if ($result['key']) {
                    $indices[$result['field']] = strtolower($result['key']);
                }
            }

            $this->indices = $indices;
        }

        return Iterator::new($this->indices);
    }
}
