<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;


/**
 * Script system/import
 *
 * This script will import all data to get your system going. Currently this script will import:
 *
 * languages
 * geo data (continents, countries, states, counties, cities, etc) (under construction)
 * geoip data  (under construction)
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho system import');

CliDocumentation::setHelp('This command allows you to import all project data with one command

Currently this script will import:
 * languages
 * geo data (continents, countries, states, counties, cities, etc) (under construction)
 * geoip data                                                      (under construction)



ARGUMENTS



[-d / --demo]                           If specified, will load demo data into the database tables so the system can
                                        quickly show actual functionality

[--min] (10)                            If demo mode is enabled, this will specified the minimum number of records added
                                        in each library

[--max] (1000)                          If demo mode is enabled, this will specified the maximum number of records added
                                        in each library');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-d,--demo')->isOptional()->isBoolean()
                     ->select('--min', true)->isOptional(10)->isNatural()
                     ->select('--max', true)->isOptional(1000)->isNatural()
                     ->validate();


Log::information(tr('Importing languages, this may take a few seconds...'));
Project::import($argv['demo'], $argv['min'], $argv['max']);
