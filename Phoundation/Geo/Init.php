<?php

namespace Phoundation\Geo;



/**
 * Init class
 *
 * This is the Init class for the Geo library
 *
 * @see \Phoundation\Initialize\Init
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
 */
class Init extends \Phoundation\Initialize\Init
{
    public function __construct()
    {
        parent::__construct('0.0.1');

        $this->addUpdate('0.0.1', function (){
            // Create the geo_timezones table.
            sql()->schema()->table('geo_timezones')
                ->setColumns('  
                    `id` int NOT NULL AUTO_INCREMENT,                   
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `code` varchar(2) CHARACTER SET latin1 DEFAULT NULL,
                    `coordinates` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `utc_offset` varchar(6) CHARACTER SET latin1 NOT NULL,
                    `utc_dst_offset` varchar(6) CHARACTER SET latin1 NOT NULL,
                    `name` varchar(64) CHARACTER SET latin1 NOT NULL,
                    `seoname` varchar(64) CHARACTER SET latin1 NOT NULL,
                    `comments` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `notes` varchar(255) CHARACTER SET latin1 DEFAULT NULL,')
                ->setIndices('  
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seoname` (`seoname`),
                    KEY `coordinates` (`coordinates`),
                    KEY `utc_offset` (`utc_offset`),
                    KEY `utc_dst_offset` (`utc_dst_offset`),
                    KEY `code` (`code`),
                    KEY `status` (`status`)')
                ->create();

            // Create the geo_continents table.
            sql()->schema()->table('geo_continents')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,                   
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `modified_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `geonames_id` int NOT NULL,
                    `code` varchar(2) CHARACTER SET latin1 NOT NULL,
                    `name` varchar(32) CHARACTER SET latin1 NOT NULL,
                    `seoname` varchar(32) CHARACTER SET latin1 NOT NULL,
                    `alternate_names` varchar(4000) CHARACTER SET latin1 NOT NULL,
                    `latitude` decimal(10,7) NOT NULL,
                    `longitude` decimal(10,7) NOT NULL,
                    `timezones_id` int DEFAULT NULL,
                    `moddate` datetime DEFAULT NULL,')
                ->setIndices('  PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seoname` (`seoname`),
                    KEY `geonames_id` (`geonames_id`),
                    KEY `code` (`code`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `moddate` (`moddate`),
                    KEY `status` (`status`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_continents_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE CASCADE')
                ->create();

            // Create the geo_countries table.
            sql()->schema()->table('geo_countries')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `geonames_id` int DEFAULT NULL,
                    `continents_id` int DEFAULT NULL,
                    `timezones_id` int DEFAULT NULL,
                    `code` varchar(2) CHARACTER SET latin1 DEFAULT NULL,
                    `iso_alpha2` char(2) CHARACTER SET latin1 DEFAULT NULL,
                    `iso_alpha3` char(3) CHARACTER SET latin1 DEFAULT NULL,
                    `iso_numeric` char(3) CHARACTER SET latin1 DEFAULT NULL,
                    `fips_code` varchar(3) CHARACTER SET latin1 DEFAULT NULL,
                    `tld` varchar(2) CHARACTER SET latin1 DEFAULT NULL,
                    `currency` varchar(3) CHARACTER SET latin1 DEFAULT NULL,
                    `currency_name` varchar(20) CHARACTER SET latin1 DEFAULT NULL,
                    `phone` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
                    `postal_code_format` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
                    `postal_code_regex` varchar(255) CHARACTER SET latin1 DEFAULT NULL,
                    `languages` varchar(200) DEFAULT NULL,
                    `neighbours` varchar(100) DEFAULT NULL,
                    `equivalent_fips_code` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `alternate_names` varchar(4000) DEFAULT NULL,
                    `name` varchar(200) NOT NULL,
                    `seoname` varchar(200) NOT NULL,
                    `capital` varchar(200) DEFAULT NULL,
                    `areainsqkm` double DEFAULT NULL,
                    `population` int DEFAULT NULL,
                    `moddate` datetime DEFAULT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seoname` (`seoname`),
                    KEY `code` (`code`),
                    KEY `code_iso` (`iso_alpha2`),
                    KEY `tld` (`tld`),
                    KEY `continents_id` (`continents_id`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `iso_alpha2` (`iso_alpha2`),
                    KEY `iso_alpha3` (`iso_alpha3`),
                    KEY `iso_numeric` (`iso_numeric`),
                    KEY `fips_code` (`fips_code`),
                    KEY `capital` (`capital`),
                    KEY `areainsqkm` (`areainsqkm`),
                    KEY `population` (`population`),
                    KEY `currency` (`currency`),
                    KEY `currency_name` (`currency_name`),
                    KEY `phone` (`phone`),
                    KEY `postal_code_format` (`postal_code_format`),
                    KEY `postal_code_regex` (`postal_code_regex`),
                    KEY `languages` (`languages`),
                    KEY `neighbours` (`neighbours`),
                    KEY `equivalent_fips_code` (`equivalent_fips_code`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `moddate` (`moddate`),
                    KEY `status` (`status`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_countries_continents_id` FOREIGN KEY (`continents_id`) REFERENCES `geo_continents` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_countries_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE CASCADE')
                ->create();

            // Create the geo_states table.
            sql()->schema()->table('geo_states')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `geonames_id` int DEFAULT NULL,
                    `countries_id` int NOT NULL,
                    `country_code` varchar(2) CHARACTER SET latin1 NOT NULL,
                    `timezones_id` int DEFAULT NULL,
                    `code` varchar(2) NOT NULL,
                    `name` varchar(200) NOT NULL,
                    `seoname` varchar(200) NOT NULL,
                    `alternate_names` text,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `population` int DEFAULT NULL,
                    `elevation` int DEFAULT NULL,
                    `admin1` varchar(20) DEFAULT NULL,
                    `admin2` varchar(20) DEFAULT NULL,
                    `moddate` datetime DEFAULT NULL,
                    `filter` enum("default", "selective") CHARACTER SET latin1 NOT NULL DEFAULT "default",')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seoname` (`seoname`),
                    UNIQUE KEY `name_2` (`name`,`latitude`,`longitude`),
                    KEY `countries_id` (`countries_id`),
                    KEY `code` (`code`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `population` (`population`),
                    KEY `elevation` (`elevation`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `admin1` (`admin1`),
                    KEY `admin2` (`admin2`),
                    KEY `moddate` (`moddate`),
                    KEY `name` (`name`),
                    KEY `country_code` (`country_code`),
                    KEY `status` (`status`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_states_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_states_country_code` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_states_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE CASCADE')
                ->create();

            // Create the geo_cities table.
            sql()->schema()->table('geo_cities')
                ->setColumns('
                    `id` int NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `is_city` int DEFAULT NULL,
                    `geonames_id` int DEFAULT NULL,
                    `counties_id` int DEFAULT NULL,
                    `states_id` int NOT NULL,
                    `countries_id` int NOT NULL,
                    `country_code` varchar(2) CHARACTER SET latin1 DEFAULT NULL,
                    `name` varchar(200) NOT NULL,
                    `seoname` varchar(200) NOT NULL,
                    `alternate_names` text,
                    `alternate_country_codes` varchar(60) DEFAULT NULL,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `elevation` int DEFAULT NULL,
                    `admin1` varchar(20) DEFAULT NULL,
                    `admin2` varchar(20) DEFAULT NULL,
                    `population` int DEFAULT NULL,
                    `timezones_id` int DEFAULT NULL,
                    `timezone` varchar(64) CHARACTER SET latin1 DEFAULT NULL,
                    `feature_code` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
                    `moddate` datetime DEFAULT NULL,')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `seoname` (`states_id`,`seoname`),
                    UNIQUE KEY `name_2` (`name`,`latitude`,`longitude`,`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `geonames_id` (`geonames_id`),
                    KEY `country_code` (`country_code`),
                    KEY `longitude` (`longitude`),
                    KEY `latitude` (`latitude`),
                    KEY `population` (`population`),
                    KEY `elevation` (`elevation`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `timezone` (`timezone`),
                    KEY `feature_code` (`feature_code`),
                    KEY `is_city` (`is_city`),
                    KEY `counties_id` (`counties_id`),
                    KEY `admin1` (`admin1`),
                    KEY `admin2` (`admin2`),
                    KEY `modified_on` (`modified_on`),
                    KEY `moddate` (`moddate`),
                    KEY `name` (`name`),
                    KEY `status` (`status`),')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_cities_counties_id` FOREIGN KEY (`counties_id`) REFERENCES `geo_counties` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_cities_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_cities_country_code` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_cities_feature_code` FOREIGN KEY (`feature_code`) REFERENCES `geo_features` (`code`) ON DELETE SET NULL,
                    CONSTRAINT `fk_geo_cities_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_cities_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE SET NULL')
                ->create();
        });
    }
}
