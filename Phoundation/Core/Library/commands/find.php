<?php

/**
 * Command "find"
 *
 * This command can find other commands in the commands structure
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Strings;

// Get arguments
$argv = ArgvValidator::new()
    ->select('command')->isPrintable()
    ->validate();

// Find the files that match the specified command
$results = Find::new(FsDirectory::getCommands(false, 'command find'))
    ->setName($argv['command'] . '.php')
    ->executeReturnIterator();


// Display the files
if ($results->getCount()) {
    foreach ($results as $result) {
        $result = Strings::from($result, DIRECTORY_COMMANDS);
        $result = Strings::until($result, '.php');
        $result = str_replace('/', ' ', $result);

        Log::cli($result);
    }

} else {
    Log::warning(tr('Could not find command ":command"', [
        ':command' => $argv['command']
    ]));
}
