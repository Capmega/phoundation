<?php

/**
 * Command tools os filesystem btrfs subvolumes create
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileExistsException;
use Phoundation\Filesystem\PhoDirectory;

throw new UnderConstructionException();
$directory = PhoDirectory::newFilesystemRoot();

CliDocumentation::setUsage('./pho tools os filesystem btrfs subvolumes create PATH');

CliDocumentation::setHelp('The BTRFS subvolume create script can create BTRFS subvolumes


ARGUMENTS


PATH                                    The path of the subvolume');

CliDocumentation::setAutoComplete([
    'positions' => [
        0 => [
            'word'   => function ($word) use ($directory) { return $directory->scan($word . '*'); },
            'noword' => function ($word) use ($directory) { return $directory->scan('*'); },
        ],
    ]
]);


// Validate data
$argv = ArgvValidator::new()
                     ->select('path')->sanitizeFile($directory, null)
                     ->validate();


// Validate the target
try {
    Btrfs::new($argv['path'])
        ->subvolumes()
        ->create();

} catch (FileExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success(ts('Finished generating subvolume ":path"', [
    ':path' => $argv['path']
]), 10);
