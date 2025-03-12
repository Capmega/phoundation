<?php

/**
 * Command databases memcached debug keys dump
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

CliDocumentation::setUsage('./pho databases export -d mysql -b system -f system.sql');

CliDocumentation::setHelp('This command will dump the specified key from the memcached server for the specified 
connector to STDOUT


ARGUMENTS


-c, --connector CONNECTOR_NAME          The memcached connector to connect to

-k, --key KEY                           The memcached key for which the value should be dumped');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '-k,--key' => true,
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
                     ->select('key')->hasMaxCharacters(250)
                     ->validate();


try {
    // Dump the value for the specified key
    $value = mc($argv['connector'])->exists($argv['key']);

    if ($value === false) {
        Log::warning(tr('The specified key ":key" does not exist', [
            ':key' => $argv['key']
        ]));

    } else {
        Log::printr(mc($argv['connector'])->get($argv['key']), echo_header: false);
    }

} catch (ConnectorNotExistsException $e) {
    throw $e->makeWarning();
}
