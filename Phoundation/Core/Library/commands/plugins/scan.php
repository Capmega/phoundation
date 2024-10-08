<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script system/plugins/scan
 *
 * This script allows you to scan the plugins directory DIRECTORY_ROOT/Plugins/ for new plugins and update the
 * core_plugins database table accordingly
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */
CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-c,--clear' => false,
                                      ],
                                  ]);

CliDocumentation::setUsage('./pho system plugins scan');

CliDocumentation::setHelp('This command allows you to scan the plugins directory DIRECTORY_ROOT/Plugins/ for new
plugins and update the core_plugins database table accordingly


ARGUMENTS


[-c, --clear]                           Clears the current plugins list from the database and scans all plugins from
                                        scratch

[-A, --all]                             List all plugins, not only the active ones');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-c,--clear')->isOptional()->isBoolean()
                     ->validate();


// Clear the plugin registration?
if ($argv['clear']) {
    Plugins::new()->erase();
}


// What columns are we going to display?
$columns = [
    'vendor'   => tr('Vendor'),
    'name'     => tr('Name'),
    'status'   => tr('Status'),
    'priority' => tr('Priority'),
    'path'     => tr('Path'),
];


// Display the results of the plugin scan
Plugins::new()
       ->scan()
       ->getAvailable()
           ->displayCliTable($columns);
