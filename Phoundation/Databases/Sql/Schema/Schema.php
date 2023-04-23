<?php

namespace Phoundation\Databases\Sql\Schema;

use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;



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
class Schema
{
    /**
     * The instance configuration for this schema
     *
     * @var string|null $instance
     */
    protected ?string $instance_name = null;

    /**
     * The databases for this schema
     *
     * @var array $databases
     */
    protected array $databases = [];

    /**
     * The current database used
     *
     * @var string|null $current_database
     */
    protected ?string $current_database = null;



    /**
     * Schema constructor
     */
    public function __construct(?string $instance_name = null, bool $use_database = false)
    {
        // Check if the specified database exists
        if (!$instance_name) {
            throw new OutOfBoundsException(tr('No instance name specified'));
        }

        $this->instance_name = $instance_name;
        $this->sql = new Sql($instance_name, $use_database);
    }



    /**
     * Access a new Database object
     *
     * @param string|null $name
     * @return Database
     */
    public function database(?string $name = null): Database
    {
        if (!$name) {
            // Default to system database
            $name = Config::get('databases.sql.instances.system.name', 'phoundation');
        }

        // If we don't have this database yet, create it now
        if (!array_key_exists($name, $this->databases)) {
            $this->databases[$name] = new Database($name, $this->sql, $this);
        }

        // Set current database and return a database object
        $this->current_database = $name;
        return $this->databases[$name];
    }



    /**
     * Access a new Table object for the currently selected database
     *
     * @param string $name
     * @return Table
     */
    public function table(string $name): Table
    {
        if (!$name) {
            throw new OutOfBoundsException(tr('No table specified'));
        }

        return $this->database()->table($name);
    }



    /**
     * Returns the current database
     *
     * @return string|null
     */
    public function getCurrent(): ?string
    {
        return $this->current_database;
    }
}