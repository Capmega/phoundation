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
     * Table constructor
     *
     * @param string                $database
     * @param Sql                   $sql
     * @param SchemaAbstract|Schema $parent
     */
    public function __construct(string $database, Sql $sql, SchemaAbstract|Schema $parent)
    {
        parent::__construct($database, $sql, $parent);

        if ($database) {
            // Load this table
            $this->load($database);
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
        return new TableDefine($this->database, $this->sql, $this);
    }


    /**
     * Define and create the table
     *
     * @return TableAlter
     */
    public function alter(): TableAlter
    {
        return new TableAlter($this->database, $this->sql, $this);
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
        sql()->query('RENAME TABLE `' . $this->database . '` TO `' . $table_name . '`');
    }


    /**
     * Returns if the table exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool
    {
        // If this query returns nothing, the table does not exist. If it returns anything, it does exist.
        return (bool) sql()->get('SHOW TABLES LIKE :name', [':name' => $this->database]);
    }


    /**
     * Will drop this table
     *
     * @return static
     */
    public function drop(): static
    {
        Log::warning(tr('Dropping table ":table" in database ":database" for SQL instance ":instance"', [
            ':table'    => $this->database,
            ':instance' => $this->sql->getConnector(),
            ':database' => $this->sql->getDatabase(),
        ]), 3);

        sql()->query('DROP TABLES IF EXISTS `' . $this->database . '`');

        return $this;
    }


    /**
     * Will truncate this table
     *
     * @return void
     */
    public function truncate(): void
    {
        Log::warning(tr('Truncating table :table', [':table' => $this->database]));
        sql()->query('TRUNCATE `' . $this->database . '`');
    }


    /**
     * Returns the number of records in this table
     *
     * @return int
     */
    public function getCount(): int
    {
        return sql()->getInteger('SELECT COUNT(*) as `count` FROM `' . $this->database . '`');
    }


    /**
     * Returns the table name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->database;
    }


    /**
     * Returns true if the specified column exists in this table
     *
     * @param string $column
     *
     * @return bool
     */
    public function columnExists(string $column): bool
    {
        return $this->getColumns()->keyExists($column);
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
            $results = sql()->listKeyValues('DESCRIBE `' . $this->database . '`');

            foreach ($results as $result) {
                $columns[$result['field']] = Arrays::lowercaseKeys($result);
            }

            $this->columns = $columns;
        }

        return Iterator::new($this->columns);
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
}
