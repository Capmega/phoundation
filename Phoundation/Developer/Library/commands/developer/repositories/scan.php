<?php

/**
 * Command developer repositories scan
 *
 * THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS
 *
 * This command will scan for phoundation repositories and register them in the database
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Developer\Versioning\Repositories\Repositories;
use Phoundation\Filesystem\PhoDirectory;


// Start documentation
CliDocumentation::setAutoComplete([
    'arguments' => [
        '-p,--path' => function ($word) {
            return PhoDirectory::newRoot()->scan($word);
        },
        '-d,--delete-gone'  => false,
        '-a,--auto-disable' => false
    ]
]);

CliDocumentation::setUsage('./pho development repositories scan');

CliDocumentation::setHelp(ts('THIS COMMAND IS ONLY FOR PHOUNDATION DEVELOPERS

This command will scan for phoundation repositories and register them in the database


ARGUMENTS


- 


OPTIONAL ARGUMENTS


[-p, --path PATH]                       If specified, will start scanning for GIT repositories from the specified path. Defaults to ":path"

[-a, --no-auto-disable]                 If specified, any repository has a backup directory path part will automatically be disabled

[-d, --no-delete-gone]                  If specified, any repository that was registered before but not found in the current scan, will be deleted', [
    ':path' => PhoDirectory::newRoot()->getParentDirectoryObject()->getParentDirectoryObject()
]));


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-p,--path', true)->isOptional(PhoDirectory::newRoot()->getParentDirectoryObject()->getParentDirectoryObject())->isPath()
                     ->select('-d,--no-delete-gone')->isOptional()->isBoolean()
                     ->select('-a,--no-auto-disable')->isOptional()->isBoolean()
                     ->validate();


// Scan for new repositories
Log::cli(ts('Scanning ":path" for repositories, this might take a few seconds...', [
    ':path' => $argv['path']
]), 'action');

$_repositories = Repositories::new()->scan($argv['path'], !$argv['no_auto_disable'], !$argv['no_delete_gone']);


// Process permission denied errors
if ($_repositories->getNumberOfResultsWithPermissionDenied()) {
    Log::cli(ts('Encountered "permission denied" on  following ":count" paths', [
        ':count' => $_repositories->getNumberOfResultsWithPermissionDenied()
    ]), 'warning');

    if (Debug::isEnabled()) {
        foreach ($_repositories->getResultsWithPermissionDenied() as $repository) {
            Log::cli($repository, 'warning');
        }
    }
}


// Done!
Log::cli(ts('Found ":new" new repositories, deleted ":deleted" repositories, there are ":count" repositories in the database', [
    ':new'     => $_repositories->getNewCount(),
    ':count'   => Repositories::new()->load()->getCount(),
    ':deleted' => $_repositories->getDeletedCount()
]), 'success');
