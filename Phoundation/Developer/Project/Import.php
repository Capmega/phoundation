<?php

namespace Phoundation\Developer\Project;

use Phoundation\Databases\Sql\Schema\Table;
use Phoundation\Developer\Debug;
use Phoundation\Filesystem\Execute;
use Phoundation\Processes\Commands\Command;


/**
 * Import class
 *
 * This is the prototype import class that contains the basic methods for all other import classes in all other
 * libraries
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
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
     * If true, demo data will also be imported
     *
     * @var bool $demo
     */
    protected static bool $demo;

    /**
     * The amount of entries to import in case of demo
     *
     * @var int $count
     */
    protected static int $count;



    /**
     * Import class constructor
     *
     * This constructor must define the self::$table variable
     *
     * @param bool $demo
     * @param int $min
     * @param int $max
     */
    protected function __construct(bool $demo, int $min, int $max)
    {
        self::$demo  = $demo;
        self::$count = mt_rand($min, $max);
    }



    /**
     * Singleton, ensure to always return the same import object.
     *
     * @param bool $demo
     * @param int $min
     * @param int $max
     * @return static
     */
    public static function getInstance(bool $demo, int $min, int $max): static
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
     * @param bool $demo
     * @param int $min
     * @param int $max
     * @return void
     */
    public static function execute(bool $demo, int $min, int $max): void
    {
        self::getInstance($demo, $min, $max);

        // Find all Import classes
        $imports = self::findImports();

        // Execute each import class
        foreach ($imports as $import) {
            $import::execute($demo, $min, $max);
        }
    }



    /**
     * Find all Import class files and ensure they are included
     *
     * @return array
     */
    protected static function findImports(): array
    {
        $return = [];
        $files  = Command::find(PATH_ROOT, 'Import.php');

        foreach ($files as $file) {
            // Include the file
            include_once($file);

            // Get the class for this file
            $return[] = Debug::getClassPath($file);
        }

        return $return;
    }
}