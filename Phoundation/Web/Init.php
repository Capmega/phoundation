<?php

namespace Phoundation\Web;



/**
 * Init class
 *
 * This is the Init class for the Web library
 *
 * @see \Phoundation\Initialize\Init
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Init extends \Phoundation\Initialize\Init
{
    public function __construct()
    {
        parent::__construct('0.0.5');

        $this->addUpdate('0.0.5', function () {
            // Add tables for the sessions management
            sql()->schema()->table('sessions_extended')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `users_id` int NOT NULL,
                    `session_key` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
                    `ip` int NOT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `session_key` (`session_key`),
                    KEY `created_on` (`created_on`),
                    KEY `users_id` (`users_id`),
                    KEY `ip` (`ip`)')
                ->setForeignKeys('
                    CONSTRAINT `fk_sessions_extended_users_id` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE')
                ->create();
        });
    }
}
