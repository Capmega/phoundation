<?php

/**
 * Command databases memcached dump keys
 *
 * This command will dump all keys available in the memcached server for the specified connector to STDOUT
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

CliDocumentation::setUsage('./pho databases memcached dump keys -c session');

CliDocumentation::setHelp('This command will dump all keys available in the memcached server for the specified connector 
to STDOUT


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


// Dump all keys
try {
    Log::printr(mc($argv['connector'])->getAllKeys(), echo_header: false);

} catch (ConnectorNotExistsException $e) {
    throw $e->makeWarning();
}
