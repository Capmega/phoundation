<?php

/**
 * Command cache system rebuild commands
 *
 * This command will rebuild the system commands cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */


declare(strict_types=1);

use Phoundation\Cache\Cache;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho cache system rebuild commands [OPTIONS]
./pho cache system rebuild commands
./pho cache system rebuild commands --commit
./pho cache system rebuild commands --sign
');

CliDocumentation::setHelp('This command will rebuild the system commands caches and automatically commit the 
updated commands caches to git


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


// Rebuild commands cache
Libraries::rebuildCommandsCache();


// Try to auto commit the cache rebuild
Cache::systemAutoGitCommit('commands', $argv['auto_commit'], $argv['sign'], $argv['message']);
