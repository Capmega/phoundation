<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\Commands\Pgrep;
use Phoundation\Os\Processes\Exception\MonitorException;


/**
 * Script monitor/process
 *
 * This script will monitor the specified process (by name) and alert and restart when it stops
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */
CliDocumentation::setUsage('./pho monitor process PROCESS_NAME');

CliDocumentation::setHelp('This command will monitor the specified process (by name) and alert and restart it when it stops');


// Get the arguments
$argv = ArgvValidator::new()
                     ->select('process')->isVariable()
                     ->select('-m,--minimum', true)->isOptional()->isNatural()
                     ->validate();


// Get process ids
$pids = Pgrep::new()->do($argv['process']);


// Is it up?
if (!$pids) {
    throw MonitorException::new(tr('The process ":process" is no longer active on this server', [
        ':process' => $argv['process'],
    ]))->makeWarning();
}

if (count($pids) < $argv['minimum']) {
    throw MonitorException::new(tr('The process ":process" has ":count" processes available, less than the minimum number of ":minimum"', [
        ':count'   => count($pids),
        ':minimum' => $argv['minimum'],
        ':process' => $argv['process'],
    ]))->makeWarning();
}
