<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Sql;



/**
 * Table class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Table extends SchemaAbstract
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
     * Table constructor
     *
     * @param string $name
     * @param Sql $sql
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
     * Returns the table name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }



    /**
     * Returns the table columns
     *
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }



    /**
     * Returns the table indices
     *
     * @return array
     */
    public function getIndices(): array
    {
        return $this->indices;
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



    /**
     * Load the table parameters from database
     *
     * @return void
     */
    protected function load(): void
    {
        // Load columns & indices data
        // TODO Implement
    }
}