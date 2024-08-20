<?php

/**
 * Command cache system rebuild web
 *
 * This command rebuilds the system web cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

use Phoundation\Cache\Cache;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Web\Web;


CliDocumentation::setUsage('./pho cache system rebuild web [OPTIONS]
./pho cache system rebuild web
./pho cache system rebuild web --commit
./pho cache system rebuild web --sign
');

CliDocumentation::setHelp('This command will rebuild the system web caches and automatically commit the updated web 
caches to git


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


// Rebuild web cache
Libraries::rebuildWebCache();


/// Try to auto commit the cache rebuild
Cache::systemAutoGitCommit('web', $argv['auto_commit'], $argv['sign'], $argv['message']);
