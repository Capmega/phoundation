<?php

/**
 * Command system sync from
 *
 * This command will synchronize either the specified environment to your local environment or vice versa.
 *
 * With this, a production environment can easily be tested locally, for example
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;
use Phoundation\Developer\Sync;


CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => function ($word) { return Project::getEnvironments()->keepMatchingValuesStartingWith($word); },
            'noword' => function () { return Project::getEnvironments(); },
        ],
    ],
    'arguments' => [
        '-l,--lock'    => false,
        '-i,--no-init' => false,
        '-t,--timeout' => true,
    ],
]);

CliDocumentation::setUsage('./pho system sync from ENVIRONMENT
./pho system sync -l -i --to ENVIRONMENT');

CliDocumentation::setHelp('This command will synchronize either the specified environment to your local environment or vice versa.
With this, a production environment can easily be tested locally, for example


ARGUMENTS


ENVIRONMENT                             The single source environment to which the data will be synced

[-i / --no-init]                        If specified, will NOT execute the system initialization right after the sync
                                        process has finished

[-l / --lock]                           If specified, will readonly lock the target environment until sync has
                                        finished

[-t / --timeout TIMEOUT]                Timeout in integer seconds before the process will be terminated due to timeout
                                        (defaults to 3600)');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('environment', true)->isVariable()
                     ->select('-l,--lock')->isOptional(false)->isBoolean()
                     ->select('-i,--no-init')->isOptional(false)->isBoolean()
                     ->select('-t,--timeout', true)->isOptional(3600)->isNatural(true)
                     ->validate();


// Sync environments
Sync::new()
    ->setTimeout($argv['timeout'])
    ->setLock($argv['lock'])
    ->setInit(!$argv['no_init'])
    ->from($argv['environment']);


// Done!
Log::Success(tr('Finished syncing process from environment ":environment"', [
    ':environment' => $argv['environment'],
]));
