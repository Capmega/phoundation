<?php

namespace Phoundation\Geo\GeoIp;



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
     * Import the content for the languages table from a data-source file
     *
     * @return void
     */
    public static function execute(bool $demo, int $min, int $max): void
    {
        self::getInstance($demo, $min, $max);

        // Download the GeoIP file from maxmind

        // Unzip file to temporary location

        // Import file into database

        // Remove tempfiles
    }
}