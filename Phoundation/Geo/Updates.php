<?php

namespace Phoundation\Geo;



/**
 * Updates class
 *
 * This is the Init class for the Geo library
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Geo
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
        return '0.0.6';
    }



    /**
     * The description for this library
     *
     * @return string
     */
    public function description(): string
    {
        return tr('The Geo library manages all geographical information for users, etc.');
    }



    /**
     * The list of version updates available for this library
     *
     * @return void
     */
    public function updates(): void
    {
        $this->addUpdate('0.0.3', function () {
            // Cleanup all
            sql()->schema()->table('geo_timezones')->drop();
            sql()->schema()->table('geo_continents')->drop();
            sql()->schema()->table('geo_countries')->drop();
            sql()->schema()->table('geo_states')->drop();
            sql()->schema()->table('geo_counties')->drop();
            sql()->schema()->table('geo_features')->drop();
            sql()->schema()->table('geo_cities')->drop();

            // Create the geo_timezones table.
            sql()->schema()->table('geo_timezones')->define()
                ->setColumns('  
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `code` varchar(2) DEFAULT NULL,
                    `coordinates` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `utc_offset` varchar(6) CHARACTER SET latin1 NOT NULL,
                    `utc_dst_offset` varchar(6) CHARACTER SET latin1 NOT NULL,
                    `name` varchar(64) NOT NULL,
                    `seo_name` varchar(64) NOT NULL,
                    `comments` varchar(255) DEFAULT NULL,
                    `notes` varchar(255) DEFAULT NULL,
                ')
                ->setIndices('  
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `coordinates` (`coordinates`),
                    KEY `utc_offset` (`utc_offset`),
                    KEY `utc_dst_offset` (`utc_dst_offset`),
                    KEY `code` (`code`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_timezones_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

            // Create the geo_continents table.
            sql()->schema()->table('geo_continents')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `geonames_id` bigint NOT NULL,
                    `code` varchar(2) NOT NULL,
                    `name` varchar(32) NOT NULL,
                    `seo_name` varchar(32) NOT NULL,
                    `alternate_names` varchar(4000) NOT NULL,
                    `latitude` decimal(10,7) NOT NULL,
                    `longitude` decimal(10,7) NOT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                ')
                ->setIndices('  
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    KEY `geonames_id` (`geonames_id`),
                    KEY `code` (`code`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `timezones_id` (`timezones_id`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_continents_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_continents_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT
                ')
                ->create();

            // Create the geo_countries table.
            sql()->schema()->table('geo_countries')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `geonames_id` bigint DEFAULT NULL,
                    `continents_id` bigint DEFAULT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `code` varchar(2) DEFAULT NULL,
                    `iso_alpha2` char(2) DEFAULT NULL,
                    `iso_alpha3` char(3) DEFAULT NULL,
                    `iso_numeric` char(3) DEFAULT NULL,
                    `fips_code` varchar(3) DEFAULT NULL,
                    `tld` varchar(2) DEFAULT NULL,
                    `currency` varchar(3) DEFAULT NULL,
                    `currency_name` varchar(20) DEFAULT NULL,
                    `phone` varchar(10) CHARACTER SET latin1 DEFAULT NULL,
                    `postal_code_format` varchar(100) DEFAULT NULL,
                    `postal_code_regex` varchar(255) DEFAULT NULL,
                    `languages` varchar(200) DEFAULT NULL,
                    `neighbours` varchar(100) DEFAULT NULL,
                    `equivalent_fips_code` varchar(10) DEFAULT NULL,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `alternate_names` varchar(4000) DEFAULT NULL,
                    `name` varchar(200) NOT NULL,
                    `seo_name` varchar(200) NOT NULL,
                    `capital` varchar(200) DEFAULT NULL,
                    `areainsqkm` double DEFAULT NULL,
                    `population` int DEFAULT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `name` (`name`),
                    UNIQUE KEY `seo_name` (`seo_name`),
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
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_countries_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_countries_continents_id` FOREIGN KEY (`continents_id`) REFERENCES `geo_continents` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_countries_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT
                ')
                ->create();

            // Create the geo_states table.
            sql()->schema()->table('geo_states')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `geonames_id` bigint DEFAULT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `continents_id` bigint DEFAULT NULL,
                    `countries_id` bigint NOT NULL,
                    `country_code` varchar(2) NOT NULL,
                    `code` varchar(2) NOT NULL,
                    `name` varchar(200) NOT NULL,
                    `seo_name` varchar(200) NOT NULL,
                    `alternate_names` text,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `population` int DEFAULT NULL,
                    `elevation` int DEFAULT NULL,
                    `admin1` varchar(20) DEFAULT NULL,
                    `admin2` varchar(20) DEFAULT NULL,
                    `filter` enum("default", "selective") CHARACTER SET latin1 NOT NULL DEFAULT "default",
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `name_2` (`name`,`latitude`,`longitude`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `continents_id` (`continents_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `code` (`code`),
                    KEY `latitude` (`latitude`),
                    KEY `longitude` (`longitude`),
                    KEY `population` (`population`),
                    KEY `elevation` (`elevation`),
                    KEY `admin1` (`admin1`),
                    KEY `admin2` (`admin2`),
                    KEY `name` (`name`),
                    KEY `country_code` (`country_code`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_states_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_states_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_states_continents_id` FOREIGN KEY (`continents_id`) REFERENCES `geo_continents` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_states_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_states_country_code` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE RESTRICT,
                ')
                ->create();

            // Create the geo_counties table.
            sql()->schema()->table('geo_counties')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `geonames_id` bigint DEFAULT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `continents_id` bigint DEFAULT NULL,
                    `countries_id` bigint NOT NULL,
                    `states_id` bigint NOT NULL,
                    `code` varchar(2),
                    `name` varchar(64),
                    `seo_name` varchar(64),
                    `alternate_names` text,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `population` int DEFAULT NULL,
                    `elevation` int DEFAULT NULL,
                    `admin1` varchar(20) DEFAULT NULL,
                    `admin2` varchar(20) DEFAULT NULL
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `seo_name` (`seo_name`),
                    UNIQUE KEY `name_2` (`name`,`latitude`,`longitude`,`countries_id`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `continents_id` (`continents_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `code` (`code`),
                    KEY `population` (`population`),
                    KEY `elevation` (`elevation`),
                    KEY `admin1` (`admin1`),
                    KEY `admin2` (`admin2`),
                    KEY `name` (`name`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_counties_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_counties_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_counties_continents_id` FOREIGN KEY (`continents_id`) REFERENCES `geo_continents` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_counties_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_counties_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                ')
                ->create();

            // Create the geo_features table.
            sql()->schema()->table('geo_features')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `code` varchar(10) NOT NULL,
                    `name` varchar(32) NOT NULL,
                    `description` varchar(4096) DEFAULT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `code` (`code`),
                    KEY `name` (`name`)
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_features_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                ')
                ->create();

            // Create the geo_cities table.
            sql()->schema()->table('geo_cities')->define()
                ->setColumns('
                    `id` bigint NOT NULL AUTO_INCREMENT,
                    `created_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `created_by` bigint DEFAULT NULL,
                    `meta_id` bigint NOT NULL,
                    `meta_state` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `status` varchar(16) CHARACTER SET latin1 DEFAULT NULL,
                    `is_city` int DEFAULT NULL,
                    `geonames_id` bigint DEFAULT NULL,
                    `timezones_id` bigint DEFAULT NULL,
                    `continents_id` bigint DEFAULT NULL,
                    `country_code` varchar(2) DEFAULT NULL,
                    `countries_id` bigint NOT NULL,
                    `states_id` bigint NOT NULL,
                    `counties_id` bigint DEFAULT NULL,
                    `name` varchar(200) NOT NULL,
                    `seo_name` varchar(200) NOT NULL,
                    `alternate_names` text,
                    `alternate_country_codes` varchar(60) DEFAULT NULL,
                    `latitude` decimal(10,7) DEFAULT NULL,
                    `longitude` decimal(10,7) DEFAULT NULL,
                    `elevation` int DEFAULT NULL,
                    `admin1` varchar(20) DEFAULT NULL,
                    `admin2` varchar(20) DEFAULT NULL,
                    `population` int DEFAULT NULL,
                    `timezone` varchar(64) DEFAULT NULL,
                    `feature_code` varchar(10) DEFAULT NULL,
                ')
                ->setIndices('
                    PRIMARY KEY (`id`),
                    KEY `created_by` (`created_by`),
                    KEY `created_on` (`created_on`),
                    KEY `meta_id` (`meta_id`),
                    KEY `status` (`status`),
                    UNIQUE KEY `seo_name` (`states_id`,`seo_name`),
                    UNIQUE KEY `name_2` (`name`,`latitude`,`longitude`,`countries_id`),
                    KEY `geonames_id` (`geonames_id`),
                    KEY `timezones_id` (`timezones_id`),
                    KEY `continents_id` (`continents_id`),
                    KEY `countries_id` (`countries_id`),
                    KEY `states_id` (`states_id`),
                    KEY `counties_id` (`counties_id`),
                    KEY `country_code` (`country_code`),
                    KEY `longitude` (`longitude`),
                    KEY `latitude` (`latitude`),
                    KEY `population` (`population`),
                    KEY `elevation` (`elevation`),
                    KEY `timezone` (`timezone`),
                    KEY `feature_code` (`feature_code`),
                    KEY `is_city` (`is_city`),
                    KEY `admin1` (`admin1`),
                    KEY `admin2` (`admin2`),
                    KEY `name` (`name`),
                ')
                ->setForeignKeys('
                    CONSTRAINT `fk_geo_cities_meta_id` FOREIGN KEY (`meta_id`) REFERENCES `meta` (`id`) ON DELETE CASCADE,
                    CONSTRAINT `fk_geo_cities_timezones_id` FOREIGN KEY (`timezones_id`) REFERENCES `geo_timezones` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_cities_continents_id` FOREIGN KEY (`continents_id`) REFERENCES `geo_continents` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_cities_countries_id` FOREIGN KEY (`countries_id`) REFERENCES `geo_countries` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_cities_states_id` FOREIGN KEY (`states_id`) REFERENCES `geo_states` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_cities_counties_id` FOREIGN KEY (`counties_id`) REFERENCES `geo_counties` (`id`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_cities_country_code` FOREIGN KEY (`country_code`) REFERENCES `geo_countries` (`code`) ON DELETE RESTRICT,
                    CONSTRAINT `fk_geo_cities_feature_code` FOREIGN KEY (`feature_code`) REFERENCES `geo_features` (`code`) ON DELETE RESTRICT,
                ')
                ->create();

        })->addUpdate('0.0.6', function () {
            // Add missing "created_by" columns with indices and FKS
            $tables = [
                'geo_timezones',
                'geo_continents',
                'geo_countries',
                'geo_states',
                'geo_counties',
                'geo_features',
                'geo_cities',
            ];

            foreach ($tables as $table) {
                sql()->schema()->table($table)->alter()->addForeignKey('
                    CONSTRAINT `fk_' . $table . '_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts_users` (`id`) ON DELETE RESTRICT
                ');
            }
        });
    }
}
