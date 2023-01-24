<?php

namespace Phoundation\System\Project;

use Phoundation\Databases\Sql\Schema\Table;



/**
 * Import class
 *
 * This is the prototype import class that contains the basic methods for all other import classes in all other
 * libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\System
 */
abstract class Import
{
    /**
     * Singleton variable
     *
     * @var Import $table
     */
    protected static Import $instance;

    /**
     * The table to which the data will be imported
     *
     * @var string $table
     */
    protected static string $table;



    /**
     * Import class constructor
     *
     * This constructor must define the self::$table variable
     */
    abstract protected function __construct();



    /**
     * Singleton, ensure to always return the same import object.
     *
     * @return static
     */
    public static function getInstance(): static
    {
        if (!isset(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }



    /**
     * Returns a Table schema object to work with
     *
     * @return Table
     */
    protected static function getTable(): Table
    {
        return sql()->schema()->table(self::$table);
    }



    /**
     * Execute the data import
     *
     * @return void
     */
    public static function execute(): void
    {
        self::getInstance();
    }
}