<?php

/**
 * Command development tests start
 *
 * This script will starts phpunit tests
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Tests\Tests;


CliDocumentation::setHelp('This script will start running all PHPUnit tests in the project


ARGUMENTS


-');

CliDocumentation::setUsage('./pho development tests start
./pho dev tests start
');


// Get arguments
$argv = ArgvValidator::new()
                     ->validate();


// Start unit testing, baby!
Tests::start();
