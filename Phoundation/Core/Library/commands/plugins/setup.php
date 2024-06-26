<?php

/**
 * Command plugins scan
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

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\ArgvValidator;

CliDocumentation::setUsage('./pho plugins scan');

CliDocumentation::setHelp('This command allows you to scan the plugins directory DIRECTORY_ROOT/Plugins/ for new plugins and
update the core_plugins database table accordingly


ARGUMENTS


[-A / --all]                            List all plugins, not only the active ones');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->validate();


// Display the results of the plugin scan
Plugins::scan()->displayCliTable();
