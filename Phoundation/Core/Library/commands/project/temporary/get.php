<?php

/**
 * Command project temporary get
 *
 * This command can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\PhoDirectory;


CliDocumentation::setUsage('./pho project temporary get PATH [OPTIONS]
./pho project temporary get PATH --public
./pho project temporary get PATH ');

CliDocumentation::setHelp('This command will create and a private temporary path


ARGUMENTS


[-p,--public]                           If specified, a public temporary directory will be returned');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-p,--public')->isOptional()->isBoolean()
                     ->validate();


// Get persistent temporary directory and we're done
Log::cli(PhoDirectory::newTemporaryObject($argv['public'], true)->getSource());
