<?php

/**
 * Command development tests unit
 *
 * This script will start phpunit tests
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Tests\Tests;


CliDocumentation::setHelp('This script will start running all PHPUnit tests in the project


ARGUMENTS


-');

CliDocumentation::setUsage('./pho development tests unit
./pho dev tests unit
');


// Get arguments
$argv = ArgvValidator::new()
                     ->validate();


// Start unit testing, baby!
Tests::unit();
