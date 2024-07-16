<?php

/**
 * Command servers/accounts/info
 *
 * This script displays information about the specified account.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Servers\SshAccount;

CliDocumentation::setUsage('./pho servers accounts info IDENTIFIER');

CliDocumentation::setHelp('This command displays information about the specified SSH account.



ARGUMENTS


IDENTIFIER                              The SSH account to display information about. Specify either by account id or
                                        name');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('identifier')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->validate();


// Display SSH account data
SshAccount::load($argv['account'])->displayCliForm();
