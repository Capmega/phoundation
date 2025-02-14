<?php

/**
 * Command tests data data-entries load multiple
 *
 * This script runs manual tests with loading multiple DataEntry objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;


CliDocumentation::setAutoComplete(User::getAutoComplete([
    'positions' => [
        0 => [
            'word'   => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE COALESCE(`username`, `email`, `code`) LIKE :word AND `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
            'noword' => 'SELECT COALESCE(`username`, `email`, `code`) AS `email` FROM `accounts_users` WHERE `status` IS NULL LIMIT ' . Limit::shellAutoCompletion(),
        ],
    ],
]));

CliDocumentation::setUsage('./pho tests data data-entries load multiple --class CLASS --count 50');

CliDocumentation::setHelp(User::getHelpText('This script runs manual tests with loading multiple DataEntry objects  


ARGUMENTS


--class CLASS-PATH                      The DataEntry class to load

--count NUMBER                          The amount of objects that should be loaded'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--class')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->select('--count')->isPositive()->isInteger()
                     ->validate();


// Ensure the library file is included

//
for ($i = 1; $i <= $argv['count']; $i++) {

}