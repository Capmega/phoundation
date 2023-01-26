<?php

namespace Phoundation\Business\Customers;

use Phoundation\Developer\TestDataGenerator;



/**
 * Importer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class Import extends \Phoundation\Developer\Project\Import
{
    /**
     * Import constructor
     */
    protected function __construct(bool $demo, int $min, int $max)
    {
        parent::__construct($demo, $min, $max);
    }



    /**
     * Import the content for the languages table from a data-source file
     *
     * @param bool $demo
     * @param int $min
     * @param int $max
     * @return void
     */
    public static function execute(bool $demo, int $min, int $max): void
    {
        self::getInstance($demo, $min, $max);

        if ($demo) {
            for ($count = 1; $count <= self::$count; $count++) {
                // Add customer
                Customer::new()
                    ->setCode(TestDataGenerator::code())
                    ->setName(TestDataGenerator::name())
                    ->setDescription(TestDataGenerator::description())
                    ->save();
            }
        }
    }
}