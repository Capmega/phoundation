<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Log\Log;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Exception\UnderConstructionException;


/**
 * class Database
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Database extends SchemaAbstract
{
    /**
     * The columns for this database
     *
     * @var array $columns
     */
    protected array $columns = [];

    /**
     * The indices for this database
     *
     * @var array $indices
     */
    protected array $indices = [];

    /**
     * The tables for this schema
     *
     * @var array $tables
     */
    protected array $tables = [];


    /**
     * Returns if the database exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool
    {
        // If this query returns nothing, the database does not exist. If it returns anything, it does exist.
        return (bool) sql()->get('SHOW DATABASES LIKE :name', [':name' => $this->sql->getDatabase()]);
    }


    /**
     * Returns the database name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->sql->getDatabase();
    }


    /**
     * Sets the database name
     *
     * This will effectively rename the database. Since MySQL does not support renaming operations, this requires
     * dumping the entire database and importing it under the new name and dropping the original. Depending on your
     * database size, this may take a while!
     *
     * @return static
     */
    public function setName(string $name): static
    {
        throw new UnderConstructionException();
    }


    /**
     * Create this database
     *
     * @return static
     */
    public function create(): static
    {
        if ($this->exists()) {
            throw new SqlException(tr('Cannot create database ":name", it already exists', [':name' => $this->sql->getDatabase()]));
        }

        Log::action(tr('Creating database ":database"', [':database' => $this->sql->getDatabase()]));

        // This query can only partially use bound variables!
        $this->sql->query('CREATE DATABASE `' . $this->sql->getDatabase() . '` DEFAULT CHARSET=:charset COLLATE=:collate', [
            ':charset' => $this->configuration['charset'],
            ':collate' => $this->configuration['collate']
        ]);

        $this->sql->use($this->sql->getDatabase());
        return $this;
    }


    /**
     * Drop this database
     *
     * @return static
     */
    public function drop(): static
    {
        // This query cannot use bound variables!
        Log::warning(tr('Dropping database ":database" for SQL instance ":instance"', [
            ':instance' => $this->sql->getInstance(),
            ':database' => $this->sql->getDatabase()
        ]), 3);

        $this->sql->query('DROP DATABASE IF EXISTS `' . $this->sql->getDatabase() . '`');
        return $this;
    }


    /**
     * Use the specified database name
     *
     * @param string $name
     * @return static
     */
    protected function use(string $name): static
    {
        $this->sql->use($name);
        return $this;
    }


    /**
     * Access a new Table object for the currently selected database
     *
     * @param string $name
     * @return Table
     */
    public function table(string $name): Table
    {
        // If we don't have this table yet, create it now
        if (!array_key_exists($name, $this->tables)) {
            $this->tables[$name] = new Table( $name, $this->sql, $this);
        }

        return $this->tables[$name];
    }


    /**
     * Load the table parameters from database
     *
     * @return static
     */
    public function load(): static
    {
        // Load columns & indices data
        // TODO Implement
        return $this;
    }


    /**
     * Renames this database
     *
     * @see https://www.atlassian.com/data/admin/how-to-rename-a-database-in-mysql
     * @return $this
     */
    public function rename(): static
    {
        $tables = $this->tables();
        //$ mysql -u dbUsername -p"dbPassword" oldDatabase -sNe 'show tables' | while read table; do mysql -u dbUsername -p"dbPassword" -sNe "RENAME TABLE oldDatabase.$table TO newDatabase.$table"; done
        return $this;
    }
}
