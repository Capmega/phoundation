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

use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Filesystem\File;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Strings;

$argv = ArgvValidator::new()
                     ->select('command')->isPrintable()
                     ->validate();

// Search and initialize results iterator
$results = new Iterator();
$paths   = Find::new(DIRECTORY_ROOT . 'commands/')
               ->setPath(DIRECTORY_ROOT . 'commands/')
               ->setName($argv['command'] . '.php')
               ->executeReturnIterator();


// Display results
if ($paths->getCount()) {
    // Add path to results iterator
    foreach ($paths as $path) {
        $result = Strings::from($path, DIRECTORY_ROOT . 'commands/');
        $result = Strings::until($result, '.php');
        $result = str_replace('/', ' ', $result);

        $results->add(['path' => File::new($path)->getRealPath(), 'command' => $result]);
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
