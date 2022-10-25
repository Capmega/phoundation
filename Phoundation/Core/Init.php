<?php

namespace Phoundation\Core;



/**
 * Init class
 *
 * This is the Init class for the Core library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Init
 */
class Init extends \Phoundation\Initialize\Init
{
    public function __construct()
    {
        parent::__construct('0.0.0');

        $this->addUpdate('0.0.1', function (){
                // Create the versions registration table.
                sql()->schema()->table('versions')
                    ->setColumns(
                     '`id`        INT(11)       NOT NULL AUTO_INCREMENT PRIMARY KEY,
                              `createdon` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              `class`     VARCHAR(64)   NOT NULL,
                              `version`   VARCHAR(64)   NOT NULL,
                              `comments`  VARCHAR(2048) NOT NULL')
                    ->setIndices(
                      'INDEX `createdon`     (`createdon`),
                              INDEX `class`         (`class`),
                              INDEX `version`       (`version`),
                              INDEX `class_version` (`class`, `version`)')
                    ->create();
        });
    }
}
