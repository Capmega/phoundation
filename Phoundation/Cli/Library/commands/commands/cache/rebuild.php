<?php

/**
 * Command commands cache rebuild
 *
 * This command will rebuild the commands cache
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Cli
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Config;

CliDocumentation::setUsage('./pho commands cache rebuild [OPTIONS]
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


// Rebuild commands cache
Libraries::rebuildCommandCache();


// TODO Move this git commit code to a SystemCache::commit() like method
// Commit the system web cache?
$git = Git::new(new FsDirectory(
    DIRECTORY_DATA . 'system/cache/commands/',
    FsRestrictions::getWritable(DIRECTORY_DATA . 'system/cache/commands/', 'command commands cache rebuild')
));

if ($git->getStatusFilesObject()->getCount()) {
    if (Config::getBoolean('cache.system.commit.auto', false) or $argv['commit']) {
        // Commit the system commands cache
        $git->add(DIRECTORY_DATA . 'system/cache/commands/')
            ->commit(
                tr('Rebuilt system commands cache'),
                Config::getBoolean('cache.system.commit.signed', false) or $argv['signed']
            );

        Log::success(tr('Committed system cache update to git'));
    }
}
