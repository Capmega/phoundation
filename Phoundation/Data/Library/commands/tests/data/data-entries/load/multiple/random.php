<?php

/**
 * Command tests data data-entries load multiple random
 *
 * This command runs manual tests with loading multiple DataEntry objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

use Phoundation\Accounts\Users\User;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Library;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Sql\Limit;
use Phoundation\Utils\Strings;


CliDocumentation::setAutoComplete(User::getAutoComplete([
    'arguments' => [
        '--class'   => true,
        '--count'   => true,
        '--caching' => false,
    ],
]));

CliDocumentation::setUsage('./pho tests data data-entries load multiple random --class CLASS --count 50
./pho tests data data-entries load multiple random --class Phoundation\Accounts\Users\User --count 50 --caching');

CliDocumentation::setHelp(User::getHelpText('This command runs manual tests with loading multiple DataEntry objects  


ARGUMENTS


-


OPTIONAL ARGUMENTS


[--class CLASS-PATH]                    The DataEntry class to load
                                        Default: Phoundation\Accounts\Users\User

[--count NUMBER]                        The amount of objects that should be loaded
                                        Default: 1000

[--caching]                             If specified, enable DataEntry caching'));


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('--class', true)->isOptional('Phoundation\Accounts\Users\User')->hasMinCharacters(2)->hasMaxCharacters(255)
                     ->select('--count', true)->isOptional(1000)->isPositive()->isInteger()
                     ->select('--caching')->isOptional(false)->isBoolean()
                     ->validate();


// Ensure the library file is included
Library::includeClassFile($argv['class']);


// Load the requested number of random DataEntry objects
for ($i = 1; $i <= $argv['count']; $i++) {
    $data_entry = $argv['class']::new()->setIgnoreDeleted(true)
                                       ->setCacheEnabled($argv['caching'])
                                       ->loadRandom();

    if ($data_entry->isLoadedFromCache()) {
        Log::dot(1);

    } else {
        Log::dot(1, 'yellow');
    }
}


// Done!
Log::cli();
Log::success(ts('Finished loading ":count" ":class" class DataEntry objects', [
    ':count' => $argv['count'],
    ':class' => $argv['class'],
]), 10);
