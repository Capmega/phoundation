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
     * Import the content for the business_customers table
     *
     * @return int
     */
    public function execute(): int
    {
        $count = 0;

        if ($this->demo) {
            for ($count = 1; $count <= $this->count; $count++) {
                // Add customer
                Customer::new()
                    ->setCode(TestDataGenerator::code())
                    ->setName(TestDataGenerator::name())
                    ->setDescription(TestDataGenerator::description())
                    ->save();
            }
        }

        return $count;
    }
}