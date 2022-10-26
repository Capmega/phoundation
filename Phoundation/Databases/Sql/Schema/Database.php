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
     * @param string|null $name
     */
    public function __construct(Sql $sql, ?string $name = null)
    {
        $this->sql = $sql;

        if ($name) {
            // Use the specified database.
            $this->sql->use($name);
        }

    }



    /**
     * Returns if the database exists in the database or not
     *
     * @return bool
     */
    public function exists(): bool
    {
        // If this query returns nothing, the database does not exist. If it returns anything, it does exist.
        sql()->query('SHOW TABLES LIKE :name', [':name' => $this->name]);
        die('aaaaaaaaaaa');
        return (bool) sql()->query('SHOW TABLES LIKE :name', [':name' => $this->name]);
    }



    /**
     * Returns the database name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }



    /**
     * Sets the database name
     *
     * @param string $name
     * @return Database
     */
    public function setName(string $name): Database
    {
        $this->name = $name;
        return $this;
    }



    /**
     * Returns the database columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }



    /**
     * Sets the database columns
     *
     * @note This will clear the current columns array
     * @param string|array $columns
     * @return Database
     */
    public function setColumns(string|array $columns): Database
    {
        $this->columns = [];
        return $this->addColumns($columns);
    }



    /**
     * Add the array of columns to the database
     *
     * @note This will clear the current columns array
     * @param string|array $columns
     * @return Database
     */
    public function addColumns(string|array $columns): Database
    {
        foreach (Arrays::force($columns) as $column) {
            $this->addColumn($column);
        }

        return $this;
    }



    /**
     * Add a single column to the database
     *
     * @note This will clear the current columns array
     * @param string $column
     * @return Database
     */
    public function addColumn(string $column): Database
    {
        $this->columns[] = $column;
        return $this;
    }



    /**
     * Returns the database indices
     *
     * @return array
     */
    public function getIndices(): array
    {
        return $this->indices;
    }



    /**
     * Sets the database indices
     *
     * @note This will clear the current indices array
     * @param string|array $indices
     * @return Database
     */
    public function setIndices(string|array $indices): Database
    {
        $this->indices = [];
        return $this->addIndices($indices);
    }



    /**
     * Add the array of indices to the database
     *
     * @note This will clear the current indices array
     * @param string|array $indices
     * @return Database
     */
    public function addIndices(string|array $indices): Database
    {
        foreach (Arrays::force($indices) as $index) {
            $this->addIndex($index);
        }

        return $this;
    }



    /**
     * Add a single index to the database
     *
     * @note This will clear the current indices array
     * @param string $index
     * @return Database
     */
    public function addIndex(string $index): Database
    {
        $this->indices[] = $index;
        return $this;
    }



    /**
     * Create the specified database
     *
     * @return void
     */
    public function create(): void
    {
        if ($this->exists()) {
            throw new SqlException(tr('Cannot create database ":name", it already exists', [':name' => $this->name]));
        }

        sql()->query($this->getCreateQuery());
    }



    /**
     * Create the SQL query to create the database in the database
     *
     * @return string
     */
    protected function getCreateQuery(): string
    {
        sql()->query('DROP   DATABASE IF EXISTS `' . Config::get('databases.sql.instances.system.name').'`');
        sql()->query('CREATE DATABASE           `' . Config::get('databases.sql.instances.system.name').'` DEFAULT CHARSET="' . $_CONFIG['db']['core']['charset'].'" COLLATE="' . $_CONFIG['db']['core']['collate'].'";');
        sql()->query('USE                       `' . Config::get('databases.sql.instances.system.name').'`');


        $query   = 'CREATE TABLE `' . $this->name . '` (';
        $query  .= implode(",\n", $this->columns);
        $query  .= implode("\n", $this->columns);
        $query  .= ') ENGINE=InnoDB AUTO_INCREMENT = ' . Config::get('databases.sql.instances.system.auto-increment', 1) . ' DEFAULT CHARSET="' . Config::get('databases.sql.instances.system.charset', 'utf8mb4') . '" COLLATE="' . Config::get('databases.sql.instances.system.collate', 'utf8mb4_general_ci') . '";';

        return $query;
    }



    /**
     * @param string $name
     * @return void
     */
    protected function use(string $name): void
    {
        $this->name = $name;

        // Load columns & indices data
        // TODO Implement
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