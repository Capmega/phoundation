<?php

/**
 * Command databases memcached dump values
 *
 * This command will dump all values available in the memcached server for the specified connector to STDOUT
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\Exception\ConnectorNotExistsException;

CliDocumentation::setUsage('./pho databases memcached dump values -c sessions');

CliDocumentation::setHelp('This command will dump all currently available keys on the specified memcached instance to 
STDOUT.


ARGUMENTS


-c, --connector CONNECTOR_NAME          The memcached connector to connect to');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-c,--connector' => [
            'word'   => function ($word) {
                return Connectors::new()->load()->autoCompleteFind($word);
            },
            'noword' => function () {
                return Connectors::new()->load()->autoCompleteFind();
            }
        ],
    ],
]);


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-c,--connector', true)->isVariable()
                     ->validate();


try {
    // Dump all values
    foreach (mc($argv['connector'])->getAllKeys() as $key) {
        Log::printr(mc($argv['connector'])->get($key), echo_header: false);
        Log::cli();
    }

} catch (ConnectorNotExistsException $e) {
    throw $e->makeWarning();
}
