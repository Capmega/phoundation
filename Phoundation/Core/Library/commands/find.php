<?php

declare(strict_types=1);

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

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Utils\Strings;

$argv = ArgvValidator::new()
    ->select('command')->isPrintable()
    ->validate();

$results = Find::new(DIRECTORY_ROOT . 'commands/')
    ->setPath(DIRECTORY_ROOT . 'commands/')
    ->setName($argv['command'] . '.php')
    ->executeReturnIterator();

if ($results->getCount()) {
    foreach ($results as $result) {
        $result = Strings::from($result, DIRECTORY_ROOT . 'commands/');
        $result = Strings::until($result, '.php');
        $result = str_replace('/', ' ', $result);

        Log::cli($result);
    }
} else {
    Log::warning(tr('Could not find command ":command"', [
        ':command' => $argv['command']
    ]));
}
