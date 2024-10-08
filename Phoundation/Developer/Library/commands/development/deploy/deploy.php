<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Project\Project;


/**
 * THIS SCRIPT IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This script will copy changed files back to your phoundation installation. The script will assume your phoundation
 * installation is in ~/projects/phoundation
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Development
 */

CliDocumentation::setUsage('./pho development deploy [OPTIONS] TARGET_ENVIRONMENT
./pho system development deploy TARGET_ENVIRONMENT
');

CliDocumentation::setHelp('This command will update your Phoundation libraries and list



ARGUMENTS



TARGET_ENVIRONMENT                      The target environment where to deploy to

-t / --tag                              The git tag which to deploy (NOTE: Production ONLY allows deploying tags). If
                                        not specified, the current branch will be deployed, if allowed

[-m / --message]                        The deployment notification message to send out. If not specified, a default
                                        will be used');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-t,--tag', true)->isOptional()->isPrintable()
                     ->select('-m,--message', true)->isOptional()->isPrintable()->hasMinCharacters(10)->hasMaxCharacters(1024)
                     ->select('target_environment')->isPrintable()
                     ->validate();


Log::information(tr('Deploying project ":project" to environment ":environment"', [
    ':project'     => PROJECT,
    ':environment' => $argv['target_environment'],
]));


Project::deploy()
       ->setTag($argv['tag'])
       ->sendMessage($argv['message'])
       ->setTargetEnvironment($argv['target_environment'])
       ->execute();
