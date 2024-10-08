<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Servers\Servers;


/**
 * Script servers/list
 *
 * This script will list the available servers on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho servers list [OPTIONS]
./pho system servers list -D');

CliDocumentation::setHelp('This command will list the configured servers on this system



ARGUMENTS


[-D / --deleted]                       Will also show deleted servers');


// Validate arguments
$argv = ArgvValidator::new()
                     ->validate();


// Display the available servers
Servers::new()
       ->addFilter('status', (DELETED ? 'deleted' : null))
       ->CliDisplayTable();
