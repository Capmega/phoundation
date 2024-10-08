<?php

/**
 * Script system/databases/export
 *
 * This script will export the specified database to the specified database file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Export;
use Phoundation\Filesystem\Directory;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Utils\Utils;


$restrictions = Restrictions::writable([
                                           DIRECTORY_DATA . 'sources/',
                                           DIRECTORY_TMP,
                                       ], tr('Export'));

CliDocumentation::setUsage('./pho system databases export -d mysql -b system -f system.sql');

CliDocumentation::setHelp('This command will export the specified database to the specified database dump file


ARGUMENTS


-c / --connector CONNECTOR              The database connector to use. Must either exist in configuration or in the
                                        system database as a database connector. If not specified, the system connector 
                                        will be assumed

[-b / --database DATABASE]              The database to export.

-f / --file FILE                        The file which to export to

[-g / --gzip]                           Will produce a gzip compressed database dump file

[-t / --timeout TIMEOUT]                Timeout in integer seconds before the process will be terminated due to timeout
                                        (defaults to 3600)');

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-t,--timeout'   => true,
                                          '-g,--gzip'      => false,
                                          '--file'         => [
                                              'word'   => function ($word) use ($restrictions) { return Directory::new(DIRECTORY_DATA . 'sources/', $restrictions)->scan($word . '*.sql'); },
                                              'noword' => function () use ($restrictions) { return Directory::new(DIRECTORY_DATA . 'sources/', $restrictions)->scan('*.sql'); },
                                          ],
                                          '-c,--connector' => [
                                              'word'   => function ($word) { return Connectors::new()->load(true, true)->keepMatchingValuesStartingWith('sys', column: 'name')->getAllRowsSingleColumn('name'); },
                                              'noword' => function () { return Connectors::new()->load(true, true)->getAllRowsSingleColumn('name'); },
                                          ],
                                          '-b,--database'  => [
                                              'word'   => function ($word) { return sql()->listScalar('SHOW DATABASES LIKE :word', [':word' => '%' . $word . '%']); },
                                              'noword' => function () { return sql()->listScalar('SHOW DATABASES'); },
                                          ],
                                      ],
                                  ]);


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-g,--gzip')->isOptional(false)->isBoolean()
                     ->select('-t,--timeout', true)->isOptional(3600)->isInteger()->isMoreThan(0)
                     ->select('-f,--file', true)->isOptional()->isFile([
                                                                           DIRECTORY_DATA . 'sources/',
                                                                           DIRECTORY_TMP,
                                                                       ], $restrictions, false)
                     ->select('-c,--connector', true)->isOptional('system')->sanitizeLowercase()->isInArray(Connectors::new()->load(true, true)->getAllRowsSingleColumn('name'))
                     ->select('-b,--database', true)->isVariable()
                     ->validate();


// Export data for the requested driver
Export::new()
      ->setConnector($argv['connector'])
      ->setDatabase($argv['database'])
      ->setTimeout($argv['timeout'])
      ->setGzip($argv['gzip'])
      ->dump($argv['file']);


// Done!
Log::success(tr('Finished exporting ":type" type database ":database" to file ":file"', [
    ':type'     => $argv['connector'],
    ':file'     => $argv['file'],
    ':database' => $argv['database'],
]));
