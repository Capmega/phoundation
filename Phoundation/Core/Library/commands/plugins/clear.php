<?php

/**
 * Command plugins clear
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho plugins clear');

CliDocumentation::setHelp('This command clears all plugin registrations by deleting all entries in the core_plugins
table


ARGUMENTS


-');


// Get command line arguments
$argv = ArgvValidator::new()->validate();


// Clear the plugin registration
Plugins::new()->erase();
