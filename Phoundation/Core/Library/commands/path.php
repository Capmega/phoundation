<?php

/**
 * Command "path"
 *
 * This command will print the real path of the file for the requested command
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Strings;

CliDocumentation::setUsage('./pho path');

CliDocumentation::setHelp('The find script will find the exact command you are looking for and display the path where 
the PHP file for that command is located


ARGUMENTS


[-l,--like]                             If specified will return all commands that appear like *filter*');


$argv = ArgvValidator::new()
                     ->select('command')->isPrintable()
                     ->select('-l,--like')->isOptional()->isBoolean()
                     ->validate();


// Are we using like? Add *
$like = $argv['like'] ? '*' : null;


// Search and initialize results iterator
$results     = new Iterator();

$files       = Find::new(FsDirectory::getCommandsObject(false, 'command path'))
                   ->setName($like . $argv['command'] . '.php' . $like)
                   ->executeReturnIterator();

$directories = Find::new(FsDirectory::getCommandsObject(false, 'command path'))
                   ->setName($like . $argv['command'] . $like)
                   ->setType('d')
                   ->executeReturnIterator();


// Merge files and directories and process the results
$files->addSource($directories);


// Display the commands and their paths
if ($files->getCount()) {
    // Add path to results iterator
    foreach ($files as $path) {
        $result = Strings::from($path, DIRECTORY_COMMANDS);
        $result = Strings::until($result, '.php');
        $result = str_replace('/', ' ', $result);
        $path   = FsFile::new($path, FsRestrictions::getReadonly(DIRECTORY_COMMANDS))->getRealPath();
        $path   = Strings::from($path, DIRECTORY_ROOT);

        $results->add(['path' => $path, 'command' => $result]);
    }

    // Sort results iterator for easier result finding
    $results->uasort(function (array $a, array $b) {
        if ($a['command'] > $b['command']) {
            return 1;
        }

        if ($a['command'] < $b['command']) {
            return -1;
        }

        return 0;
    });

    // Display iterator results table
    $results->displayCliTable([
        'command' => tr('Command'),
        'path'    => tr('Path')
    ]);

} else {
    Log::warning(tr('Could not find command ":command"', [
        ':command' => $argv['command']
    ]));
}
