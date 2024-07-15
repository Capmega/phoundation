<?php

/**
 * Command development mdb repositories sync
 *
 * This script can sync MDB repositories
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Development
 */

declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Versioning\Git\Git;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;

CliDocumentation::setHelp('The development/mdb/repositories/sync script synchronizes all MDB repositories

If the repositories do not exist, they will be cloned.


ARGUMENTS


[-s,--source FILE]                     The source file that contains a list of all repositories

[-t,--target DIRECTORY]                The target directory where to checkout or ');

CliDocumentation::setUsage('./pho development mdb repositories sync
./pho development mdb repositories sync --source mdb/repositories
./pho development mdb repositories sync --path ~/projects/mdb
./pho development mdb repositories sync --path ~/projects/mdb --source mdb/repositories
');


// Setup restrictions
$source_restrictions = FsRestrictions::new('data/sources', true);
$target_restrictions = FsRestrictions::new('~', true);


// Get arguments
$argv = ArgvValidator::new()
                     ->select('-s,--source')->isOptional('data/sources/mdb/repositories')->isFile(FsDirectory::getDataSources())
                     ->select('-t,--target')->isOptional('~/projects/mdb')->isDirectory(FsDirectory::new('~/projects/mdb', FsRestrictions::getReadonly('~/projects/mdb')))
                     ->validate();


// Get repositories and target path
$repositories = FsFile::new($argv['source'], $source_restrictions);
$target       = FsDirectory::new($argv['target'], $target_restrictions);
$repositories = $repositories->getContentsAsIterator();

Log::information(tr('About to sync ":count" repositories, this might take a while...', [
    ':count' => $repositories->getCount(),
]));


// Sync each repository
foreach ($repositories as $repository) {
    $file = Strings::fromReverse($repository, '/');
    $file = Strings::until($file, '.');
    $path = $target->addDirectory($file);

    if ($path->exists()) {
        Log::action(tr('Fetching all for MDB repository ":repository"', [
            ':repository' => $repository,
        ]));

        Git::new($path)->fetchAll();

    } else {
        Log::action(tr('Cloning MDB repository ":repository"', [
            ':repository' => $repository,
        ]));

        // Repo does not yet exist, clone it
        Git::new($path->getParentDirectory()->ensure())->clone($repository);
    }
}

Log::success(tr('Finished MDB repository sync, MDB repositories size is now ":size"', [
    ':size' => Numbers::getHumanReadableBytes(FsDirectory::new($argv['target'])->getSize()),
]));
