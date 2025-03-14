<?php

/**
 * Command project test
 *
 * This is the test script for the project.
 *
 * This command can manage and start the PHP unit Tests and others
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Tests\Tests;

CliDocumentation::setUsage('./pho project test [OPTIONS]
./pho project test --unit');

CliDocumentation::setHelp('This is the test script for the project.

This command can manage and start the PHP unit Tests and others


ARGUMENTS


[--unit]                                Execute PHP Unit Tests');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('--unit')->isOptional(false)->isBoolean()
                     ->validate();


// Execute unit Tests?
if ($argv['unit']) {
    Tests::unit();
}
