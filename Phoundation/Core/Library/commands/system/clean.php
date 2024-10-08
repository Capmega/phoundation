<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Date\DateTime;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;


/**
 * Script system/clean
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setUsage('./pho system clean [OPTIONS]
');

CliDocumentation::setHelp('This command will clean old and stale files from caches, temporary directories, sessions
and logs.

Deleting temporary and cache files should normally not be necessary as the system cleans up its temporary files and
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
    $argv['date'] = DateTime::new($argv['timestamp'])->format('Y-m-d H:i:s');

} elseif ($argv['days']) {
    $sub          = \Phoundation\Date\DateInterval::new($argv['days'] . ' days');
    $argv['date'] = DateTime::new()->sub($sub)->format('Y-m-d H:i:s');

} elseif ($argv['timestamp']) {
    $argv['date'] = DateTime::new($argv['timestamp'])->format('Y-m-d H:i:s');
}


// Define paths and clean them
$paths = [
    DIRECTORY_DATA . 'tmp',
    DIRECTORY_DATA . 'run',
    DIRECTORY_DATA . 'log',
    DIRECTORY_DATA . 'sessions',
];

foreach ($paths as $path) {
    // Configure find to find all files and directories older than $argv[date]
    $find = Directory::new($path, Restrictions::new($path, true))
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
Log::success('Cleaned old files');
