<?php

/**
 * Command accounts rights create
 *
 * This command will create a new right with the specified properties
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

use Phoundation\Accounts\Rights\Right;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setAutoComplete(Right::getAutoComplete([
    'positions' => [
        0 => function ($word) { return Rights::new()->loadForAutocomplete($word, 'name'); },
    ],
    'arguments' => [
        '-n,--name'        => true,
        '-d,--description' => true,
    ]
]));

CliDocumentation::setUsage('./pho accounts right create -n NAME [OPTIONS]
./pho accounts right modify test -n test2 -d "This is a test right!"');

CliDocumentation::setHelp('This command allows you to modify user rights


ARGUMENTS


RIGHT                                   The name or id of the right to modify


OPTIONAL ARGUMENTS 


[-n / --name] NAME                      Updates the name for the right

[-d / --description DESCRIPTION]        Updated the description for the right');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('right', true)->isName()
                     ->select('-n,--name', true)->isOptional(null)->isName()
                     ->select('-d,--description', true)->isOptional(null)->isDescription()
                     ->validate();


// Get right, ensure the new name does not exist yet, and modify it
$right = Right::new()->load($argv['right']);

if ($argv['name']) {
    // If changing name, ensure it does not exist yet as it is a unique identifier
    Right::notExists(['name' => $argv['name']], $right->getId(), true);
}

$right->apply(false, $argv)->save();


// Done!
Log::success(ts('Modified right ":right"', [':right' => $right->getName()]), 10);
