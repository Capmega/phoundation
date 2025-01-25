<?php

/**
 * Service services test wait
 *
 * This service will run by waiting for the specified amount of time and then terminate
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Os\Services\Service;
use Phoundation\Os\Services\SystemD\SystemDService;


CliDocumentation::setAutoComplete(Service::getAutoComplete([
    'arguments' => [
        '-s,--seconds' => true
    ]
]));

CliDocumentation::setUsage('./pho services test wait 40
');

CliDocumentation::setHelp('This service will run by waiting for the specified amount of time and then terminate


ARGUMENTS


See services systemctl -H for basic service commands 


-


OPTIONAL ARGUMENTS


-s,--seconds SECONDS                    The amount of seconds to wait before terminating');


// Get arguments
$argv = ArgvValidator::new()
                     ->select('-s,--seconds', true)->isOptional()->isNumeric()->isPositive()
                     ->validate(false);


$service = SystemDService::new()
                         ->setCycleGcChance(5)
                         ->setCycleSleep(5000)
                         ->execute(function(int $pid) use ($argv) {
    // This is the actual code for this service that will execute
    Log::action(tr('Starting test service "countdown"'), 10);

    while($argv['seconds'] > 0) {
        Log::cli($argv['seconds']);
        sleep(1);
    }
});
