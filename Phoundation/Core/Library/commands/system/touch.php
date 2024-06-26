<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Mtime;


/**
 * Command system/touch
 *
 * This is the touch script for the project.
 *
 * This script can manage and start the PHP unit touchs and others
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Core
 */
CliDocumentation::setUsage('./pho system touch [OPTIONS]
./pho system touch --unit');

CliDocumentation::setHelp('This is the touch script for the project.

This script can touch and update mtime for all files in the project


ARGUMENTS


[-d,--datetime]                         The datetime to use');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-d,--datetime')->isOptional(false)->isDateTime()
                     ->validate();


Mtime::new()
     ->setDateTime($argv['datetime'])
     ->apply();