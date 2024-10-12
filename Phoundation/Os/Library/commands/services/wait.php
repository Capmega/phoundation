<?php

/**
 * Service services wait
 *
 * This service will run by waiting for the specified amount of time and then terminate
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliCommand;
use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Processes\ProcessServiceThis;
use Phoundation\Os\Processes\ProcessThis;


CliDocumentation::setAutoComplete([
    'positions' => [
        0 => true,
    ]
]);

CliDocumentation::setUsage('./pho services wait 40');

CliDocumentation::setHelp('This service will run by waiting for the specified amount of time and then terminate


ARGUMENTS


-


OPTIONAL ARGUMENTS


SECONDS                                 The amount of seconds to wait before terminating');


// Get arguments
$argv = ArgvValidator::new()
    ->select('seconds')->isOptional()->isNumeric()->isPositive()
    ->validate();


$p = ProcessThis::new();
showdie();

$service = ProcessServiceThis::new()
                             ->ensure(function (int $pid) {
                                 // Done!
                                 Log::success(tr('Started service ":service" with PID ":pid"', [
                                     ':pid'     => $pid,
                                     ':service' => CliCommand::getCommands()
                                 ]));
                             })
                             ->execute(function () use ($argv) {
                                 // Wait for the specified amount of time, and terminate
                                 sleep($argv['seconds']);


                                 // Done!
                                 Log::success(tr('Finished waiting ":seconds" seconds', [
                                     ':seconds' => $argv['seconds']
                                 ]));
                             });
