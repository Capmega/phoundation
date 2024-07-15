<?php

declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Command accounts/rights/create
 *
 * This command will create a new right with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts right create NAME [OPTIONS]
./pho system accounts right create test -d "This is a test right!"');

CliDocumentation::setHelp('This command allows you to create user rights


ARGUMENTS


NAME                                    The name for the right

[-d / --description]                    The description for the right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('name', true)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->validate();


// Check if the right already exists
Right::notExists($argv['name'], 'name', null, true);


// Create right and save it
$right = Right::new()->apply(false, $argv)->save();


// Done!
Log::success(tr('Created new right ":right"', [':right' => $right->getName()]));
