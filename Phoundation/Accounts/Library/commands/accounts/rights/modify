<?php

declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


/**
 * Script accounts/rights/create
 *
 * This script will create a new right with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho accounts right create -n NAME [OPTIONS]
./pho system accounts right create -n test -d "This is a test right!"');

CliDocumentation::setHelp('This command allows you to modify user rights


ARGUMENTS


-n / --name                             The name for the right

[-d / --description]                    The description for the right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('right', true)->isName()
                     ->select('-n,--name', true)->isOptional(null)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->validate();


// Get right, ensure new name doesn't exist yet, and modify it
$right = Right::get($argv['right']);

if ($argv['name']) {
    // If changing name, ensure it doesn't exist yet as its a unique identifier
    Right::notExists($argv['name'], 'name', $right->getId(), true);
}

$right->apply(false, $argv)->save();


// Done!
Log::success(tr('Modified right ":right"', [':right' => $right->getName()]));
