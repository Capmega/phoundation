<?php

declare(strict_types=1);

namespace Phoundation\Developer\Project;


use Exception;

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
     * The name for this importers object
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * Import class constructor
     *
     * This constructor must define the $this->table variable
     *
     * @param bool $demo
     * @param int|null $min
     * @param int|null $max
     * @throws Exception
     */
    public function __construct(bool $demo = false, ?int $min = null, ?int $max = null)
    {
        $this->demo  = $demo;
        $this->count = random_int((int) $min, (int) $max);
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
     * @throws Exception
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

    /**
     * Returns the name for this importers object
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

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