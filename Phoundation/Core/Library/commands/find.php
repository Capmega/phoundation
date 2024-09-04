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

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Strings;


CliDocumentation::setUsage('./pho find');

CliDocumentation::setHelp('The find script will find the exact command you are looking for. Just type the sub 
command as an argument and the find script will show all commands that have that sub command


ARGUMENTS


[-l,--like]                             If specified, will return all commands that appear like *filter*');


// Get arguments
$argv = ArgvValidator::new()
                     ->select('command')->isPrintable()
                     ->select('-l,--like')->isOptional()->isBoolean()
                     ->validate();


// Are we using like? Add *
$like = $argv['like'] ? '*' : null;


// Find the files that match the specified command
$files = Find::new(FsDirectory::newCommandsObject())
    ->setName($like . $argv['command'] . '.php' . $like)
    ->executeReturnIterator();

$directories = Find::new(FsDirectory::newCommandsObject())
    ->setName($like . $argv['command'] . $like)
    ->setType('d')
    ->executeReturnIterator();


// Merge files and directories and display the results
$files->addSource($directories);

if ($files->getCount()) {
    foreach ($files as $result) {
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
