<?php

declare(strict_types=1);

use Phoundation\Cli\CliDocumentation;
use Phoundation\Exception\UnderConstructionException;

CliDocumentation::setUsage('./pho databases mysql method [OPTIONS]

./pho databases mysql list databases [OPTIONS]
./pho databases mysql list tables [OPTIONS]
./pho databases mysql list foreign-keys [OPTIONS]
./pho databases mysql change default-charset [OPTIONS]
./pho databases mysql scan SERVER');

CliDocumentation::setHelp('This command contains various methods to assist with various mysql operations



METHODS

list databases                   - List the available databases for the used
                                   connector

list tables                      - List the available tables for the used
                                   connector and database

list foreign-keys                - List the available foreign keys for the used
                                   connector and database

scan SERVER                      - Register all databases from the specified
                                   server





OPTIONS

--all                            - Apply to all databases

--databases                      - Apply to specified databases. NOTE: Specified
                                   databases will have to exist on the mysql
                                   server specified by the used connector!

--connector                      - Use a different database connector.
                                   By default, "core" is used, which will use
                                   connector $_CONFIG[db][core]

--database                       - Use a different database. By default, the
                                   first database found in the used connector
                                   will be used

replication

    list

        servers                  - List all currently registered servers

        databases                - List all currently registered databases

    add-master                   - Add new master

        --hostname HOSTNAME      - The hostname of the master server
                                  (e.g. s1.s.capmega.com)

        --database DATABASE      - The name/id of the database to replicate

        --force-channel          - Force slave sarver to create channel
                                   on mysql

    backup                       - Make remote backup on replication server
                                   for databases that are replicating');

throw new UnderConstructionException();

load_libs('mysql,mysqlr');
cli_only();

$all       = cli_argument('--all');
$connector = cli_argument('--connector', true);
$databases = cli_argument('--databases', 'all');

/*
 * Check required connector, load connector data
 */
if (!$connector) {
    $connector = 'core';
}

if (empty($_CONFIG['db'][$connector])) {
    throw new CoreException(tr('Specified connector ":connector" does not exist. Please check the configuration $_CONFIG[db][CONNECTORNAME]', [':connector' => $connector]), 'not-exists');
}

$connector_data = $_CONFIG['db'][$connector];

/*
 * Check required database
 */
if ($all) {
    if ($databases) {
        throw new CoreException(tr('Both --databases and --all have been specified. Use either one or the other.'), 'invalid');
    }

    /*
     * Use all databases available on the server specified by this connector
     */
    $databases = sql_query('SELECT `TABLE_SCHEMA` FROM `information_schema`.`TABLES` GROUP BY `TABLE_SCHEMA`', null, null, $connector);

} else {
    if (!$databases) {
        /*
         * Use the default database for the specified connector
         * If not, use the list of specified databases
         */
        $databases = [$_CONFIG['db'][$connector]['db']];
    }

    /*
     * Confirm that the required databases exist on the server specified by the connector
     */
    foreach ($databases as $database) {
        $exist = sql_get('SELECT `TABLE_SCHEMA` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = :TABLE_SCHEMA GROUP BY `TABLE_SCHEMA` LIMIT 1', 'TABLE_SCHEMA', [':TABLE_SCHEMA' => $database], $connector);

        if (!$exist) {
            throw new CoreException(tr('Specified database ":database" does not exist', [':database' => $database]), 'not-exists');
        }
    }
}

/*
 * Process required methods
 */
switch (cli_method()) {
    case 'check':
        /*
         * Perform MySQL checks
         */
        switch (cli_method(1)) {
            case 'timezone':
                // FALLTHROUGH
            case 'timezones':
                if (!$count = sql_get('SELECT COUNT(*) AS `count` FROM mysql.time_zone_name;')) {
                    throw new CoreException('MySQL timezone tables are empty');
                }

                log_console($count, '', 'white');
                break;

            case '':
                throw new CoreException(tr('No sub method specified for method ":method"', [':method' => cli_method()]), 'not-specified');

            default:
                throw new CoreException(tr('Unknown sub method ":submethod" specified for method ":method"', [
                    ':method'    => cli_method(),
                    ':submethod' => cli_method(1),
                ]),                     'unknown-method');
        }

        break;

    case 'change':
        // FALLTHROUGH
    case 'alter':
        switch (cli_argument(1)) {
            case 'charset':
                // FALLTHROUGH
            case 'default-charset':
                $tables     = cli_argument('--tables', 'all');
                $all_tables = cli_argument('--all-tables');
                $charset    = cli_argument(2);

                cli_no_arguments_left();

                if ($tables and $all_tables) {
                    throw new CoreException(tr('Both --all-tables and --tables have been specified. --all-tables is not needed, if --tables is not specified, all tables will be displayed'), 'invalid');
                }

                $tables = s_get_tables($tables, $databases);

                if ($all) {
                    log_console(tr('Changing default charset for tables (:count) for connector/host/database ":connector/:host/all databases"', [
                        ':connector' => $connector,
                        ':host'      => $_CONFIG['db'][$connector]['host'],
                        ':count'     => $tables->rowCount(),
                    ]),         'white');

                } else {
                    log_console(tr('Changing default charset for tables (:count) in connector/host/database ":connector/:host/:database"', [
                        ':connector' => $connector,
                        ':host'      => $_CONFIG['db'][$connector]['host'],
                        ':database'  => $database,
                        ':count'     => $tables->rowCount(),
                    ]),         'white');
                }

                log_console(Strings::size(tr('Database'), 40) . tr('Table'), 'cyan');

                while ($table = sql_fetch($tables)) {
                    log_console(Strings::size($table['TABLE_SCHEMA'], 40) . $table['TABLE_NAME']);
                    sql_query('ALTER TABLE `' . $table['TABLE_SCHEMA'] . '`.`' . $table['TABLE_NAME'] . '` CONVERT TO CHARACTER SET "' . $charset . '"', null, null, $connector);
                }
        }

        break;

    case 'list':
        switch (cli_method(1)) {
            case 'timzeone':
                // FALLTHROUGH

            case 'timzeones':
            case 'db':
                // FALLTHROUGH
            case 'dbs':
                // FALLTHROUGH
            case 'databases':
                cli_no_arguments_left();

                if (VERBOSE) {
                    log_console(tr('Databases (:count) for connector/host ":connector/:host"', [
                        ':connector' => $connector,
                        ':host'      => $_CONFIG['db'][$connector]['host'],
                        ':count'     => $databases->rowCount(),
                    ]),         'white');
                }

                log_console(tr('Database'), 'cyan');

                foreach ($databases as $database) {
                    log_console($database);
                }

                break;

            case 'tables':
                $tables     = cli_argument('--tables', 'all');
                $all_tables = cli_argument('--all-tables');

                cli_no_arguments_left();

                if ($tables and $all_tables) {
                    throw new CoreException(tr('Both --all-tables and --tables have been specified. --all-tables is not needed, if --tables is not specified, all tables will be displayed'), 'invalid');
                }

                $tables = s_get_tables($tables, $databases);

                if (VERBOSE) {
                    if ($all) {
                        log_console(tr('Tables (:count) for connector/host/database ":connector/:host/all databases"', [
                            ':connector' => $connector,
                            ':host'      => $_CONFIG['db'][$connector]['host'],
                            ':count'     => $tables->rowCount(),
                        ]),         'white');

                    } else {
                        log_console(tr('Tables (:count) for connector/host/database ":connector/:host/:database"', [
                            ':connector' => $connector,
                            ':host'      => $_CONFIG['db'][$connector]['host'],
                            ':database'  => $database,
                            ':count'     => $tables->rowCount(),
                        ]),         'white');
                    }
                }

                log_console(Strings::size(tr('Database'), 40) . tr('Table'), 'cyan');

                while ($table = sql_fetch($tables)) {
                    log_console(Strings::size($table['TABLE_SCHEMA'], 40) . $table['TABLE_NAME']);
                }

                break;

            case 'fk':
                // FALLTHROUGH
            case 'foreign-key':
                // FALLTHROUGH
            case 'foreign-keys':
                $filter      = cli_argument('--filter', true);
                $reference   = cli_argument('--reference', true);
                $constraint  = cli_argument('--constraint', true);
                $database    = cli_argument('--database', true);
                $foreign_key = cli_argument('--foreign-key', true, cli_argument('--fk', true));

                cli_no_arguments_left();

                $where   = ' WHERE `referenced_table_name` IS NOT NULL ';
                $execute = [];

                if ($foreign_key) {
                    $having[]                = ' `foreign_key` LIKE :foreign_key ';
                    $execute[':foreign_key'] = '%' . $foreign_key . '%';
                }

                if ($database) {
                    $having[]             = ' `database` LIKE :database ';
                    $execute[':database'] = '%' . $database . '%';
                }

                if ($constraint) {
                    $having[]               = ' `constraint` LIKE :constraint ';
                    $execute[':constraint'] = '%' . $constraint . '%';
                }

                if ($reference) {
                    $having[]              = ' `references` LIKE :reference ';
                    $execute[':reference'] = '%' . $reference . '%';
                }

                $r = sql_query('SELECT `information_schema`.`key_column_usage`.`constraint_name`  AS `constraint`,
                                       `information_schema`.`key_column_usage`.`constraint_schema` AS `database`,
                                       CONCAT(`information_schema`.`key_column_usage`.`table_name`           , ".", `information_schema`.`key_column_usage`.`column_name`)            AS `foreign_key`,
                                       CONCAT(`information_schema`.`key_column_usage`.`referenced_table_name`, ".", `information_schema`.`key_column_usage`.`referenced_column_name`) AS `references`

                                FROM   `information_schema`.`key_column_usage` ' . $where . (empty($having) ? '' : ' HAVING ' . implode(' AND ', $having)), $execute);

                if ($r->rowCount()) {
                    log_console(tr('Listing ":count" foreign key references', [':count' => $r->rowCount()]), 'white');
                    log_console(tr('Database           Constraint name                          Foreign key                              References'), 'cyan');

                    while ($row = sql_fetch($r)) {
                        $row = Strings::size($row['database'], 18, ' ') . ' ' . Strings::size($row['constraint'], 40, ' ') . ' ' . Strings::size($row['foreign_key'], 40, ' ') . ' ' . $row['references'];

                        if (!$filter or strstr($row, $filter)) {
                            log_console($row);
                        }
                    }

                } else {
                    log_console(tr('No foreign key references found'), 'white');
                }

                break;

            case '':
                throw new CoreException(tr('No sub method specified for method ":method"', [':method' => cli_method()]), 'not-specified');

            default:
                throw new CoreException(tr('Unknown sub method ":submethod" specified for method ":method"', [
                    ':method'    => cli_method(),
                    ':submethod' => cli_method(1),
                ]),                     'unknown-method');
        }

        break;

    case 'replication':
        switch (cli_method(1)) {
            case 'check':
                /*
                 * The replicator checker runs over Cron each 1 minute
                 * If its already running it won't run
                 */
                cli_run_once_local();
                log_console(tr('Checking databases replication status'), 'white');

                /*
                 * Get databases
                 */
                $databases = sql_query('SELECT     `databases`.`id`,
                                                   `databases`.`status`,
                                                   `databases`.`replication_status`,
                                                   `databases`.`id`         AS `databases_id`,
                                                   `databases`.`name`       AS `database`,
                                                   `databases`.`servers_id` AS `servers_id`

                                        FROM       `databases`

                                        LEFT JOIN  `servers`
                                        ON         `databases`.`servers_id` = `servers`.`id`

                                        WHERE      `databases`.`status` IS NULL
                                        AND        `servers`.`status`   IS NULL

                                        ORDER BY   `servers`.`id`');

                load_libs('ssh,servers');

                while ($database = sql_fetch($databases)) {
                    try {
                        log_console(tr('Checking database ":database"', [':database' => $database['database']]));
                        mysqlr_monitor_database($database);
                        sleep(1);

                    } catch (Exception $e) {
                        $message = tr('mysqlr_monitor_database(): Failed to on database ":database", encountered error ":error"', [
                            ':database' => $database['database'],
                            ':error'    => $e->getMessage(),
                        ]);

                        mysqlr_add_log([
                                           'databases_id' => $database['id'],
                                           'type'         => 'other',
                                           'message'      => $message,
                                       ]);
                        mysqlr_update_replication_status($database, 'error');
                        log_console($message, 'red');
                        sleep(1);
                    }
                }

                /*
                 *
                 */
                log_console(tr('Finished checking replication'));
                break;

            case 'list':
                switch (cli_method(2)) {
                    case 'servers':
                        break;

                    case 'databases':
                        break;

                    case '':
                        throw new CoreException(tr('No sub method specified for the methods ":method0" ":method1"', [
                            ':method0' => cli_method(),
                            ':method1' => cli_method(1),
                        ]),                     'no-method');

                    default:
                        throw new CoreException(tr('Unknown sub method ":method2" specified for the methods ":method0" ":method1"', [
                            ':method0' => cli_method(),
                            ':method1' => cli_method(1),
                            ':method2' => cli_method(2),
                        ]),                     'unknown-method');
                }

                break;

            case 'add-master':
                /*
                 * Get arguments and needed data
                 */
                $server_restrictions                 = cli_arguments('--hostname,--database');
                $force_channel                       = cli_arguments('--force-channel');
                $server_restrictions['root_db_user'] = 'root';
                $server_restrictions['arguments']    = '-q';
                cli_no_arguments_left();

                /*
                 * Setup master
                 */
                log_console(tr('Preparing master'), 'white');
                $master = mysqlr_master_replication_setup($server_restrictions);

                /*
                 * Setup slave with master info
                 */
                log_console(tr('Preparing slave'), 'white');
                $master['force_channel'] = $force_channel;
                $slave                   = mysqlr_slave_replication_setup($master);
                break;

            case 'pause':
                /*
                 * Get arguments and needed data
                 */
                $server_restrictions                 = cli_arguments('--database');
                $server_restrictions['root_db_user'] = 'root';
                $server_restrictions['arguments']    = '-q';
                cli_no_arguments_left();

                /*
                 * Pause database
                 */
                mysqlr_pause_replication($server_restrictions['database']);
                break;

            case 'resume':
                /*
                 * Get arguments and needed data
                 */
                $server_restrictions                 = cli_arguments('--database');
                $server_restrictions['root_db_user'] = 'root';
                $server_restrictions['arguments']    = '-q';
                cli_no_arguments_left();

                /*
                 * Pause database
                 */
                mysqlr_resume_replication($server_restrictions['database']);
                break;

            case 'backup':
                /*
                 * No arguments required
                 */
                cli_no_arguments_left();

                /*
                 * Make backups for every replicating database
                 */
                mysqlr_full_backup();
                break;

            case '':
                throw new CoreException(tr('No sub method specified for the method ":method"', [':method' => cli_method()]), 'no-method');

            default:
                throw new CoreException(tr('Unknown sub method ":submethod" specified for the method ":method"', [
                    ':method'    => cli_method(),
                    ':submethod' => cli_method(1),
                ]),                     'unknown-method');
        }

        break;

    case 'scan':
        $server_restrictions = cli_argument();

        cli_no_arguments_left();
        load_libs('mysql,servers');

        $count = mysql_register_databases($server_restrictions);

        log_console(tr('Added ":count" databases for server ":server"', [
            ':server' => $server_restrictions['name'],
            ':count'  => $count,
        ]),         'green');
        break;

    case '':
        throw new CoreException(tr('No method specified'), 'not-specified');

    default:
        throw new CoreException(tr('Unknown method ":method" specified', [':method' => cli_method()]), 'unknown-method');
}


/*
 *
 */
function s_get_tables($tables, $databases)
{
    global $all, $all_tables, $connector;

    try {
        if ($all) {
            /*
             * Show from all databases
             */
            if ($tables) {
                /*
                 * Show specified tables from all databases
                 */
                $tables = sql_in($tables);
                $tables = sql_query('SELECT CONCAT(`TABLE_SCHEMA`, `TABLE_NAME`) AS `id`, `TABLE_SCHEMA`, `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_NAME` IN (' . implode(',', array_keys($tables)) . ') GROUP BY `TABLE_NAME`', $tables, null, $connector);

            } else {
                /*
                 * Show all tables from all databases
                 */
                $tables = sql_query('SELECT CONCAT(`TABLE_SCHEMA`, `TABLE_NAME`) AS `id`, `TABLE_SCHEMA`, `TABLE_NAME` FROM `information_schema`.`TABLES` GROUP BY `TABLE_NAME`', null, null, $connector);
            }

        } else {
            /*
             * Show all tables from specified databases
             */
            $databases = sql_in($databases);
            $databases = sql_list('SELECT `TABLE_SCHEMA` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` IN (' . implode(',', array_keys($databases)) . ') GROUP BY `TABLE_SCHEMA` ORDER BY `TABLE_SCHEMA` ASC', $databases, null, $connector);
            $databases = sql_in($databases);

            if ($tables) {
                /*
                 * Show specified tables from specified databases
                 */
                $tables = sql_in($tables, ':table');
                $in     = array_merge($databases, $tables);
                $tables = sql_query('SELECT CONCAT(`TABLE_SCHEMA`, `TABLE_NAME`) AS `id`, `TABLE_SCHEMA`, `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` IN (' . implode(',', array_keys($databases)) . ') AND `TABLE_NAME` IN (' . implode(',', array_keys($tables)) . ')  GROUP BY `TABLE_SCHEMA`, `TABLE_NAME` ORDER BY `TABLE_SCHEMA` ASC, `TABLE_NAME` ASC', $in, null, $connector);

            } else {
                /*
                 * Show specified tables from specified databases
                 */
                $tables = sql_query('SELECT CONCAT(`TABLE_SCHEMA`, `TABLE_NAME`) AS `id`, `TABLE_SCHEMA`, `TABLE_NAME` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` IN (' . implode(',', array_keys($databases)) . ') GROUP BY `TABLE_SCHEMA`, `TABLE_NAME` ORDER BY `TABLE_SCHEMA` ASC, `TABLE_NAME` ASC', $databases, null, $connector);
            }
        }

        return $tables;

    } catch (Exception $e) {
        throw new CoreException(tr('s_get_tables(): Failed'), $e);
    }
}


?>
