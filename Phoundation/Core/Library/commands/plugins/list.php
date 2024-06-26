<?php

/**
 * Command plugins list
 *
 * This command allows you to view your registered plugins
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\ArgvValidator;

CliDocumentation::setUsage('./pho plugins list [OPTIONS]
./pho system plugins list --all');

CliDocumentation::setHelp('This command allows you to view your registered plugins


ARGUMENTS


[-A / --all]                            List all plugins, not only the active ones');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->validate();


// What columns are we going to display?
$columns = [
    'vendor'    => tr('Vendor'),
    'name'      => tr('Name'),
    'status'    => tr('Status'),
    'priority'  => tr('Priority'),
    'directory' => tr('Directory'),
];


// Display the plugins
if (ALL) {
    Plugins::getAvailable()->displayCliTable($columns);

} else {
    Plugins::getEnabled()->displayCliTable($columns);
}
