<?php

namespace Phoundation\Business;



/**
 * Updates class
 *
 * This is the Init class for the Business library
 *
 * @see \Phoundation\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Business
 */
class Updates extends \Phoundation\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.8';
    }



    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('The Core library is the most basic library in the entire Phoundation framwork. It contains all the low level libraries used by all other libraries and is an essential component of your entire system. Do NOT modify!');
    }



    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.8', function () {
            // Add table for companies
            sql()->schema()->table('business_companies')->define()
                ->setColumns('

                    ')
                ->setIndices('
                
                    ')
                ->setForeignKeys('
                
                    ')
                ->create();

            // Add table for branches
            sql()->schema()->table('business_branches')->define()
                ->setColumns('

                    ')
                ->setIndices('
                
                    ')
                ->setForeignKeys('
                
                    ')
                ->create();

            // Add table for departments
            sql()->schema()->table('business_departments')->define()
                ->setColumns('

                    ')
                ->setIndices('
                
                    ')
                ->setForeignKeys('
                
                    ')
                ->create();

            // Add table for employees
            sql()->schema()->table('business_employees')->define()
                ->setColumns('

                    ')
                ->setIndices('
                
                    ')
                ->setForeignKeys('
                
                    ')
                ->create();

            // Add table for providers
            sql()->schema()->table('business_providers')->define()
                ->setColumns('

                    ')
                ->setIndices('
                
                    ')
                ->setForeignKeys('
                
                    ')
                ->create();

            // Add table for customers
            sql()->schema()->table('business_customers')->define()
                ->setColumns('

                    ')
                ->setIndices('
                
                    ')
                ->setForeignKeys('
                
                    ')
                ->create();
        });
    }
}
