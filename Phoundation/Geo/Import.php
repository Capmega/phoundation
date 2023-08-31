<?php

declare(strict_types=1);

namespace Phoundation\Geo;

use PDO;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Meta\Meta;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Filesystem;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Path;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Processes\Commands\Wget;
use Stringable;
use Throwable;


/**
 * Import class
 *
 *
 * @note See http://download.geonames.org/export/dump/readme.txt
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Geo
 */
class Import extends \Phoundation\Developer\Project\Import
{
    /**
     * Import class constructor
     *
     * @param bool $demo
     * @param int|null $min
     * @param int|null $max
     */
    public function __construct(bool $demo = false, ?int $min = null, ?int $max = null)
    {
        parent::__construct($demo, $min, $max);
        $this->name = 'Geo / GeoNames';
    }

    public function execute(): int
    {
        // TODO: Implement execute() method.
        return -1;
    }


    /**
     * Download the GeoIP files
     *
     * @note Using this functionality requires an account on https://www.maxmind.com/
     *
     * @note Using this functionality requires that you have an API key configured on the page
     *       https://www.maxmind.com/en/accounts/YOUR_ACCOUNT_ID/license-key and configured in the configuration path
     *       geo.ip.max-mind.api-key
     *
     * @param string|null $path
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return Path
     */
    public static function download(string $path = null, RestrictionsInterface|array|string|null $restrictions = null): Path
    {
        // Default restrictions are default path writable
        $path         = $path ?? PATH_DATA . 'sources/geo';
        $restrictions = $restrictions ?? new Restrictions(PATH_DATA . 'sources/geo', true);

        // Ensure target path can be written and is non-existent
        $path = Path::new($path, $restrictions)
            ->ensureWritable()
            ->delete();

        $wget     = Wget::new();
        $tmp_path = $wget->setExecutionPathToTemp()->getExecutionPath();

        Log::action(tr('Downloading Geo files to temporary path ":path"', [':path' => $tmp_path]));

        foreach (static::getGeoNamesFiles() as $file => $data) {
            Log::action(tr('Downloading GeoNames URL ":url"', [':url' => $data['url']]));

            // Set timeout to two hours for this download as the file is hundreds of megabytes. Depending on internet
            // connection, this can take anywhere from seconds to minutes to an hour
            $wget->getProcess()->setTimeout(7200);
            $wget->setSource($data['url'])
                 ->setTarget($file)
                 ->execute();
        }

        Log::action(tr('Moving Geo files to target path ":path"', [':path' => $path]));
        $tmp_path->move($path);

        return $path;
    }


    /**
     * Process downloaded Geo files
     *
     * @param Stringable|string $source_path
     * @param Stringable|string|null $target_path
     * @return string
     */
    public static function process(Stringable|string $source_path, Stringable|string|null $target_path = null, RestrictionsInterface|array|string|null $restrictions = null): string
    {
        // Determine what target path to use
        $restrictions = $restrictions ?? Restrictions::new(PATH_DATA, true);
        $target_path  = Config::getString('geo.geonames.path', PATH_DATA . 'sources/geo/geonames/', $target_path);
        $target_path  = Filesystem::absolute($target_path, PATH_ROOT, false);

        Path::new($target_path, $restrictions)->ensure();
        Log::action(tr('Processing GeoNames Geo files and moving to path ":path"', [':path' => $target_path]));

        try {
            // Clean source path GeoLite2 directories and garbage path and move the current data files to the garbage
            File::new(PATH_DATA . 'garbage/geonames', $restrictions->addPath(PATH_DATA . 'garbage/', true))->delete();
            $previous = Path::new($target_path, $restrictions)->move(PATH_DATA . 'garbage/');

            // Prepare and import each file
            foreach (static::getGeoNamesFiles() as $file => $data) {
                Log::action(tr('Processing GeoNames file ":file"', [':file' => $file]));

                if (str_ends_with($file, '.zip')) {
                    foreach ($data['files'] as $target_file) {
                        // Ensure the target files are gone so that we can unzip over them
                        File::new($source_path . $target_file, $restrictions)->delete();
                    }

                    // Unzip the files so that we have usable target files
                    File::new($source_path . $file, $restrictions)->checkReadable()->unzip();
                }

                // Move all target files to the target path
                foreach ($data['files'] as $target_file) {
                    File::new($source_path . $target_file, $restrictions)->checkReadable()->move($target_path);
                }
            }

            // Delete the previous data files from garbage
            $previous->delete();

        } catch (Throwable $e) {
            // Something borked. Move the previous data files back from the garbage to their original path so the system
            // will remain functional
            if (isset($previous)) {
                $previous->move($target_path);
            }

            throw $e;
        }

        static::load($target_path);
        return $target_path;
    }


    /**
     * Returns a list of MaxMind files that will be downloaded
     *
     * @note Using this functionality requires an account on https://www.maxmind.com/
     *
     * @note The list of these files can be found on https://www.maxmind.com/en/accounts/YOUR_ACCOUNT_ID/geoip/downloads
     *
     * @return array
     */
    protected static function getGeoNamesFiles(): array
    {
        return [
            'allCountries.zip' => [
                'files' => ['allCountries.txt'],
                'url'   => 'https://download.geonames.org/export/dump/allCountries.zip',
            ],
            'alternateNames.zip' => [
                'files' => ['alternateNames.txt', 'iso-languagecodes.txt'],
                'url'  => 'https://download.geonames.org/export/dump/alternateNames.zip',
            ],
            'hierarchy.zip' => [
                'files' => ['hierarchy.txt'],
                'url'  => 'https://download.geonames.org/export/dump/hierarchy.zip',
            ],
            'admin1CodesASCII.txt' => [
                'files' => ['admin1CodesASCII.txt'],
                'url'  => 'https://download.geonames.org/export/dump/admin1CodesASCII.txt',
            ],
            'admin2Codes.txt' => [
                'files' => ['admin2Codes.txt'],
                'url'  => 'https://download.geonames.org/export/dump/admin2Codes.txt',
            ],
            'featureCodes_en.txt' => [
                'files' => ['featureCodes_en.txt'],
                'url'  => 'https://download.geonames.org/export/dump/featureCodes_en.txt',
            ],
            'timeZones.txt' => [
                'files' => ['timeZones.txt'],
                'url'  => 'https://download.geonames.org/export/dump/timeZones.txt',
            ],
            'countryInfo.txt' => [
                'files' => ['countryInfo.txt'],
                'url' => 'https://download.geonames.org/export/dump/countryInfo.txt',
            ],
        ];
    }


    /**
     * Import the GeoNames data
     *
     * @param string $path
     * @param string|null $database
     * @return void
     */
    public static function load(string $path, ?string $database = null): void
    {
return;
        if (!$database) {
            // Default to geonames database
            $database = 'geonames';
        }

        Log::action(tr('Starting data import from path ":path"', [
            ':path' => $path
        ]));

        // Get the system SQL configuration, so we can use the user and password from there
        $config = sql()->readConfiguration('system');

        Sql::addConfiguration('geonames', [
            'name'           => 'geonames',
            'user'           => $config['user'],
            'pass'           => $config['pass'],
            'pdo_attributes' => [PDO::MYSQL_ATTR_LOCAL_INFILE => true]
        ]);

        // Create database
        Log::action(tr('Creating database "geonames"...'));

        sql('geonames', false)->schema(false)->database()->drop();
        sql('geonames', false)->schema(false)->database()->create();
        sql('geonames', false)->resetSchema();
        sql('geonames')->use();

        // Create table structure
        Log::action(tr('Creating database "geonames" tables...'));

        sql('geonames')->query('
            CREATE TABLE geoname (
                geonameid int PRIMARY KEY,
                name varchar(200),
                asciiname varchar(200),
                alternatenames varchar(4000),
                latitude decimal(10,7),
                longitude decimal(10,7),
                fclass char(1),
                fcode varchar(10),
                country varchar(2),
                cc2 varchar(60),
                admin1 varchar(20),
                admin2 varchar(80),
                admin3 varchar(20),
                admin4 varchar(20),
                population int,
                elevation int,
                gtopo30 int,
                timezone varchar(40),
                moddate date
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE alternatename (
                alternatenameId int PRIMARY KEY,
                geonameid int,
                isoLanguage varchar(7),
                alternateName varchar(200),
                isPreferredName BOOLEAN,
                isShortName BOOLEAN,
                isColloquial BOOLEAN,
                isHistoric BOOLEAN
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE countryinfo (
                iso_alpha2 char(2),
                iso_alpha3 char(3),
                iso_numeric integer,
                fips_code varchar(3),
                name varchar(200),
                capital varchar(200),
                areainsqkm double,
                population integer,
                continent char(2),
                tld char(3),
                currency char(3),
                currencyName char(20),
                Phone char(10),
                postalCodeFormat varchar(100),
                postalCodeRegex varchar(255),
                geonameId int,
                languages varchar(200),
                neighbours char(100),
                equivalentFipsCode char(10)
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE iso_languagecodes(
                iso_639_3 CHAR(4),
                iso_639_2 VARCHAR(50),
                iso_639_1 VARCHAR(50),
                language_name VARCHAR(200)
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE admin1CodesAscii (
                code CHAR(6),
                name TEXT,
                nameAscii TEXT,
                geonameid int
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE admin2Codes (
                code CHAR(15),
                name TEXT,
                nameAscii TEXT,
                geonameid int
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE hierarchy (
                parentId int,
                childId int,
                type VARCHAR(50)
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE featureCodes (
                code CHAR(7),
                name VARCHAR(200),
                description TEXT
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE timeZones (
                timeZoneId VARCHAR(200),
                GMT_offset DECIMAL(3,1),
                DST_offset DECIMAL(3,1)
            ) CHARACTER SET utf8;
            
            
            CREATE TABLE continentCodes (
                code CHAR(2),
                name VARCHAR(20),
                geonameid INT
            ) CHARACTER SET utf8;');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'allCountries.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/allCountries.txt"
            INTO TABLE geoname
            CHARACTER SET "UTF8"
                (geonameid, name, asciiname, alternatenames, latitude, longitude, fclass, fcode, country, cc2, admin1, admin2, admin3, admin4, population, elevation, gtopo30, timezone, moddate);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'alternateNames.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/alternateNames.txt"
            INTO TABLE alternatename
            CHARACTER SET "UTF8"
                (alternatenameid, geonameid, isoLanguage, alternateName, isPreferredName, isShortName, isColloquial, isHistoric);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'languagecodes.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/iso-languagecodes.txt"
            INTO TABLE iso_languagecodes
            CHARACTER SET "UTF8"
            IGNORE 1 LINES
                (iso_639_3, iso_639_2, iso_639_1, language_name);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'admin1CodesASCII.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/admin1CodesASCII.txt"
            INTO TABLE admin1CodesAscii
            CHARACTER SET "UTF8"
                (code, name, nameAscii, geonameid);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'admin2Codes.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/admin2Codes.txt"
            INTO TABLE admin2Codes
            CHARACTER SET "UTF8"
                (code, name, nameAscii, geonameid);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'hierarchy.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/hierarchy.txt"
            INTO TABLE hierarchy
            CHARACTER SET "UTF8"
                (parentId, childId, type);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'featureCodes_en.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/featureCodes_en.txt"
            INTO TABLE featureCodes
            CHARACTER SET "UTF8"
                (code, name, description);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'timeZones.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/timeZones.txt"
            INTO TABLE timeZones
            CHARACTER SET "UTF8"
            IGNORE 1 LINES
                (timeZoneId, GMT_offset, DST_offset);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'countryInfo.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/countryInfo.txt"
            INTO TABLE countryinfo
            CHARACTER SET "UTF8"
            IGNORE 51 LINES
                (iso_alpha2, iso_alpha3, iso_numeric, fips_code, name, capital, areaInSqKm, population, continent, tld, currency, currencyName, phone, postalCodeFormat, postalCodeRegex, languages, geonameid, neighbours, equivalentFipsCode);');


        Log::action(tr('Importing geonames data file ":file"', [':file' => 'continentCodes.txt']));
        sql('geonames')->query('SET GLOBAL local_infile=true;
            SET SESSION wait_timeout=600;
            
            LOAD DATA LOCAL INFILE "' . PATH_DATA . 'sources/geo/geonames/continentCodes.txt"
            INTO TABLE continentCodes
            CHARACTER SET "UTF8"
            FIELDS TERMINATED BY ","
                (code, name, geonameId);');

        // Disable local_infile for security
        sql('geonames')->query('SET GLOBAL local_infile=false;');
   }


    /**
     * Import the geonames data from the specified database
     *
     * @param string|null $database
     * @return void
     */
    public static function import(string $database = null): void
    {
        if (!$database) {
            $database = 'geonames';
        }

        // Disable meta tracking during import as there is a LOT of data and we don't really care much about who dunnit
        // for this data
        Meta::disable();

        // Re-enable meta data
        Meta::enable();
    }
}
