<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Locale\Language\Import;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Command languages/import
 *
 * This command will import data into the languages table from the data/sources/languages/languages file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho languages import');

CliDocumentation::setHelp('This command allows you to create users



ARGUMENTS



[--min] (10)                            If demo mode is enabled, this will specified the minimum number of records added
                                        in each library

[--max] (1000)                          If demo mode is enabled, this will specified the maximum number of records added
                                        in each library

[-d / --demo]                           If specified, will load demo data into the database tables so the system can
                                        quickly show actual functionality');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--min', true)->isOptional(10)->isNatural()
                     ->select('--max', true)->isOptional(1000)->isNatural()
                     ->select('-d,--demo')->isOptional()->isBoolean()
                     ->validate();


Import::new($argv['demo'], $argv['min'], $argv['max'])->execute();
