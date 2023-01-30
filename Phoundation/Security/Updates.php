<?php

namespace Phoundation\Security;



/**
 * Updates class
 *
 * This is the Init class for the Security library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Updates extends \Phoundation\Core\Libraries\Updates
{
    /**
     * The current version for this library
     *
     * @return string
     */
    public function version(): string
    {
        return '0.0.5';
    }



    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('The security library monitors, registers and manages security incidents that happen on this system');
    }



    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.5', function () {
            // Add security incidents table
            sql()->schema()->table('security_incidents')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `type` varchar(64) NOT NULL,
                    `severity` ENUM("notice", "low", "medium", "high", "severe") NOT NULL,
                    `title` VARCHAR(255) NOT NULL,
                    `details` TEXT NULL,
                ')
                ->setIndices('                
                    PRIMARY KEY (`id`),
                    KEY `type` (`type`),
                    KEY `severity` (`severity`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_security_incidents_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT,
                ')
                ->create();
        });
    }
}
