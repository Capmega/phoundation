<?php

/**
 * Command databases import
 *
 * This command will import the specified database file into the specified database
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Import;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\Pho;

CliDocumentation::setUsage('./pho databases import -d mysql -b system -f system.sql');

CliDocumentation::setHelp('This command will import the specified database dump file into the specified database


ARGUMENTS


-c / --connector CONNECTOR              The database connector to use. Must either exist in configuration or in the
                                        system database as a database connector. If not specified, the system connector 
                                        will be assumed

-b / --database DATABASE                The database in which to import. Must specify either database, or connector

[--comments COMMENTS]                   The optional comments to add in the versions table if the init is executed right
                                        after import (NOT YET IMPLEMENTED)

-f / --file FILE                        The file which to import

[--no-drop]                             If specified, Phoundation will not drop the database before starting the import

[--no-init]                             If specified, Phoundation will not immediately after import execute the init
                                        system. Please note that this may leave the database in an incompatible state!

[-t / --timeout TIMEOUT]                Timeout in integer seconds before the process will be terminated due to timeout
                                        (defaults to 3600)');

CliDocumentation::setAutoComplete([
    'arguments' => [
        '--file'         => [
            'word'   => function ($word) { return PhoDirectory::newDataSourcesObject()->scan('/^' . $word . '.*?[.sql|.gz]$/'); },
            'noword' => function ($word) { return PhoDirectory::newDataSourcesObject()->scan('/^' . $word . '.*?[.sql|.gz]$/'); },
        ],
        '-c,--connector' => [
            'word'   => function ($word) {
                return Connectors::new()
                                 ->load(null, true, true)
                                 ->keepMatchingValuesStartingWith($word, column: 'name');
            },
            'noword' => function ($word) {
                return Connectors::new()
                                 ->load(null, true, true)
                                 ->getAllRowsSingleColumn('name');
            },
        ],
        '-b,--database'  => [
            'word'   => function ($word) {
                return sql()->listScalar('SHOW DATABASES LIKE :word', [':word' => '%' . $word . '%']);
            },
            'noword' => function ($word) {
                return sql()->listScalar('SHOW DATABASES');
            },
        ],
        '-t,--timeout'   => true,
        '--comments'     => true,
        '--no-init'      => false,
        '--no-drop'      => false,
    ],
]);


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-f,--file', true)->sanitizeFile([PhoDirectory::newDataSourcesObject(), PhoDirectory::newDataTmpObject()])
                     ->select('-b,--database', true)->isOptional()->isVariable()
                     ->select('--comments', true)->isOptional()->isPrintable()
                     ->select('-c,--connector', true)->isOptional('system')->sanitizeLowercase()->isInArray(Connectors::new()->load(null, true, true)->getAllRowsSingleColumn('name'))
                     ->select('-t,--timeout', true)->isOptional(3600)->isNatural(true)
                     ->select('--no-drop')->isOptional(false)->isBoolean()
                     ->select('--no-init')->isOptional(false)->isBoolean()
                     ->validate();


// Execute the import for the specified driver
Log::information(ts('Executing database import'), 10);

Import::new()
      ->setConnector($argv['connector'])
      ->setDatabase($argv['database'])
      ->setDrop(!$argv['no_drop'])
      ->setFileObject($argv['file'])
      ->setTimeout($argv['timeout'])
      ->import();


// Execute init?
if ($argv['no_init']) {
    Log::warning(ts('Not executing database init due to "--no-init" argument but this may leave the database in an incompatible state!'), 10);

} else {
    Log::information(ts('Executing database init to ensure database layout is compatible with the current code version'), 10);

    Pho::new()
       ->setTimeout(0)
       ->setPhoCommands('project init')
       ->setArguments(['--comments', tr('Init after database import')])
       ->executePassthru();
}


// Done!
Log::success(ts('Finished database import'), 10);
