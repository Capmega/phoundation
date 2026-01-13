<?php

/**
 * Command cache system rebuild Tests
 *
 * This command will rebuild the system Tests cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cache
 */


declare(strict_types=1);

use Phoundation\Cache\Cache;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Data\Validator\ArgvValidator;


CliDocumentation::setUsage('./pho cache system rebuild Tests [OPTIONS]
./pho cache system rebuild Tests
./pho cache system rebuild Tests --commit
./pho cache system rebuild Tests --sign
');

CliDocumentation::setHelp('This command will rebuild the system Tests caches and automatically commit the 
updated Tests caches to git


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
                     ->select('-s,--sign')->isOptional(false)->requiresColumnsNotEmpty('--auto-commit')->isBoolean()
                     ->select('-m,--message', true)->isOptional()->requiresColumnsNotEmpty('--auto-commit')->isDescription()
    ->validate();


// Rebuild Tests cache
Libraries::rebuildTestsCache();


// Try to auto commit the cache rebuild
Cache::systemAutoGitCommit('Tests', $argv['auto_commit'], $argv['sign'], $argv['message']);
