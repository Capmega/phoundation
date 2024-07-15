<?php

/**
 * Command databases import
 *
 * This script will import the specified database file into the specified database
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Libraries\Libraries;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Import;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Utils;

$restrictions = FsRestrictions::getReadonly( [
                                           DIRECTORY_DATA . 'sources/',
                                           DIRECTORY_TMP,
                                       ], tr('Import'));

CliDocumentation::setUsage('./pho databases import -d mysql -b system -f system.sql');

CliDocumentation::setHelp('This command will import the specified database file into the specified database


ARGUMENTS


-c / --connector CONNECTOR              The database connector to use. Must either exist in configuration or in the
                                        system database as a database connector. If not specified, the system connector 
                                        will be assumed

-b / --database DATABASE                The database in which to import. Must specify either database, or connector

[--comments COMMENTS]                   The optional comments to add in the versions table if the init is executed right
                                        after import

-f / --file FILE                        The file which to import

[--no-drop]                             If specified, Phoundation will not drop the database before starting the import

[--no-init]                             If specified, Phoundation will not immediately after import execute the init
                                        system. Please note that this may leave the database in an incompatible state!

[-t / --timeout TIMEOUT]                Timeout in integer seconds before the process will be terminated due to timeout
                                        (defaults to 3600)');

CliDocumentation::setAutoComplete([
                                      'arguments' => [
                                          '-f,--file'      => [
                                              'word'   => function ($word) use ($restrictions) { return FsDirectory::new(DIRECTORY_DATA . 'sources/', $restrictions)->scan($word . '*.{sql,sql.gz}'); },
                                              'noword' => function ()      use ($restrictions) { return FsDirectory::new(DIRECTORY_DATA . 'sources/', $restrictions)->scan('*.{sql,sql.gz}'); },
                                          ],
                                          '-c,--connector' => [
                                              'word'   => function ($word) { return Arrays::keepValues(Connectors::new()->load(true, true)->keepMatchingValues('sys', Utils::MATCH_STARTS_WITH, 'name')->getAllRowsSingleColumn('name'), $word); },
                                              'noword' => function () { return Connectors::new()->load(true, true)->keepMatchingValues('sys', Utils::MATCH_STARTS_WITH, 'name')->getAllRowsSingleColumn('name'); },
                                          ],
                                          '-b,--database'  => [
                                              'word'   => function ($word) { return sql()->listScalar('SHOW DATABASES LIKE :word', [':word' => '%' . $word . '%']); },
                                              'noword' => function () { return sql()->listScalar('SHOW DATABASES'); },
                                          ],
                                          '-t,--timeout'   => true,
                                          '--no-init'      => false,
                                          '--no-drop'      => false,
                                      ],
                                  ]);


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('-f,--file', true)->isFile([FsDirectory::getDataSources(), FsDirectory::getTemporary()])
                     ->select('-b,--database', true)->isVariable()
                     ->select('-c,--comments', true)->isOptional()->isPrintable()
                     ->select('-c,--connector', true)->isOptional('system')->sanitizeLowercase()->isInArray(Connectors::new()->load(true, true, true)->getAllRowsSingleColumn('name'))
                     ->select('-t,--timeout', true)->isOptional(3600)->isNatural(true)
                     ->select('--no-drop')->isOptional(false)->isBoolean()
                     ->select('--no-init')->isOptional(false)->isBoolean()
                     ->validate();


// Execute the import for the specified driver
Import::new()
      ->setConnector($argv['connector'], true)
      ->setDatabase($argv['database'])
      ->setDrop(!$argv['no_drop'])
      ->setFile(FsFile::new($argv['file'], $restrictions))
      ->setTimeout($argv['timeout'])
      ->import();


// Execute init?
if ($argv['no_init']) {
    Log::warning(tr('Not executing database init due to "--no-init" argument but this may leave the database in an incompatible state!'));

} else {
    Log::information(tr('Executing database init to ensure database layout is compatible with the current code version'));
    Libraries::initialize(comments: $argv['comments'] ?? tr('Init after database import'));
}


// Done!
Log::success(tr('Finished database import'));
