<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
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
class Database
{
    /**
     * The database name
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
     * The SQL configuration
     *
     * @var array $configuration
     */
    protected array $configuration;

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
     * Database constructor
     *
     * @param Sql $sql
     */
    public function __construct(Sql $sql)
    {
        $this->sql           = $sql;
        $this->configuration = $sql->getConfiguration();
   }



    /**
     * Returns if the database exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool
    {
        // If this query returns nothing, the database does not exist. If it returns anything, it does exist.
        return (bool) sql()->get(' SHOW DATABASES LIKE :name', [':name' => $this->sql->getDatabase()]);
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
     * Create this database
     *
     * @return void
     */
    public function create(): void
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
    }



    /**
     * Drop this database
     *
     * @return void
     */
    public function drop(): void
    {
        // This query cannot use bound variables!
        $this->sql->query('DROP DATABASE IF EXISTS `' . $this->sql->getDatabase() . '`');
    }



    /**
     * Use the specified database name
     *
     * @param string $name
     * @return void
     */
    protected function use(string $name): void
    {
        $this->sql->use($name);
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
            $this->tables[$name] = new Table($this->sql, $name);
        }

        return $this->tables[$name];
    }
}