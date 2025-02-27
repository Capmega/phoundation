<?php

/**
 * Command databases memcached debug keys dump
 *
 * This command will dump all available keys to STDOUT
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

CliDocumentation::setUsage('./pho databases export -d mysql -b system -f system.sql');

CliDocumentation::setHelp('This command will dump all currently availabe keys on the specified memcached instance to 
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


// Dump all values
foreach (mc($argv['connector'])->getAllKeys() as $key) {
    Log::printr(mc($argv['connector'])->get($key), echo_header: false);
}
