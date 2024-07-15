<?php

/**
 * Command servers accounts list
 *
 * This script will list the available SSH accounts on this system
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Servers\SshAccounts;

CliDocumentation::setUsage('./pho servers accounts list [OPTIONS]
./pho system servers accounts list -D');

CliDocumentation::setHelp('This command will list the configured SSH accounts on this system


ARGUMENTS


[-D / --deleted]                       Will also show deleted accounts');


// Validate arguments
$argv = ArgvValidator::new()
                     ->validate();


// Display the available SSH accounts
SshAccounts::new()
           ->removeValues(DELETED ? 'deleted' : '', 'status', true)
           ->displayCliTable();
