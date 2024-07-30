<?php

/**
 * class Database
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
use Phoundation\Databases\Export;
use Phoundation\Databases\Import;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Schema\Interfaces\DatabaseInterface;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Web\Html\Components\P;

class Database extends SchemaAbstract implements DatabaseInterface
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
     * @param string $name
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
     * @param bool $use
     *
     * @return static
     */
    public function create(bool $use = true): static
    {
        if ($this->exists()) {
            throw new SqlException(tr('Cannot create database ":name", it already exists', [
                ':name' => $this->sql->getDatabase(),
            ]));
        }

        Log::action(tr('Creating database ":database"', [':database' => $this->sql->getDatabase()]));

        // This query can only partially use bound variables!
        $this->sql->query('CREATE DATABASE `' . $this->sql->getDatabase() . '` DEFAULT CHARSET=:charset COLLATE=:collate', [
            ':charset' => $this->configuration['charset'],
            ':collate' => $this->configuration['collate'],
        ]);

        if ($use) {
            $this->sql->use($this->sql->getDatabase());
        }

        return $this;
    }


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
     * Use the specified database name
     *
     * @param string $name
     *
     * @return static
     */
    protected function use(string $name): static
    {
        $this->sql->use($name);

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
            ':instance' => $this->sql->getConnector(),
            ':database' => $this->sql->getDatabase(),
        ]), 5);
        $this->sql->query('DROP DATABASE IF EXISTS `' . $this->sql->getDatabase() . '`');

        return $this;
    }


    /**
     * Access a new Table object for the currently selected database
     *
     * @param string $name
     *
     * @return Table
     */
    public function table(string $name): Table
    {
        // If we don't have this table yet, create it now
        if (!array_key_exists($name, $this->tables)) {
            $this->tables[$name] = new Table($name, $this->sql, $this);
        }

        return $this->tables[$name];
    }


    /**
     * Load the table parameters from the database
     *
     * @param bool $clear
     *
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static
    {
        // Load columns & indices data
        // TODO Implement
        return $this;
    }


    /**
     * Renames this database
     *
     * @param string $database_name
     * @return static
     *
     * @see https://www.atlassian.com/data/admin/how-to-rename-a-database-in-mysql
     */
    public function rename(string $database_name): static
    {
        $tables = $this->tables();
        $target = Database::new($database_name, $this->sql, $this->parent);

        if ($target->exists()) {
            // Target already exists
            if (!FORCE) {
                throw new OutOfBoundsException(tr('Cannot rename database ":from" to ":to", the target database ":database" already exists', [
                    ':from'     => $this->getName(),
                    ':to'       => $database_name,
                    ':database' => $database_name,
                ]));
            }

            $target->drop();
        }

        $target->create();

        foreach ($tables as $table) {
            sql()->query('RENAME TABLE `' . $this->getName() . '`.`' . $table . '` 
                                TO           `' . $database_name . '`.`' . $table . '`');
        }

        // Drop the current database
        $this->drop();

        // Return link to the new target
        return $target;
    }


    /**
     * Will copy the current database to the new name
     *
     * Database will be copied by making a complete dump of the current database, which is then imported into the new
     * database
     *
     * @param string $database_name
     * @param int $timeout
     *
     * @return static
     */
    public function copy(string $database_name, int $timeout = 3600): static
    {
        // Export current database
        $file   = FsFile::getTemporary();
        $target = Database::new($database_name, $this->sql, $this->parent);

        if ($target->exists()) {
            // Target already exists
            if (!FORCE) {
                throw new OutOfBoundsException(tr('Cannot copy database ":from" to ":to", the target database ":database" already exists', [
                    ':from'     => $this->getName(),
                    ':to'       => $database_name,
                    ':database' => $database_name,
                ]));
            }

            $target->drop();
        }

        $target->create();

        // Export the current database
        Export::new()
              ->setConnector($this->sql->getConnector())
              ->setDatabase($this->getName())
              ->setTimeout($timeout)
              ->dump($file);

        // Import dump into new database
        Import::new()
              ->setConnector($this->sql->getConnector())
              ->setDatabase($database_name)
              ->setFile($file)
              ->setTimeout($timeout)
              ->import();

        return $this;
    }
}
