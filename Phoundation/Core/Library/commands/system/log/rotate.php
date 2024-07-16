<?php

/**
 * Command system/log/rotate
 *
 * This script can be used to test the authentication for the specified user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\Exception\FileExistsException;

CliDocumentation::setUsage('./pho system log rotate
');

CliDocumentation::setHelp('This command will rotate the current log file to a zipped backup file


ARGUMENTS


');


// Get command line arguments
$argv = ArgvValidator::new()
                     ->validate();


// Rotate the log
try {
    Log::rotate();

} catch (FileExistsException $e) {
    throw $e->makeWarning();
}


// Done!
Log::success('Finished rotating log file');
