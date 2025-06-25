<?php

/**
 * Command databases erase
 *
 * This command will erase the specified row from the specified database
 *
 * This command requires a connector, optionally a database, a table, a column, and a value. It will then find said
 * column, erase it, and then make sure its metadata and meta-history is erased too
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Databases\Connectors\Connectors;


CliDocumentation::setUsage('./pho databases erase -c system -t filesystem_mounts -k id -v 2348972349
./pho databases erase -c system -d phoundation -t filesystem_mounts -k id -v 2348972349');

CliDocumentation::setHelp('This command will erase the specified row from the specified database

This command requires a connector, optionally a database, a table, a column, and a value. It will then find said column, 
erase it. If the table is a DataEntry type table, it will then make sure its metadata and meta-history is erased too

 
ARGUMENTS

-k, --column                            The column which will be used to select the record that should be erased

-t, --table                             The table from which to erase a record

-v, --value                             The value that the specified column should have. If the column in the table has 
                                        the given value, the row will be erased 


OPTIONAL ARGUMENTS


[-b, --database DATABASE]               The database to use
 
[-c, --connector CONNECTOR]             The database connector to use. Must either exist in configuration or in the
                                        system database as a database connector. If not specified, the system connector 
                                        will be assumed');

throw new \Phoundation\Exception\UnderConstructionException('Implement auto complete correctly');
CliDocumentation::setAutoComplete([
      'arguments' => [
          '-v,--value'     => true,
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
          '-t,--table'     => [
              'word'   => function ($word) {
                  return sql()->getSchemaObject()->getDatabaseObject($database)->getColumns()->FilterOn($word);
              },
              'noword' => function ($word) {
                  return sql()->getSchemaObject()->getDatabaseObject($database)->getColumns();
              },
          ],
          '-k,--column'    => [
              'word'   => function ($word) {
                  return sql()->getSchemaObject()->getDatabaseObject($database)->getTableObject($table)->getColumns()->FilterOn($word);
              },
              'noword' => function ($word) {
                  return sql()->getSchemaObject()->getDatabaseObject($database)->getTableObject($table)->getColumns();
              },
          ],
      ],
  ]);


// Validate arguments
throw new \Phoundation\Exception\UnderConstructionException('Implement validation correctly');
$argv = ArgvValidator::new()
                     ->select('-c,--connector', true)->isOptional('system')->sanitizeLowercase()->isInArray(Connectors::new()->load(null, true, true)->getAllRowsSingleColumn('name'))
                     ->select('-d,--database', true)->isVariable()
                     ->select('-k,--column', true)->isVariable()
                     ->select('-t,--table', true)->isVariable()
                     ->select('-v,--value', true)->isVariable()
                     ->validate();



// Select the record and detect if record is from a DataEntry table
$source = sql()->getRow('');
$class  = DataEntry::detectClassFromArray($source);

if ($class) {
    $object = $class::newFromSource($source);
}


// Erase the record
sql()->delete();


// Is the record from a DataEntry table? Erase the metadata too
$object->getMetaObject()->erase();


// Done!
Log::success(ts('Finished erasing record ":value" from table ":table" from database ":database"', [
    ':database' => $argv['database'],
    ':table'    => $argv['table'],
    ':value'    => $argv['key'] . '=' . $argv['value'],
]), 10);
