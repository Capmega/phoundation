<?php

/**
 * Command cache system rebuild all
 *
 * This command will rebuild all system caches
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cache\Cache;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho cache system rebuild all [OPTIONS]
./pho cache system rebuild all
./pho cache system rebuild all --commit
./pho cache system rebuild all --sign
');

CliDocumentation::setHelp('This command will rebuild all the system caches and automatically commit the updated 
system caches to git


ARGUMENTS


[-c, --auto-commit]                     If specified, will commit the update to git, even if configured not to

[-s, --sign]                            If specified, will sign the commit, even if configured not to

[-m, --message MESSAGE]                 If specified, this message will be used');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-s,--sign'        => false,
        '-c,--auto-commit' => false,
        '-m,--message'     => true,
    ],
]);


// Get command arguments
$argv = ArgvValidator::new()
    ->select('-c,--auto-commit')->isOptional(false)->isBoolean()
    ->select('-s,--sign')->isOptional(false)->requiresNotEmpty('--auto-commit')->isBoolean()
    ->select('-m,--message', true)->isOptional()->requiresNotEmpty('--auto-commit')->isDescription()
    ->validate();


// Rebuild commands and web cache
Libraries::rebuildWebCache();
Libraries::rebuildHookCache();
Libraries::rebuildTestsCache();
Libraries::rebuildCommandsCache();


// Commit the system web cache?
Cache::systemAutoGitCommit(null, $argv['auto_commit'], $argv['sign'], $argv['message']);
