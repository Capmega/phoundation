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

foreach ($results as $result) {
    $result = Strings::from($result, DIRECTORY_ROOT . 'commands/');
    Log::cli($result);
}
