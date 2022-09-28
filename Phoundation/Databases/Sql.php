<?php

namespace Phoundation\Databases;

use Debug;
use Exception;
use PDO;
use PDOStatement;
use Phoundation\Core\CoreException;
use Phoundation\Core\Json\Arrays;
use Phoundation\Core\Json\Strings;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Exception\SqlColumnDoesNotExistsException;
use Phoundation\Databases\Exception\SqlException;

/**
 * Sql class
 *
 * This class is the main SQL database access class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2021 <copyright@capmega.com>
 * @package Phoundation\Databases
 */
class Sql
{
    /**
     * Initialize the library, automatically executed by libs_load()
     *
     * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package
     *
     * @return void
     */
    public static function __constructor()
    {
        try {
            if (!class_exists('PDO')) {
                /*
                 * Wulp, PDO class not available, PDO driver is not loaded somehow
                 */
                throw new SqlException('Sql::library_init(): Could not find the "PDO" class, does this PHP have PDO available?', 'not-available');
            }

            if (!defined('PDO::MYSql::ATTR_USE_BUFFERED_QUERY')) {
                /*
                 * Wulp, MySQL library is not available
                 */
                throw new SqlException('Sql::library_init(): Could not find the "MySQL" library. To install this on Ubuntu derrivates, please type "sudo apt install php-mysql', 'not-available');
            }

        } catch (Exception $e) {
            throw new SqlException('Sql::library_init(): Failed', $e);
        }
    }


    /**
     * Execute specified query
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function query($query, $execute = null, $connector_name = null): PDOStatement
    {
        global $core;

        try {
            log_console(tr('Executing query ":query"', array(':query' => $query)), 'VERYVERBOSE/cyan');

            $connector_name = $this->connectorName($connector_name);
            $connector_name = $this->init($connector_name);
            $query_start = microtime(true);

            if (!is_string($query)) {
                if (is_object($query)) {
                    if (!($query instanceof PDOStatement)) {
                        throw new SqlException(tr('Sql::query(): Object of unknown class ":class" specified where either a string or a PDOStatement was expected', [':class' => get_class($query)]));
                    }

                    /*
                     * PDO statement was specified instead of a query
                     */
                    if ($query->queryString[0] == ' ') {
                        debug_sql($query, $execute);
                    }

                    if (VERYVERBOSE) {
                        log_console(Strings::ends(str_replace("\n", '', debug_sql($query->queryString, $execute, true)), ';'));
                    }

                    $query->execute($execute);
                    return $query;
                }

                throw new SqlException(tr('Sql::query(): Specified query ":query" is not a string', array(':query' => $query)), 'invalid');
            }

            if (!empty($core->register['Sql::debug_queries'])) {
                $core->register['Sql::debug_queries']--;
                $query = ' ' . $query;
            }

            if ($query[0] == ' ') {
                debug_sql($query, $execute);
            }

            if (VERYVERBOSE) {
                log_console(Strings::ends(str_replace("\n", '', debug_sql($query, $execute, true)), ';'));
            }

            if (!$execute) {
                /*
                 * Just execute plain SQL query string.
                 */
                $pdo_statement = $core->sql[$connector_name]->query($query);

            } else {
                /*
                 * Execute the query with the specified $execute variables
                 */
                $pdo_statement = $core->sql[$connector_name]->prepare($query);

                try {
                    $pdo_statement->execute($execute);

                } catch (Exception $e) {
                    /*
                     * Failure is probably that one of the the $execute array values is not scalar
                     */
                    // :TODO: Move all of this to Sql::error()
                    if (!is_array($execute)) {
                        throw new SqlException('Sql::query(): Specified $execute is not an array!', 'invalid');
                    }

                    /*
                     * Check execute array for possible problems
                     */
                    foreach ($execute as $key => &$value) {
                        if (!is_scalar($value) and !is_null($value)) {
                            throw new SqlException(tr('Sql::query(): Specified key ":value" in the execute array for query ":query" is NOT scalar! Value is ":value"', array(':key' => str_replace(':', '.', $key), ':query' => str_replace(':', '.', $query), ':value' => str_replace(':', '.', $value))), 'invalid');
                        }
                    }

                    throw $e;
                }
            }

            if (Debug::enabled()) {
                /*
                 * Get current function / file@line. If current function is actually
                 * an include then assume this is the actual script that was
                 * executed by route()
                 */
                $current = 1;

                if (substr(Debug::currentFunction($current), 0, 4) == 'Sql::') {
                    $current = 2;

                    if (substr(Debug::currentFunction($current), 0, 4) == 'Sql::') {
                        $current = 3;
                    }
                }

                $function = Debug::currentFunction($current);

                if ($function === 'include') {
                    $function = '-';
                }

                $file = Debug::currentFile($current);
                $line = Debug::currentLine($current);

                $core->executedQuery(array('time' => microtime(true) - $query_start,
                    'query' => self::show($query, $execute, true),
                    'function' => $function,
                    'file' => $file,
                    'line' => $line));
            }

            return $pdo_statement;

        } catch (Exception $e) {
            try {
                /*
                 * Let Sql::error() try and generate more understandable errors
                 */
                Sql::error($e, $query, $execute, isset_get($core->sql[$connector_name]));

                if (!is_string($connector_name)) {
                    throw new SqlException(tr('Sql::query(): Specified connector name ":connector" for query ":query" is invalid, it should be a string', array(':connector' => $connector_name, ':query' => $query)), $e);
                }

                Sql::error($e, $query, $execute, isset_get($core->sql[$connector_name]));

            } catch (Exception $e) {
                throw new SqlException(tr('Sql::query(:connector): Query ":query" failed', array(':connector' => $connector_name, ':query' => $query)), $e);
            }
        }
    }


    /**
     * Prepare specified query
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function prepare($query, $connector_name = null)
    {
        global $core;

        try {
            $connector_name = Sql::connectorName($connector_name);
            $connector_name = Sql::init($connector_name);

            return $core->sql[$connector_name]->prepare($query);

        } catch (Exception $e) {
            throw new SqlException('Sql::prepare(): Failed', $e);
        }
    }


    /**
     * Fetch and return data from specified resource
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function fetch($r, $single_column = false, $fetch_style = PDO::FETCH_ASSOC)
    {
        try {
            if (!is_object($r)) {
                throw new SqlException('Sql::fetch(): Specified resource is not a PDO object', 'invalid');
            }

            $result = $r->fetch($fetch_style);

            if ($result === false) {
                /*
                 * There are no entries
                 */
                return null;
            }

            if ($single_column === true) {
                /*
                 * Return only the first column
                 */
                if (count($result) !== 1) {
                    throw new SqlException(tr('Sql::fetch(): Failed for query ":query" to fetch single column, specified query result contains not 1 but ":count" columns', array(':count' => count($result), ':query' => $r->queryString)), 'multiple');
                }

                return array_shift($result);
            }

            if ($single_column) {
                if (!array_key_exists($single_column, $result)) {
                    throw new SqlException(tr('Sql::fetch(): Failed for query ":query" to fetch single column ":column", specified query result does not contain the requested column', array(':column' => $single_column, ':query' => $r->queryString)), 'multiple');
                }

                return $result[$single_column];
            }

            /*
             * Return everything
             */
            return $result;

        } catch (Exception $e) {
            throw new SqlException('Sql::fetch(): Failed', $e);
        }
    }



    /**
     * Execute query and return only the first row
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function get(string $query, array $execute = null, $connector_name = null): array
    {
        try {
            $connector_name = Sql::connectorName($connector_name);
            $result = Sql::query($query, $execute, $connector_name);

            if ($result->rowCount() > 1) {
                throw new SqlException(tr('Sql::get(): Failed for query ":query" to fetch single row, specified query result contains not 1 but ":count" results', array(':count' => $result->rowCount(), ':query' => debug_sql($result->queryString, $execute, true))), 'multiple');
            }

            return Sql::fetch($result);

        } catch (Exception $e) {
            if (is_object($query)) {
                $query = $query->queryString;
            }

            if ((strtolower(substr(trim($query), 0, 6)) !== 'select') and (strtolower(substr(trim($query), 0, 4)) !== 'show')) {
                throw new SqlException('Sql::get(): Query "' . Strings::log(debug_sql($query, $execute, true), 4096) . '" is not a select or show query and as such cannot return results', $e);
            }

            throw new SqlException('Sql::get(): Failed', $e);
        }
    }



    /**
     * Get the value of a single column from a single row for the specified query
     *
     * @param string $query
     * @param array|null $execute
     * @param null $connector_name
     * @return string|null
     */
    public static function getColumn(string $query, string $column, array $execute = null, $connector_name = null): ?string
    {
        $result = self::get($query, $execute);

        if (!$result) {
            // No results
            return null;
        }

        if (count($result) > 1) {
            throw new SqlException('The query ":query" returned ":count" columns while Sql::getColumn() can only return one single column', [':query' => $query, ':count' => count($result)]);
        }

        if (array_key_exists($column, $result)) {
            return $result[$column];
        }

        // Specified column doesn't exist
        throw new SqlColumnDoesNotExistsException('Cannot select column ":column", it does not exist in the result set for query ":query"', [':query' => $query, ':column' => $column]);
    }



    /**
     * Execute query and return only the first row
     *
     * @param
     * @return
     */
    public static function list($query, $execute = null, $numerical_array = false, $connector_name = null)
    {
        try {
            $connector_name = Sql::connectorName($connector_name);

            if (is_object($query)) {
                $r = $query;
                $query = $r->queryString;

            } else {
                $r = Sql::query($query, $execute, $connector_name);
            }

            $retval = array();

            while ($row = Sql::fetch($r)) {
                if (is_scalar($row)) {
                    $retval[] = $row;

                } else {
                    switch ($numerical_array ? 0 : count($row)) {
                        case 0:
                            /*
                             * Force numerical array
                             */
                            $retval[] = $row;
                            break;

                        case 1:
                            $retval[] = array_shift($row);
                            break;

                        case 2:
                            $retval[array_shift($row)] = array_shift($row);
                            break;

                        default:
                            $retval[array_shift($row)] = $row;
                    }
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new SqlException('Sql::list(): Failed', $e);
        }
    }



    /**
     * Connect with the main database
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function init($connector_name = null)
    {
        global $_CONFIG, $core;

        try {
            $connector_name = Sql::connectorName($connector_name);

            if (!empty($core->sql[$connector_name])) {
                /*
                 * Already connected to requested DB
                 */
                return $connector_name;
            }

            /*
             * Get a database configuration connector and ensure its valid
             */
            $connector = Sql::ensureConnector($_CONFIG['db'][$connector_name]);

            /*
             * Set the MySQL rand() seed for this session
             */
            // :TODO: On PHP7, update to random_int() for better cryptographic numbers
            $_SESSION['Sql::random_seed'] = mt_rand();

            /*
             * Connect to database
             */
            log_console(tr('Connecting with SQL connector ":name"', array(':name' => $connector_name)), 'VERYVERBOSE/cyan');
            $core->sql[$connector_name] = Sql::connect($connector);

            /*
             * This is only required for the system connection
             */
            if ((PLATFORM_CLI) and ($core->register['script'] == 'init') and FORCE and !empty($connector['init'])) {
                include(__DIR__ . '/handlers/sql-init-force.php');
            }

            /*
             * Check current init data?
             */
            if (empty($core->register['skip_init_check'])) {
                if (!defined('FRAMEWORKDBVERSION')) {
                    /*
                     * Get database version
                     *
                     * This can be disabled by setting $_CONFIG[db][CONNECTORNAME][init] to false
                     */
                    if (!empty($_CONFIG['db'][$connector_name]['init'])) {
                        try {
                            $r = $core->sql[$connector_name]->query('SELECT `project`, `framework`, `offline_until` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                        } catch (Exception $e) {
                            if ($e->getCode() !== '42S02') {
                                if ($e->getMessage() === 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'offline_until\' in \'field list\'') {
                                    $r = $core->sql[$connector_name]->query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                                } else {
                                    /*
                                     * Compatibility issue, this happens when older DB is running init.
                                     * Just ignore it, since in these older DB's the functionality
                                     * wasn't even there
                                     */
                                    throw $e;
                                }
                            }
                        }

                        try {
                            if (empty($r) or !$r->rowCount()) {
                                log_console(tr('Sql::init(): No versions table found or no versions in versions table found, assumed empty database ":db"', array(':db' => $_CONFIG['db'][$connector_name]['db'])), 'yellow');

                                define('FRAMEWORKDBVERSION', 0);
                                define('PROJECTDBVERSION', 0);

                                $core->register['no-db'] = true;

                            } else {
                                $versions = $r->fetch(PDO::FETCH_ASSOC);

                                if (!empty($versions['offline_until'])) {
                                    if (PLATFORM_HTTP) {
                                        page_show(503, array('offline_until' => $versions['offline_until']));
                                    }
                                }

                                define('FRAMEWORKDBVERSION', $versions['framework']);
                                define('PROJECTDBVERSION', $versions['project']);

                                if (version_compare(FRAMEWORKDBVERSION, '0.1.0') === -1) {
                                    $core->register['no-db'] = true;
                                }
                            }

                        } catch (Exception $e) {
                            /*
                             * Database version lookup failed. Usually, this would be due to the database being empty,
                             * and versions table does not exist (yes, that makes a query fail). Just to be sure that
                             * it did not fail due to other reasons, check why the lookup failed.
                             */
                            load_libs('init');
                            init_process_version_fail($e);
                        }

                        /*
                         * On console, show current versions
                         */
                        if ((PLATFORM_CLI) and VERBOSE) {
                            log_console(tr('Sql::init(): Found framework code version ":frameworkcodeversion" and framework database version ":frameworkdbversion"', array(':frameworkcodeversion' => FRAMEWORKCODEVERSION, ':frameworkdbversion' => FRAMEWORKDBVERSION)));
                            log_console(tr('Sql::init(): Found project code version ":projectcodeversion" and project database version ":projectdbversion"', array(':projectcodeversion' => PROJECTCODEVERSION, ':projectdbversion' => PROJECTDBVERSION)));
                        }


                        /*
                         * Validate code and database version. If both FRAMEWORK and PROJECT versions of the CODE and DATABASE do not match,
                         * then check exactly what is the version difference
                         */
                        if ((FRAMEWORKCODEVERSION != FRAMEWORKDBVERSION) or (PROJECTCODEVERSION != PROJECTDBVERSION)) {
                            Init::processVersionDiff();
                        }
                    }
                }

            } else {
                /*
                 * We were told NOT to do an init check. Assume database framework
                 * and project versions are the same as their code variants
                 */
                define('FRAMEWORKDBVERSION', FRAMEWORKCODEVERSION);
                define('PROJECTDBVERSION', PROJECTCODEVERSION);
            }

            return $connector_name;

        } catch (Exception $e) {
            include(__DIR__ . '/handlers/sql-init-fail.php');
        }
    }


    /**
     * Close the connection for the specified connector
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function close($connector = null)
    {
        global $_CONFIG, $core;

        try {
            $connector = Sql::connectorName($connector);
            unset($core->sql[$connector]);

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::close(): Failed for connector ":connector"', array(':connector' => $connector)), $e);
        }
    }



    /**
     * Connect to database and do a DB version check.
     * If the database was already connected, then just ignore and continue.
     * If the database version check fails, then exception
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function connect(&$connector, $use_database = true)
    {
        global $_CONFIG;

        try {
            array_ensure($connector);
            array_default($connector, 'driver', null);
            array_default($connector, 'host', null);
            array_default($connector, 'user', null);
            array_default($connector, 'pass', null);
            array_default($connector, 'charset', null);

            /*
             * Does this connector require an SSH tunnel?
             */
            if (isset_get($connector['ssh_tunnel']['required'])) {
                include(__DIR__ . '/handlers/sql-ssh-tunnel.php');
            }

            /*
             * Connect!
             */
            $connector['pdo_attributes'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            $connector['pdo_attributes'][PDO::MYSql::ATTR_USE_BUFFERED_QUERY] = !(boolean)$connector['buffered'];
            $connector['pdo_attributes'][PDO::MYSql::ATTR_INIT_COMMAND] = 'SET NAMES ' . strtoupper($connector['charset']);
            $retries = 7;

            while (--$retries >= 0) {
                try {
                    $connect_string = $connector['driver'] . ':host=' . $connector['host'] . (empty($connector['port']) ? '' : ';port=' . $connector['port']) . ((empty($connector['db']) or !$use_database) ? '' : ';dbname=' . $connector['db']);
                    $pdo = new PDO($connect_string, $connector['user'], $connector['pass'], $connector['pdo_attributes']);

                    log_console(tr('Connected with PDO connect string ":string"', array(':string' => $connect_string)), 'VERYVERBOSE/green');
                    break;

                } catch (Exception $e) {
                    /*
                     * This is a work around for the weird PHP MySQL error
                     * "PDO::__construct(): send of 5 bytes failed with errno=32
                     * Broken pipe". So far we have not been able to find a fix
                     * for this but we have noted that you always have to
                     * connect 3 times, and the 3rd time the bug magically
                     * disappears. The work around will detect the error and
                     * retry up to 3 times to work around this issue for now.
                     *
                     * Over time, it has appeared that the cause of this issue
                     * may be that MySQL is chewing on a huge and slow query
                     * which prevents it from accepting new connections. This is
                     * not confirmed yet, but very likely. Either way, this
                     * "fix" still fixes the issue..
                     */
                    log_console(tr('Failed to connect with PDO connect string ":string"', array(':string' => $connect_string)), 'exception');
                    log_console($e->getMessage(), 'exception');

                    $message = $e->getMessage();

                    if (!strstr($message, 'errno=32')) {
                        if ($e->getMessage() == 'ERROR 2013 (HY000): Lost connection to MySQL server at \'reading initial communication packet\', system error: 0') {
                            if (isset_get($connector['ssh_tunnel']['required'])) {
                                /*
                                 * The tunneling server has "AllowTcpForwarding"
                                 * set to "no" in the sshd_config, attempt auto
                                 * fix
                                 */
                                os_enable_ssh_tcp_forwarding($connector['ssh_tunnel']['server']);
                                continue;
                            }
                        }

                        /*
                         * This is a different error. Continue throwing the
                         * exception as normal
                         */
                        throw $e;
                    }

                    /*
                     * This error seems to happen when MySQL is VERY busy
                     * processing queries. Wait a little before trying again
                     */
                    usleep(100000);
                }
            }

            try {
                $pdo->query('SET time_zone = "' . $connector['timezone'] . '";');

            } catch (Exception $e) {
                include(__DIR__ . '/handlers/sql-error-timezone.php');
            }

            if (!empty($connector['mode'])) {
                $pdo->query('SET Sql::mode="' . $connector['mode'] . '";');
            }

            return $pdo;

        } catch (Exception $e) {
            return include(__DIR__ . '/handlers/sql-error-connect.php');
        }
    }



    /**
     * Import data from specified file
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function import($file, $connector = null)
    {
        global $core;

        try {
            $connector = Sql::connectorName($connector);

            if (!file_exists($file)) {
                throw new SqlException(tr('Sql::import(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
            }

            $tel = 0;
            $handle = @fopen($file, 'r');

            if (!$handle) {
                throw new isException('Sql::import(): Could not open file', 'notopen');
            }

            while (($buffer = fgets($handle)) !== false) {
                $buffer = trim($buffer);

                if (!empty($buffer)) {
                    $core->sql[$connector]->query(trim($buffer));

                    $tel++;
                    // :TODO:SVEN:20130717: Right now it updates the display for each record. This may actually slow down import. Make display update only every 10 records or so
                    echo 'Importing SQL data (' . $file . ') : ' . number_format($tel) . "\n";
                    //one line up!
                    echo "\033[1A";
                }
            }

            echo "\nDone\n";

            if (!feof($handle)) {
                throw new isException(tr('Sql::import(): Unexpected EOF'), 'invalid');
            }

            fclose($handle);

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::import(): Failed to import file ":file"', array(':file' => $file)), $e);
        }
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function columns($source, $columns)
    {
        try {
            if (!is_array($source)) {
                throw new SqlException('Sql::columns(): Specified source is not an array');
            }

            $columns = array_force($columns);
            $retval = array();

            foreach ($source as $key => $value) {
                if (in_array($key, $columns)) {
                    $retval[] = '`' . $key . '`';
                }
            }

            if (!count($retval)) {
                throw new SqlException('Sql::columns(): Specified source contains non of the specified columns "' . Strings::log(implode(',', $columns)) . '"');
            }

            return implode(', ', $retval);

        } catch (Exception $e) {
            throw new SqlException('Sql::columns(): Failed', $e);
        }
    }



    // :OBSOLETE: Remove this function soon
    ///*
    // *
    // */
    //public static function set($source, $columns, $filter = 'id') {
    //    try{
    //        if (!is_array($source)) {
    //            throw new SqlException('Sql::set(): Specified source is not an array', 'invalid');
    //        }
    //
    //        $columns = array_force($columns);
    //        $filter  = array_force($filter);
    //        $retval  = array();
    //
    //        foreach ($source as $key => $value) {
    //            /*
    //             * Add all in columns, but not in filter (usually to skip the id column)
    //             */
    //            if (in_array($key, $columns) and !in_array($key, $filter)) {
    //                $retval[] = '`' . $key . '` = :' . $key;
    //            }
    //        }
    //
    //        foreach ($filter as $item) {
    //            if (!isset($source[$item])) {
    //                throw new SqlException('Sql::set(): Specified filter item "'.Strings::log($item) . '" was not found in source', 'not-exists');
    //            }
    //        }
    //
    //        if (!count($retval)) {
    //            throw new SqlException('Sql::set(): Specified source contains non of the specified columns "'.Strings::log(implode(',', $columns)) . '"', 'empty');
    //        }
    //
    //        return implode(', ', $retval);
    //
    //    } catch (Exception $e) {
    //        throw new SqlException('Sql::set(): Failed', $e);
    //    }
    //}



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function values($source, $columns, $prefix = ':')
    {
        try {
            if (!is_array($source)) {
                throw new SqlException('Sql::values(): Specified source is not an array');
            }

            $columns = array_force($columns);
            $retval = array();

            foreach ($source as $key => $value) {
                if (in_array($key, $columns) or ($key == 'id')) {
                    $retval[$prefix . $key] = $value;
                }
            }

            return $retval;

        } catch (Exception $e) {
            throw new SqlException('Sql::values(): Failed', $e);
        }
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function insert_id($connector = null)
    {
        global $core;

        try {
            $connector = Sql::connectorName($connector);
            return $core->sql[Sql::connectorName($connector)]->lastInsertId();

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::insert_id(): Failed for connector ":connector"', array(':connector' => $connector)), $e);
        }
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function get_id_or_name($entry, $seo = true, $code = false)
    {
        try {
            if (is_array($entry)) {
                if (!empty($entry['id'])) {
                    $entry = $entry['id'];

                } elseif (!empty($entry['name'])) {
                    $entry = $entry['name'];

                } elseif (!empty($entry['seoname'])) {
                    $entry = $entry['seoname'];

                } elseif (!empty($entry['code'])) {
                    $entry = $entry['code'];

                } else {
                    throw new SqlException('Sql::get_id_or_name(): Invalid entry array specified', 'invalid');
                }
            }

            if (is_numeric($entry)) {
                $retval['where'] = '`id` = :id';
                $retval['execute'] = array(':id' => $entry);

            } elseif (is_string($entry)) {
                if ($seo) {
                    if ($code) {
                        $retval['where'] = '`name` = :name OR `seoname` = :seoname OR `code` = :code';
                        $retval['execute'] = array(':code' => $entry,
                            ':name' => $entry,
                            ':seoname' => $entry);

                    } else {
                        $retval['where'] = '`name` = :name OR `seoname` = :seoname';
                        $retval['execute'] = array(':name' => $entry,
                            ':seoname' => $entry);
                    }

                } else {
                    if ($code) {
                        $retval['where'] = '`name` = :name OR `code` = :code';
                        $retval['execute'] = array(':code' => $entry,
                            ':name' => $entry);

                    } else {
                        $retval['where'] = '`name` = :name';
                        $retval['execute'] = array(':name' => $entry);
                    }
                }

            } else {
                throw new SqlException('Sql::get_id_or_name(): Invalid entry with type "' . gettype($entry) . '" specified', 'invalid');
            }

            return $retval;

        } catch (SqlException $e) {
            throw new SqlException('Sql::get_id_or_name(): Failed (use either numeric id, name sting, or entry array with id or name)', $e);
        }
    }



    /**
     * Return a unique, non existing ID for the specified table.column
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function unique_id($table, $column = 'id', $max = 10000000, $connector = null)
    {
        try {
            $connector = Sql::connectorName($connector);

            $retries = 0;
            $maxretries = 50;

            while (++$retries < $maxretries) {
                $id = mt_rand(1, $max);

                if (!Sql::get('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :id', array(':id' => $id), null, $connector)) {
                    return $id;
                }
            }

            throw new SqlException('Sql::unique_id(): Could not find a unique id in "' . $maxretries . '" retries', 'not-exists');

        } catch (SqlException $e) {
            throw new SqlException('Sql::unique_id(): Failed', $e);
        }
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function filters($params, $columns, $table = '')
    {
        try {
            $retval = array('filters' => array(),
                'execute' => array());

            $filters = array_keep($params, $columns);

            foreach ($filters as $key => $value) {
                $safe_key = str_replace('`.`', '_', $key);

                if ($value === null) {
                    $retval['filters'][] = ($table ? '`' . $table . '`.' : '') . '`' . $key . '` IS NULL';

                } else {
                    $retval['filters'][] = ($table ? '`' . $table . '`.' : '') . '`' . $key . '` = :' . $safe_key;
                    $retval['execute'][':' . $safe_key] = $value;
                }
            }

            return $retval;

        } catch (SqlException $e) {
            throw new SqlException('Sql::filters(): Failed', $e);
        }
    }



    /**
     * Return a sequential array that can be used in Sql::in
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function in($source, $column = ':value', $filter_null = false, $null_string = false)
    {
        try {
            if (empty($source)) {
                throw new SqlException(tr('Sql::in(): Specified source is empty'), 'not-specified');
            }

            $column = Strings::startsWith($column, ':');
            $source = Arrays::force($source);

            return Arrays::sequentialKeys($source, $column, $filter_null, $null_string);

        } catch (SqlException $e) {
            throw new SqlException('Sql::in(): Failed', $e);
        }
    }



    /**
     * Helper for building Sql::in key value pairs
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $in
     * @param int|string|null $column_starts_with
     * @return string a comma delimeted string of columns
     */
    public static function inColumns(array $in, int|string|null $column_starts_with = null)
    {
        try {
            if ($column_starts_with) {
                /*
                 * Only return those columns that start with this string
                 */
                foreach ($in as $key => $column) {
                    if (substr($key, 0, strlen($column_starts_with)) !== $column_starts_with) {
                        unset($in[$key]);
                    }
                }
            }

            return implode(', ', array_keys($in));

        } catch (Exception $e) {
            throw new SqlException('Sql::in_columns(): Failed', $e);
        }
    }



    /**
     * Try to get single data entry from memcached. If not available, get it from
     * MySQL and store results in memcached for future use
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function getCached($key, $query, $column = false, $execute = false, $expiration_time = 86400, $connector = null)
    {
        try {
            $connector = Sql::connectorName($connector);

            if (($value = Mc::get($key, 'Sql::')) === false) {
                /*
                 * Keyword data not found in cache, get it from MySQL with
                 * specified query and store it in cache for next read
                 */
                if (is_array($column)) {
                    /*
                     * Argument shift, no columns were specified.
                     */
                    $tmp = $execute;
                    $execute = $column;
                    $column = $tmp;
                    unset($tmp);
                }

                if (is_numeric($column)) {
                    /*
                     * Argument shift, no columns were specified.
                     */
                    $tmp = $expiration_time;
                    $expiration_time = $execute;
                    $execute = $tmp;
                    unset($tmp);
                }

                $value = Sql::get($query, $column, $execute, $connector);

                Mc::set($value, $key, 'Sql::', $expiration_time);
            }

            return $value;

        } catch (SqlException $e) {
            throw new SqlException('Sql::getCached(): Failed', $e);
        }
    }



    /**
     * Try to get data list from memcached. If not available, get it from
     * MySQL and store results in memcached for future use
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function listCached($key, $query, $execute = false, $numerical_array = false, $connector = null, $expiration_time = 86400)
    {
        try {
            $connector = Sql::connectorName($connector);

            if (($list = Mc::get($key, 'Sql::')) === false) {
                /*
                 * Keyword data not found in cache, get it from MySQL with
                 * specified query and store it in cache for next read
                 */
                $list = Sql::list($query, $execute, $numerical_array, $connector);

                Mc::set($list, $key, 'Sql::', $expiration_time);
            }

            return $list;

        } catch (SqlException $e) {
            throw new SqlException('Sql::list_cached(): Failed', $e);
        }
    }



    /**
     * Fetch and return data from specified resource
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function fetchColumn($r, $column)
    {
        try {
            $row = Sql::fetch($r);

            if (!isset($row[$column])) {
                throw new SqlException('Sql::fetchColumn(): Specified column "' . Strings::log($column) . '" does not exist', $e);
            }

            return $row[$column];

        } catch (Exception $e) {
            throw new SqlException('Sql::fetchColumn(): Failed', $e);
        }
    }



    /**
     * Merge database entry with new posted entry, overwriting the old DB values,
     * while skipping the values specified in $skip
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $database_entry
     * @param array $post
     * @param mixed $skip
     * @return array The specified datab ase entry, updated with all the data from the specified $_POST entry
     */
    public static function merge($database_entry, $post, $skip = null)
    {
        try {
            if (!$post) {
                /*
                 * No post was done, there is nothing to merge
                 */
                return $database_entry;
            }

            if ($skip === null) {
                $skip = 'id,status';
            }

            if (!is_array($database_entry)) {
                if ($database_entry !== null) {
                    throw new SqlException(tr('Sql::merge(): Specified database source data type should be an array but is a ":type"', array(':type' => gettype($database_entry))), 'invalid');
                }

                /*
                 * Nothing to merge
                 */
                $database_entry = array();
            }

            if (!is_array($post)) {
                if ($post !== null) {
                    throw new SqlException(tr('Sql::merge(): Specified post source data type should be an array but is a ":type"', array(':type' => gettype($post))), 'invalid');
                }

                /*
                 * Nothing to merge
                 */
                $post = array();
            }

            $skip = array_force($skip);

            /*
             * Copy all POST variables over DB
             * Skip POST variables that have NULL value
             */
            foreach ($post as $key => $value) {
                if (in_array($key, $skip)) {
                    continue;
                }

                $database_entry[$key] = $post[$key];
            }

            return $database_entry;

        } catch (Exception $e) {
            throw new SqlException('Sql::merge(): Failed', $e);
        }
    }



    /**
     * Ensure that $connector_name is default in case its not specified
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param string $connector_name
     * @return string The connector that should be used
     */
    public static function connectorName($connector_name)
    {
        global $_CONFIG, $core;

        try {
            if (!$connector_name) {
                $connector_name = $core->register('Sql::connector');

                if ($connector_name) {
                    return $connector_name;
                }

                return $_CONFIG['db']['default'];
            }

            if (!is_scalar($connector_name)) {
                throw new SqlException(tr('Sql::connectorName(): Invalid connector ":connector" specified, it must be scalar', array(':connector' => $connector_name)), 'invalid');
            }

            if (empty($_CONFIG['db'][$connector_name])) {
                throw new SqlException(tr('Sql::connectorName(): Specified database connector ":connector" does not exist', array(':connector' => $connector_name)), 'not-exists');
            }

            return $connector_name;

        } catch (Exception $e) {
            throw new SqlException('Sql::connectorName(): Failed', $e);
        }
    }



    /**
     * Use correct SQL in case NULL is used in queries
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function is($value, $label, $not = false)
    {
        try {
            if ($not) {
                if ($value === null) {
                    return ' IS NOT ' . $label . ' ';
                }

                return ' != ' . $label . ' ';
            }

            if ($value === null) {
                return ' IS ' . $label . ' ';
            }

            return ' = ' . $label . ' ';

        } catch (Exception $e) {
            throw new SqlException('Sql::is(): Failed', $e);
        }
    }



    /**
     * Enable / Disable all query logging on mysql server
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function log($enable)
    {
        try {
            if ($enable) {
                Sql::query('SET global log_output = "FILE";');
                Sql::query('SET global general_log_file="/var/log/mysql/queries.log";');
                Sql::query('SET global general_log = 1;');

            } else {
                Sql::query('SET global log_output = "OFF";');
            }

        } catch (Exception $e) {
            throw new SqlException('Sql::log(): Failed', $e);
        }
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function rowExists($table, $column, $value, $id = null)
    {
        try {
            if ($id) {
                return Sql::get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `id` != :id', true, array($column => $value, ':id' => $id));
            }

            return Sql::get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . '', true, array($column => $value));

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::rowExists(): Failed'), $e);
        }
    }



    /**
     * NOTE: Use only on huge tables (> 1M rows)
     *
     * Return table row count by returning results count for SELECT `id`
     * Results will be cached in a counts table
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function count($table, $where = '', $execute = null, $column = '`id`')
    {
        global $_CONFIG;

        try {
            load_config('Sql::large');

            $expires = $_CONFIG['Sql::large']['cache']['expires'];
            $hash = hash('sha1', $table . $where . $column . json_encode($execute));
            $count = Sql::get('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', array(':hash' => $hash));

            if ($count) {
                return $count;
            }

            /*
             * Count value was not found cached, count it directly
             */
            $count = Sql::get('SELECT COUNT(' . $column . ') AS `count` FROM `' . $table . '` ' . $where, 'count', $execute);

            Sql::query('INSERT INTO `counts` (`createdby`, `count`, `hash`, `until`)
                       VALUES               (:createdby , :count , :hash , NOW() + INTERVAL :expires SECOND)
    
                       ON DUPLICATE KEY UPDATE `count`      = :update_count,
                                               `modifiedon` = NOW(),
                                               `modifiedby` = :update_modifiedby,
                                               `until`      = NOW() + INTERVAL :update_expires SECOND',

                array(':createdby' => isset_get($_SESSION['user']['id']),
                    ':hash' => $hash,
                    ':count' => $count,
                    ':expires' => $expires,
                    ':update_expires' => $expires,
                    ':update_modifiedby' => isset_get($_SESSION['user']['id']),
                    ':update_count' => $count));

            return $count;

        } catch (Exception $e) {
            throw new SqlException('Sql::count(): Failed', $e);
        }
    }



    /**
     * Returns what database currently is selected
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function currentDatabase()
    {
        try {
            return Sql::get('SELECT DATABASE() AS `database` FROM DUAL;');

        } catch (Exception $e) {
            throw new SqlException('Sql::currentDatabase(): Failed', $e);
        }
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function randomId($table, $min = 1, $max = 2147483648, $connector_name = null)
    {
        try {
            $connector_name = Sql::connectorName($connector_name);
            $exists = true;
            $timeout = 50; // Don't do more than 50 tries on this!

            while ($exists and --$timeout > 0) {
                $id = mt_rand($min, $max);
                $exists = Sql::query('SELECT `id` FROM `' . $table . '` WHERE `id` = :id', array(':id' => $id), $connector_name);
            }

            return $id;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::randomId(): Failed for table ":table"', array(':table' => $table)), $e);
        }
    }



    /**
     * Execute a query on a remote SSH server.
     * NOTE: This does NOT support bound variables!
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function exec($server, $query, $root = false, $simple_quotes = false)
    {
        try {
            load_libs('servers');

            $query = addslashes($query);

            if (!is_array($server)) {
                $server = servers_get($server, true);
            }

            /*
             * Are we going to execute as root?
             */
            if ($root) {
                MySql::create_password_file('root', $server['db_root_password'], $server);

            } else {
                MySql::create_password_file($server['db_username'], $server['db_password'], $server);
            }

            if ($simple_quotes) {
                $results = servers_exec($server, 'mysql -e \'' . Strings::ends($query, ';') . '\'');

            } else {
                $results = servers_exec($server, 'mysql -e \"' . Strings::ends($query, ';') . '\"');
            }

            MySql::delete_password_file($server);

            return $results;

        } catch (Exception $e) {
            /*
             * Make sure the password file gets removed!
             */
            try {
                MySql::delete_password_file($server);

            } catch (Exception $e) {

            }

            throw new SqlException(tr('Sql::exec(): Failed'), $e);
        }
    }



    ///*
    // *
    // *
    // * @copyright Copyright (c) 2021 Capmega
    // * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
    // * @category Function reference
    // * @package sql
    // *
    // * @return array
    // */
    //public static function exec_get($server, $query, $root = false, $simple_quotes = false) {
    //    try{
    //
    //    } catch (Exception $e) {
    //        throw new SqlException(tr('Sql::exec_get(): Failed'), $e);
    //    }
    //}



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $params
     * @return
     */
    public static function getDatabase($db_name)
    {
        try {
            $database = self::get('SELECT  `databases`.`id`,
                                           `databases`.`servers_id`,
                                           `databases`.`status`,
                                           `databases`.`replication_status`,
                                           `databases`.`name` AS `database`,
                                           `databases`.`error`,
    
                                           `servers`.`id` AS `servers_id`,
                                           `servers`.`hostname`,
                                           `servers`.`port`,
                                           `servers`.`replication_status` AS `servers_replication_status`,
    
                                           `database_accounts`.`username`      AS `replication_db_user`,
                                           `database_accounts`.`password`      AS `replication_db_password`,
                                           `database_accounts`.`root_password` AS `root_db_password`
    
                                 FROM      `databases`
    
                                 LEFT JOIN `servers`
                                 ON        `servers`.`id`           = `databases`.`servers_id`
    
                                 LEFT JOIN `database_accounts`
                                 ON        `database_accounts`.`id` = `servers`.`database_accounts_id`
    
                                 WHERE     `databases`.`id`         = :name
                                 OR        `databases`.`name`       = :name',

                array(':name' => $db_name));

            if (!$database) {
                throw new SqlException(log_database(tr('Specified database ":database" does not exist', array(':database' => $_GET['database'])), 'not-exists'));
            }

            return $database;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::getDatabase(): Failed'), $e);
        }
    }



    /**
     * Return connector data for the specified connector.
     *
     * Connector data will first be searched for in $_CONFIG[db][CONNECTOR]. If the connector is not found there, the Sql::connectors table will be searched. If the connector is not found there either, NULL will be returned
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param string $connector_name The requested connector name
     * @return array The requested connector data. NULL if the specified connector does not exist
     */
    public static function getConnector($connector_name)
    {
        global $_CONFIG;

        try {
            if (!is_natural($connector_name)) {
                /*
                 * Connector was specified by name
                 */
                if (isset($_CONFIG['db'][$connector_name])) {
                    return $_CONFIG['db'][$connector_name];
                }

                $where = ' `name` = :name ';
                $execute = array(':name' => $connector_name);

            } else {
                /*
                 * Connector was specified by id
                 */
                $where = ' `id` = :id ';
                $execute = array(':id' => $connector_name);
            }

            $connector = Sql::get('SELECT `id`,
                                         `createdon`,
                                         `createdby`,
                                         `meta_id`,
                                         `status`,
                                         `name`,
                                         `seoname`,
                                         `servers_id`,
                                         `hostname`,
                                         `driver`,
                                         `database`,
                                         `user`,
                                         `password`,
                                         `autoincrement`,
                                         `buffered`,
                                         `charset`,
                                         `collate`,
                                         `limit_max`,
                                         `mode`,
                                         `ssh_tunnel_required`,
                                         `ssh_tunnel_source_port`,
                                         `ssh_tunnel_hostname`,
                                         `usleep`,
                                         `pdo_attributes`,
                                         `timezone`
    
                                  FROM   `Sql::connectors`
    
                                  WHERE  ' . $where,

                null, $execute, 'core');

            if ($connector) {
                $connector['ssh_tunnel'] = array('required' => $connector['ssh_tunnel_required'],
                    'source_port' => $connector['ssh_tunnel_source_port'],
                    'hostname' => $connector['ssh_tunnel_hostname']);

                unset($connector['ssh_tunnel_required']);
                unset($connector['ssh_tunnel_source_port']);
                unset($connector['ssh_tunnel_hostname']);

                $_CONFIG['db'][$connector_name] = $connector;
            }

            return $connector;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::getConnector(): Failed'), $e);
        }
    }



    /**
     * Create an SQL connector in $_CONFIG['db'][$connector_name] = $data
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param string $connector_name
     * @param array $connector
     * @return array The specified connector data, with all informatinon completed if missing
     */
    public static function makeConnector($connector_name, $connector)
    {
        global $_CONFIG;

        try {
            if (empty($connector['ssh_tunnel'])) {
                $connector['ssh_tunnel'] = array();
            }

            if (Sql::getConnector($connector_name)) {
                if (empty($connector['overwrite'])) {
                    throw new SqlException(tr('Sql::makeConnector(): The specified connector name ":name" already exists', array(':name' => $connector_name)), 'exists');
                }
            }

            $connector = Sql::ensureConnector($connector);

            if ($connector['ssh_tunnel']) {
                $connector['ssh_tunnel']['required'] = true;
            }

            $_CONFIG['db'][$connector_name] = $connector;
            return $connector;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::makeConnector(): Failed'), $e);
        }
    }



    /**
     * Ensure all SQL connector fields are available
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $connector
     * @return array The specified connector data with all fields available
     */
    public static function ensureConnector($connector)
    {
        try {
            $template = array('driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => null,
                'db' => '',
                'user' => '',
                'pass' => '',
                'autoincrement' => 1,
                'init' => false,
                'buffered' => false,
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_general_ci',
                'limit_max' => 10000,
                'mode' => 'PIPES_AS_CONCAT,IGNORE_SPACE,NO_KEY_OPTIONS,NO_TABLE_OPTIONS,NO_FIELD_OPTIONS',
                'ssh_tunnel' => array('required' => false,
                    'source_port' => null,
                    'hostname' => '',
                    'usleep' => 1200000),
                'pdo_attributes' => array(),
                'version' => '0.0.0',
                'timezone' => 'UTC');

            $connector['ssh_tunnel'] = Sql::merge($template['ssh_tunnel'], isset_get($connector['ssh_tunnel'], array()));
            $connector = Sql::merge($template, $connector);

            if (!is_array($connector['ssh_tunnel'])) {
                throw new SqlException(tr('Sql::ensureConnector(): Specified ssh_tunnel ":tunnel" should be an array but is a ":type"', array(':tunnel' => $connector['ssh_tunnel'], ':type' => gettype($connector['ssh_tunnel']))), 'invalid');
            }

            return $connector;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::ensureConnector(): Failed'), $e);
        }
    }



    /**
     * Test SQL functions over SSH tunnel for the specified server
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @exception SqlException when the test failse
     *
     * @param mixed $server The server that is to be tested
     * @return void
     */
    public static function testTunnel($server)
    {
        global $_CONFIG;

        try {
            load_libs('servers');

            $connector_name = 'test';
            $port = 6000;
            $server = servers_get($server, true);

            if (!$server['database_accounts_id']) {
                throw new SqlException(tr('Sql::testTunnel(): Cannot test SQL over SSH tunnel, server ":server" has no database account linked', array(':server' => $server['domain'])), 'not-exists');
            }

            Sql::makeConnector($connector_name, array('port' => $port,
                'user' => $server['db_username'],
                'pass' => $server['db_password'],
                'ssh_tunnel' => array('source_port' => $port,
                    'domain' => $server['domain'])));

            Sql::get('SELECT TRUE', true, null, $connector_name);

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::testTunnel(): Failed'), $e);
        }
    }



    /**
     * Process SQL query errors
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @exception SqlException when the test failse
     *
     * @param SqlException $e The query exception
     * @param string $query The executed query
     * @param array $execute The bound query variables
     * @param SqlException $sql The PDO SQL object
     * @return void
     */
    public static function error($e, $query, $execute, $sql)
    {
        include(__DIR__ . '/handlers/sql-error.php');
    }



    /**
     *
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param
     * @return
     */
    public static function validLimit($limit, $connector = null)
    {
        global $_CONFIG;

        try {
            $connector = Sql::connectorName($connector);
            $limit = force_natural($limit);

            if ($limit > $_CONFIG['db'][$connector]['limit_max']) {
                return $_CONFIG['db'][$connector]['limit_max'];
            }

            return $limit;

        } catch (Exception $e) {
            throw new SqlException('Sql::validLimit(): Failed', $e);
        }
    }



    /**
     * Return a valid " LIMIT X, Y " string built from the specified parameters
     *
     * @copyright Copyright (c) 2021 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     * @version 2.4.8: Added function and documentation
     *
     * @param int $limit
     * @param int $limit
     * @return string The SQL " LIMIT X, Y " string
     */
    public static function limit($limit = null, $page = null)
    {
        try {
            load_libs('paging');

            if (!$limit) {
                $limit = paging_limit();
            }

            if (!$limit) {
                /*
                 * No limits, so show all
                 */
                return '';
            }

            return ' LIMIT ' . ((paging_page($page) - 1) * $limit) . ', ' . $limit;

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::limit(): Failed'), $e);
        }
    }



    /**
     * Show the specified SQL query in a debug
     *
     * @param string $query
     * @param string|null $execute
     * @param bool $return_only
     * @return mixed
     * @throws CoreException
     */
    public static function show(string $query, ?string $execute = null, bool $return_only = false): mixed
    {
        if (is_array($execute)) {
            /*
             * Reverse key sort to ensure that there are keys that contain at least parts of other keys will not be used incorrectly
             *
             * example:
             *
             * array(category    => test,
             *       category_id => 5)
             *
             * Would cause the query to look like `category` = "test", `category_id` = "test"_id
             */
            krsort($execute);

            if (is_object($query)) {
                /*
                 * Query to be debugged is a PDO statement, extract the query
                 */
                if (!($query instanceof PDOStatement)) {
                    throw new CoreException(tr('debug_sql(): Object of unknown class ":class" specified where PDOStatement was expected', array(':class' => get_class($query))), 'invalid');
                }

                $query = $query->queryString;
            }

            foreach ($execute as $key => $value) {
                if (is_string($value)) {
                    $value = addslashes($value);
                    $query = str_replace($key, '"'.(!is_scalar($value) ? ' ['.tr('NOT SCALAR') . '] ' : '').Strings::log($value) . '"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace($key, ' '.tr('NULL') . ' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace($key, Strings::boolean($value), $query);

                } else {
                    if (!is_scalar($value)) {
                        throw new CoreException(tr('debug_sql(): Specified key ":key" has non-scalar value ":value"', array(':key' => $key, ':value' => $value)), 'invalid');
                    }

                    $query = str_replace($key, $value, $query);
                }
            }
        }

        if ($return_only) {
            return $query;
        }

        if (empty($core->register['clean_debug'])) {
            $query = str_replace("\n", ' ', $query);
            $query = Strings::nodouble($query, ' ', '\s');
        }

        /*
         * VERYVERBOSE already logs the query, don't log it again
         */
        if (!VERYVERBOSE) {
            Log::debug(Strings::endsWith($query, ';'));
        }

        return show(Strings::endsWith($query, ';'), 6);
    }



    /**
     * Access the Simple SQL class
     *
     * @return SqlSimple
     */
    public static function simple(): SqlSimple
    {
        return new SqlSimple(self);
    }



    /**
     * Access the SQL "exists" class
     *
     * @return SqlExists
     */
    public static function exists(): SqlExists
    {
        return new SqlExists(self);
    }



    /**
     * Generate a unique seo name for the seo column
     *
     * This function will use seo_string() to convert the specified $source variable to a seo optimized string, and then it will check the specified $table to ensure that it does not yet exist. If the current seo string already exists, it will be expanded with a natural number and the table will be checked again. If the seo string is still found, this number will be incremented each loop, until the string is no longer found
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2018 Capmega
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package seo
     * @see seo_string()
     * @version 1.27.0: Added documentation
     * @example
     * code
     * $name   = 'Capmega';
     * $result = seo_unique($name, 'customers', 15);
     * showdie($result);
     * /code
     *
     * This would return
     * code
     * capmega
     * /code
     *
     * @param string $source
     * @param string $table
     * @param int|null $ownid
     * @param string $column
     * @param string $replace
     * @param string|null $first_suffix
     * @param $connector_name
     * @return array|mixed|string|null
     * @throws \BException
     */
    // :TODO: Update to use bound variable queries
    public static function getUniqueSeo(string $source, string $table, ?int $ownid = null, string $column = 'seoname', string $replace = '-', ?string $first_suffix = null, $connector_name )
    {
        /*
         * Prepare string
         */
        $id = 0;

        if (empty($source)) {
            /*
             * If the given string is empty, then treat seoname as null, this should not cause indexing issues
             */
            return null;
        }

        if (is_array($source)) {
            /*
             * The specified source is a key => value array which can be used
             * for unique entries spanning multiple columns
             *
             * Example: geo_cities has unique states_id with seoname
             * $source = array('seoname'   => 'cityname',
             *                 'states_id' => 3);
             *
             * NOTE: The first column will have the identifier added
             */
            foreach ($source as $column => &$value) {
                if (empty($first)) {
                    $first = array($column => $value);
                }

                $value = trim(seo_string($value, $replace));
            }

            unset($value);

        } else {
            $source = trim(seo_string($source, $replace));
        }

        /*
         * Filter out the id of the record itself
         */
        if ($ownid) {
            if (is_scalar($ownid)) {
                $ownid = ' AND `id` != ' . $ownid;

            } elseif (is_array($ownid)) {
                $key = key($ownid);

                if (!is_numeric($ownid[$key])) {
                    if (!is_scalar($ownid[$key])) {
                        throw new OutOfBoundsException(tr('seo_unique(): Invalid $ownid array value datatype specified, should be scalar and numeric, but is "%type%"', array('%type%' => gettype($ownid[$key]))), 'invalid');
                    }

                    $ownid[$key] = '"' . $ownid[$key] . '"';
                }

                $ownid = ' AND `' . $key . '` != ' . $ownid[$key];

            } else {
                throw new OutOfBoundsException(tr('seo_unique(): Invalid $ownid datatype specified, should be either scalar, or array, but is "%type%"', array('%type%' => gettype($ownid))), 'invalid');
            }

        } else {
            $ownid = '';
        }

        /*
         * If the seostring exists, add an identifier to it.
         */
        while (true) {
            if (is_array($source)) {
                /*
                 * Check on multiple columns, add identifier on first column value
                 */
                if ($id) {
                    if ($first_suffix) {
                        $source[key($first)] = reset($first) . trim(seo_string($first_suffix, $replace));
                        $first_suffix = null;
                        $id--;

                    } else {
                        $source[key($first)] = reset($first) . $id;
                    }
                }

                $exists = sql_get('SELECT COUNT(*) AS `count` FROM `' . $table . '` WHERE `' . array_implode_with_keys($source, '" AND `', '` = "', true) . '"' . $ownid . ';', true, null, $connector_name);

                if (!$exists) {
                    return $source[key($first)];
                }

            } else {
                if (!$id) {
                    $str = $source;

                } else {
                    if ($first_suffix) {
                        $source = $source . trim(seo_string($first_suffix, $replace));
                        $first_suffix = null;
                        $id--;

                    } else {
                        $str = $source . $id;
                    }
                }

                $exists = sql_get('SELECT COUNT(*) AS `count` FROM `' . $table . '` WHERE `' . $column . '` = "' . $str . '"' . $ownid . ';', true, null, $connector_name);

                if (!$exists) {
                    return $str;
                }
            }

            $id++;
        }
    }
}
