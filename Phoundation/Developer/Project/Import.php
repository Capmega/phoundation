<?php

namespace Phoundation\Developer\Project;



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
     * If true, demo data will also be imported
     *
     * @var bool $demo
     */
    protected bool $demo;

    /**
     * The amount of entries to import in case of demo
     *
     * @var int $count
     */
    protected int $count;



    /**
     * Import class constructor
     *
     * This constructor must define the $this->table variable
     *
     * @param bool $demo
     * @param int $min
     * @param int $max
     */
    public function __construct(bool $demo, int $min, int $max)
    {
        $this->demo  = $demo;
        $this->count = mt_rand($min, $max);
    }



    /**
     * Returns a new Import object
     *
     * This constructor must define the $this->table variable
     *
     * @param bool $demo
     * @param int $min
     * @param int $max
     * @return static
     */
    public static function new(bool $demo, int $min, int $max): static
    {
        return new static($demo, $min, $max);
    }



    /**
     * Execute the import function
     *
     * @return int
     */
    abstract public function execute(): int;



//    /**
//     * Execute the data import
//     *
//     * @param bool $demo
//     * @param int $min
//     * @param int $max
//     * @return void
//     */
//    public function execute(bool $demo, int $min, int $max): void
//    {
//        static::getInstance($demo, $min, $max);
//
//        // Find all Import classes
//        $imports = static::findImports();
//
//        // Execute each import class
//        foreach ($imports as $import) {
//            $import::execute($demo, $min, $max);
//        }
//    }
//
//
//
//    /**
//     * Find all Import class files and ensure they are included
//     *
//     * @return array
//     */
//    protected function findImports(): array
//    {
//        $return = [];
//        $files  = Command::find(PATH_ROOT, 'Import.php');
//
//        foreach ($files as $file) {
//            // Include the file
//            include_once($file);
//
//            // Get the class for this file
//            $return[] = Library::getClassPath($file);
//        }
//
//        return $return;
//    }
}