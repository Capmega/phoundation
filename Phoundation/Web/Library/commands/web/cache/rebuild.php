<?php

/**
 * Command web cache rebuild
 *
 * This command rebuilds the web cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Utils\Config;
use Phoundation\Web\Web;

CliDocumentation::setUsage('./pho web cache rebuild [OPTIONS]
./pho rebuild
./pho rebuild --commit
./pho rebuild --sign
');

CliDocumentation::setHelp('This command will rebuild all the system caches and automatically commit the updated
cache to git


ARGUMENTS


-c,--commit                             If specified will commit the update to git, even if configured not to

-s,--sign                               If specified will sign the commit, even if configured not to
');

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-s,--sign'   => false,
                                          '-c,--commit' => false,
                                      ],
                                  ]);


// Get command arguments
$argv = ArgvValidator::new()
                     ->select('-c,--commit')->isOptional(false)->isBoolean()
                     ->select('-s,--sign')->isOptional(false)->isBoolean()
                     ->validate();


// Rebuild web cache
Web::rebuildCache();


// Commit the system web cache?
$git = Git::new(DIRECTORY_DATA . 'system/cache/web/');

if ($git->getStatus()->getCount()) {
    if (Config::getBoolean('cache.system.commit.auto', false) or $argv['commit']) {
        // Commit the system web cache
        $git->add(DIRECTORY_DATA . 'system/cache/web/')
            ->commit(tr('Rebuilt system web cache'), Config::getBoolean('cache.system.commit.signed', false) or $argv['signed']);

        Log::success(tr('Committed system cache update to git'));
    }
}
