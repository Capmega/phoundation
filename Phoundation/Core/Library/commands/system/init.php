<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Libraries\Library;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Utils\Strings;


/**
 * Script system/init
 *
 * This is the init script for the project. Run this script to ensure that the database is running with the same version
 * as the code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */

CliDocumentation::setAutoComplete([
    'arguments' => [
        '--drop'           => false,
        '--import'         => false,
        '--min'            => true,
        '--max'            => true,
        '-c,--comments'    => true,
        '-d,--demo'        => false,
        '-l,--libraries'   => true,
        '-p,--plugins'     => false,
        '-s,--system'      => false,
        '-t,--templates'   => false,
        '-v,--set-version' => true,
    ]
]);

CliDocumentation::setUsage('./pho system init [OPTIONS]
./pho system init --drop
./pho system init --import
./pho system init --version "core/0.0.5,"');

CliDocumentation::setHelp('This command allows you to setup a new project


ARGUMENTS


[--drop]                                Drops the system database and start init from version 0.0.0

[--force]                               (SYSTEM FLAG) If set, will run import (That is, truncate old data and import
                                        new) even if data was already available

[--import]                              Run import for all libraries that support it. This will automatically import all
                                        data for all systems. Since this requires (amongst things) downloading and
                                        importing (sometimes) very large data sets, this may take a little while

[-l / --libraries LIBRARIES]            Comma delimited field. Only available if --import was specified. If specified,
                                        will only execute import for libraries matching the names specified

[--min NUMBER (10)]                     If demo mode is enabled, this will specified the minimum number of records added
                                        in each library

[--max NUMBER (1000)]                   If demo mode is enabled, this will specified the maximum number of records added
                                        in each library

[-d / --demo]                           If specified, will load demo data into the database tables so the system can
                                        quickly show actual functionality

[-p, --no-plugins]                      Do NOT initalize plugin libraries

[-s, --no-system]                       Do NOT initialize system libraries

[-t, --no-templates]                    Do NOT initialize template libraries

[-c, --comments COMMENTS]               The optional comments to add in the versions table about this init

[-v, --set-version [NAME/VERSION],      Will update the specified libraries to the specified version before
                   [NAME/VERSION], ...] initialization is executed. This is useful to re-run a specific version update');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('--drop')->isOptional(false)->isBoolean()
                     ->select('--import')->isOptional(false)->isBoolean()
                     ->select('--min', true)->isOptional(10)->isNatural()
                     ->select('--max', true)->isOptional(1000)->isNatural()
                     ->select('-c,--comments', true)->isOptional()->isPrintable()
                     ->select('-d,--demo')->isOptional()->isBoolean()
                     ->select('-l,--libraries', true)->isOptional()->isString()->sanitizeForceArray()
                     ->select('-p,--plugins')->isOptional(false)->isBoolean()
                     ->select('-s,--system')->isOptional(false)->isBoolean()
                     ->select('-t,--templates')->isOptional(false)->isBoolean()
                     ->select('-v,--set-version', true)->isOptional()->hasMaxCharacters(2048)->sanitizeForceArray()->each()->matchesRegex('/[a-z0-9-_]+\/\d{1,3}\.\d{1,3}\.\d{1,3}/i')
                     ->validate();


// Drop the database and start from scratch? DANGER!
if ($argv['drop']) {
    Libraries::reset();
}


// Update the version for specified libraries
if ($argv['set_version']) {
    foreach ($argv['set_version'] as $lib_version) {
        $library = Strings::until($lib_version, '/');
        $version = Strings::from($lib_version, '/');

        Library::get($library)->setVersion($version);
    }
}


// Initialize the system
Libraries::initialize(!$argv['system'], !$argv['plugins'], !$argv['templates'], $argv['comments'], $argv['libraries']);


// Run import?
if ($argv['import']) {
    Project::import($argv['demo'], $argv['min'], $argv['max'], $argv['libraries']);
}


//    // During init, force EMULATE_PREPARES because loads of init stuff will NOT work without
//    foreach ($_CONFIG['db'] as $name => &$connector) {
//        if ($name == 'default') continue;
//
//        if (!empty($connector['init'])) {
//            $connector['pdo_attributes'] = array(PDO::ATTR_EMULATE_PREPARES => true,
//                PDO::ATTR_STRINGIFY_FETCHES => true);
//        }
//    }
