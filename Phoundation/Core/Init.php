<?php

namespace Phoundation\Core;



/**
 * Init class
 *
 * This is the Init class for the Core library
 *
 * @see \Phoundation\Initialize\Init
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Init extends \Phoundation\Initialize\Init
{
    public function __construct()
    {
        parent::__construct('0.0.5');

        $this->addUpdate('0.0.1', function () {
            // Add table for version control itself
            sql()->schema()->table('versions')
                ->setColumns(
                    '`id`        INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                          `created_on` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                          `library`   VARCHAR(64)   NOT NULL,
                          `version`   VARCHAR(64)   NOT NULL,
                          `comments`  VARCHAR(2048)     NULL')
                ->setIndices(
                    'INDEX `created_on`       (`created_on`),
                          INDEX `library`         (`library`),
                          INDEX `version`         (`version`),
                          INDEX `library_version` (`library`, `version`)')
                ->create();
        })->addUpdate('0.0.2', function () {
            // Add tables for the meta library
            sql()->schema()->table('meta')
                ->setColumns('`id` int NOT NULL AUTO_INCREMENT')
                ->setIndices('PRIMARY KEY (`id`)')
                ->create();

            sql()->schema()->table('meta_history')
                ->setColumns('
                  `id` int NOT NULL AUTO_INCREMENT,
                  `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `created_by` int DEFAULT NULL,
                  `meta_id` int DEFAULT NULL,
                  `action` varchar(16) DEFAULT NULL,
                  `data` varchar(64) DEFAULT NULL')
                ->setIndices('
                  PRIMARY KEY (`id`),
                  KEY `created_on` (`created_on`),
                  KEY `created_by` (`created_by`),
                  KEY `action` (`action`),
                  KEY `fk_meta_history_id` (`meta_id`)')
                ->setForeignKeys('
                  CONSTRAINT `fk_meta_history_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE')
                ->create();
        });
    }
}
