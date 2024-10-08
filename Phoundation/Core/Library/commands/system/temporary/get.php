<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Directory;


/**
 * Script system/temporary/get
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setUsage('./pho system temporary get PATH [OPTIONS]
./pho system temporary get PATH --public
./pho system temporary get PATH ');

CliDocumentation::setHelp('This command will create and a private temporary path


ARGUMENTS


[-p,--public]                           If specified, a public temporary directory will be returned');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-p,--public')->isOptional()->isBoolean()
                     ->validate();


// Get persistent temporary directory and we're done
Log::cli(Directory::getTemporary($argv['public'], true)->getPath());
