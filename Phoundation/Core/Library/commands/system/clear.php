<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Date\DateTime;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;


/**
 * Script system/clear
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

CliDocumentation::setUsage('./pho system clear [OPTIONS]
');

CliDocumentation::setHelp('This command will clear the caches and temporary files

This should normally not be necessary as the system cleans up its temporary files and caches automatically whenever need
be but in case of crashes, some files may be left lying around


ARGUMENTS


[-t,--timestamp [TIMESTAMP]             Timestamp that serves as a limit for the mtime for files. All files mtime before
                                        this limit will be deleted

[-d,--date [DATETIME]                   Date time that serves as a limit for the mtime for files. All files with an
                                        mtime before this limit will be deleted

[-s,--shred]                            Shreds the files instead of merely deleting them. NOTE: Depending on amount and
                                        sizes of the files, this may take a significantly longer number of time!
');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->select('-s,--shred')->isOptional()->isBoolean()
                     ->select('-t,--timestamp')->isOptional()->or('date')->isNatural()
                     ->select('-d,--date')->isOptional()->or('timestamp')->isDate()
                     ->validate();


// Convert timestamp to ISO-8601 date
if ($argv['timestamp']) {
    $argv['date'] = DateTime::new($argv['timestamp'])->format('Y-m-d H:i:s');
}


// Start clearing!
if ($argv['date']) {
    // Find all files before this date and delete / shred them
    // TODO Implement
    throw new UnderConstructionException();

} else {
    if ($argv['shred']) {
        File::new(DIRECTORY_DATA . 'tmp', Restrictions::new(DIRECTORY_DATA, true))->shred();
        File::new(DIRECTORY_DATA . 'content/cdn/tmp', Restrictions::new(DIRECTORY_DATA, true))->shred();
        File::new(DIRECTORY_DATA . 'cache', Restrictions::new(DIRECTORY_DATA, true))->shred();

    } else {
        File::new(DIRECTORY_DATA . 'tmp', Restrictions::new(DIRECTORY_DATA, true))->delete();
        File::new(DIRECTORY_DATA . 'content/cdn/tmp', Restrictions::new(DIRECTORY_DATA, true))->delete();
        File::new(DIRECTORY_DATA . 'cache', Restrictions::new(DIRECTORY_DATA, true))->delete();
    }
}


// Done!
Log::success('Cleared temporary and cache files');
