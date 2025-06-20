<?php

/**
 * Command project clean
 *
 * This command can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Date\PhoDateTime;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;


CliDocumentation::setUsage('./pho project clean [OPTIONS]
');

CliDocumentation::setHelp('This command will clean old and stale files from caches, temporary directories, sessions
and logs.

Deleting temporary and cache files should normally not be necessary as the project cleans up its temporary files and
caches automatically whenever need be but in case of crashes, some files may be left lying around.


ARGUMENTS


[-t,--timestamp [TIMESTAMP]             Timestamp that serves as a limit for the mtime for files. All files mtime before
                                        this limit will be deleted

[-d,--date [DATETIME]                   Date time that serves as a limit for the mtime for files. All files with an
                                        mtime before this limit will be deleted

[-a,--days [COUNT]                      Amount of days a files must exist before being deleted.

[-s,--shred]                            Shreds the files instead of merely deleting them. NOTE: Depending on amount and
                                        sizes of the files, this may take a significantly longer number of time!
');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-a,--days')->isOptional()->isNumeric()
                     ->select('-d,--date')->isOptional()->xor('days')->isDate()
                     ->select('-t,--timestamp')->isOptional()->xor('date')->isNumeric()
                     ->select('-s,--shred')->isOptional()->isBoolean()
                     ->validate();


// Convert timestamp to ISO-8601 date
if ($argv['date']) {
    $argv['date'] = PhoDateTime::new($argv['timestamp'])->format('Y-m-d H:i:s');

} elseif ($argv['days']) {
    $sub          = \Phoundation\Date\PhoDateInterval::new($argv['days'] . ' days');
    $argv['date'] = PhoDateTime::new()->sub($sub)->format('Y-m-d H:i:s');

} elseif ($argv['timestamp']) {
    $argv['date'] = PhoDateTime::new($argv['timestamp'])->format('Y-m-d H:i:s');
}


// Define paths and clean them
$paths = [
    DIRECTORY_SYSTEM . 'tmp/',
    DIRECTORY_SYSTEM . 'run/',
    DIRECTORY_SYSTEM . 'sessions/',
    DIRECTORY_SYSTEM . 'cache/files/',
    DIRECTORY_DATA   . 'log/',
];

foreach ($paths as $path) {
    // Configure find to find all files and directories older than $argv[date]
    $find = PhoDirectory::new($path, PhoRestrictions::new($path, true))
                        ->find()
                        ->olderThan($argv['date']);

    // Delete or shred?
    if ($argv['shred']) {
        $find->doDelete()->execute();

    } else {
        $find->doShred()->execute();
    }
}


// Done!
Log::success('Cleaned old files', 10);
