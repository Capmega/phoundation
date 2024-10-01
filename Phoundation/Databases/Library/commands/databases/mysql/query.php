<?php

/**
 * Command databases mysql query
 *
 * This command can execute queries over the specified connector and results will be dumped on screen
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
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Sql\Exception\SqlDatabaseDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlNoDatabaseSpecifiedException;
use Phoundation\Databases\Sql\Exception\SqlTableDoesNotExistException;
use Phoundation\Utils\Json;


CliDocumentation::setUsage('./pho databases mysql query CONNECTOR QUERY
./pho databases mysql query "system" "SELECT `id`, `email`, `status` FROM `accounts_user`" 
./pho databases mysql query "system" "SELECT `id`, `user` FROM `user`" -d mysql
./pho databases mysql query "system" "SELECT `id`, `user` FROM `mysql`.`user`" 
./pho databases mysql query "otherconnector" "SELECT COLUMNS FROM TABLE" 
');

CliDocumentation::setHelp('The query command allows you to execute any query on any database and see the results dumped 
to STDOUT in table format. Use -J or --json-output if JSON output is required


ARGUMENTS


CONNECTOR                               The connector to use to execute the query. This will make the command use the 
                                        default database for that connector

QUERY                                   The query to execute


OPTIONAL ARGUMENTS


[-d, --database DATABASE]               If specified, this alternative database will be used instead of the default 
                                        database for the specified connector');


// Validate arguments
$argv = ArgvValidator::new()
                     ->select('connector')->isVariable()->hasMaxCharacters(64)
                     ->select('query')->isPrintable()->hasMaxCharacters(8192)
                     ->select('-d,--database', true)->isOptional()->isVariable()->hasMaxCharacters(64)
                     ->validate();


// Setup the database connector
$connector = Connector::load($argv['connector']);

if ($argv['database']) {
    $connector->setDatabase($argv['database']);
}


// Execute the query and handle basic exceptions
try {
    $results = sql($connector)->listKeyValues($argv['query']);

} catch (SqlDatabaseDoesNotExistException $e) {
    if ($e->getDataKey('database')) {
        throw SqlDatabaseDoesNotExistException::new(tr('Database ":database" does not exist', [
            ':database' => $e->getDataKey('database')
        ]), $e)->makeWarning();
    }

    throw SqlNoDatabaseSpecifiedException::new(tr('No database specified in the connector ":connector" nor in the query itself', [
        ':connector' => $connector->getName()
    ]), $e)->makeWarning();

} catch (SqlTableDoesNotExistException $e) {
    throw SqlDatabaseDoesNotExistException::new(tr('Table ":table" does not exist', [
        ':table' => $e->getDataKey('table')
    ]), $e)->makeWarning();

} catch (SqlException $e) {
    throw $e->makeWarning();
}


// Fix output
switch (count($results)) {
    case 0:
        $results = ['no results'];
        break;

    case 1:
        $results = sql($connector)->get($argv['query']);
        break;
}


// Output
return Log::cli($results);
