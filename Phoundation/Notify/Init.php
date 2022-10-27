<?php

namespace Phoundation\Notify;



/**
 * Init class
 *
 * This is the Init class for the Notify library
 *
 * @see \Phoundation\Initialize\Init
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notify
 */
class Init extends \Phoundation\Initialize\Init
{
    public function __construct()
    {
        parent::__construct('0.0.6');

        $this->addUpdate('0.0.6', function () {
            // Add initial tables for the Notify library
            sql()->schema()->table('notify')
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
        });
    }
}
