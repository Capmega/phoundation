<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Tests\Tests;


/**
 * Script system/test
 *
 * This is the test script for the project.
 *
 * This script can manage and start the PHP unit tests and others
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */
CliDocumentation::setUsage('./pho system test [OPTIONS]
./pho system test --unit');

CliDocumentation::setHelp('This is the test script for the project.

This script can manage and start the PHP unit tests and others


ARGUMENTS


[--unit]                                Execute PHP Unit tests');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('--unit')->isOptional(false)->isBoolean()
                     ->validate();


// Execute unit tests?
if ($argv['unit']) {
    Tests::startPhpUnitTests();
}
