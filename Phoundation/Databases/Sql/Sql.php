<?php

namespace Phoundation\Databases;

use Exception;
use Paging;
use PDO;
use PDOException;
use PDOStatement;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\LogException;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Databases\Exception\MysqlException;
use Phoundation\Databases\Exception\SqlColumnDoesNotExistsException;
use Phoundation\Databases\Exception\SqlException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Server;
use Servers;
use Throwable;



/**
 * Sql class
 *
 * This class is the main SQL database access class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Sql
{
    /**
     * Singleton variable
     *
     * @var Sql|null $instance
     */
    protected static ?Sql $instance = null;

    /**
     * Other databases variable
     *
     * @var array $connectors
     */
    protected static array $connectors = [];

    /**
     * Connector configuration
     *
     * @var array $connector
     */
    protected static array $connector = [];

    /**
     * The identifier name for this connector
     *
     * @var ?string $connector_name
     */
    protected static ?string $connector_name = null;

    /**
     * The actual database interface
     *
     * @var ?PDO $interface
     */
    protected static ?PDO $interface = null;



    /**
     * Sql constructor
     *
     * @param string|null $connector_name
     * @return void
     */
    protected function __constructor(?string $connector_name = null)
    {
        if (!class_exists('PDO')) {
            /*
             * Wulp, PDO class not available, PDO driver is not loaded somehow
             */
            throw new SqlException('Could not find the "PDO" class, does this PHP have PDO available?');
        }

        if (!defined('PDO::MYSql::ATTR_USE_BUFFERED_QUERY')) {
            /*
             * Wulp, MySQL library is not available
             */
            throw new SqlException('Could not find the "MySQL" library for PDO. To install this on Ubuntu derivatives, please type "sudo apt install php-mysql');
        }

        if ($connector_name === null) {
            $connector_name = 'core';
        }

        // Clean connector name, get connector configuration and ensure all required config data is there
        $connector_name = self::connectorName($connector_name);
        self::$connector_name = $connector_name;
        self::$connector = Config::get('databases.connectors.' . self::$connector_name);
        Arrays::ensure(self::$connectors[$connector_name], ['driver', 'host', 'user', 'pass', 'charset']);
        self::$connectors[$connector_name] = &self::$connector;
    }



    /**
     * Singleton, ensure to always return the same Log object.
     *
     * @return Sql
     */
    public static function getInstance(): Sql
    {
        if (!isset(self::$instance)) {
            self::$instance = new Sql('core');
            self::$connectors['core'] = self::$instance;
        }

        return self::$instance;
    }



    /**
     * Access a different SQL object for the specified (different) database
     *
     * @param string $connector_name
     * @return Sql
     */
    public static function database(string $connector_name): Sql
    {
        if (!array_key_exists($connector_name, self::$connectors)) {
            self::$instance = new Sql($connector_name);
            self::$connectors[$connector_name] = self::$instance;
        }

        return self::$instance;
    }



    /**
     * Execute specified query
     *
     * @param
     * @param null $execute
     * @return PDOStatement
     * @package sql
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     */
    public static function query($query, $execute = null): PDOStatement
    {
        global $core;

        try {
            Log::notice(tr('Executing query ":query"', [':query' => $query]));

            $connector_name = self::init();
            $query_start = microtime(true);

            if (!is_string($query)) {
                if (is_object($query)) {
                    if (!($query instanceof PDOStatement)) {
                        throw new SqlException(tr('Object of unknown class ":class" specified where either a string or a PDOStatement was expected', [':class' => get_class($query)]));
                    }

                    // PDO statement was specified instead of a query
                    if ($query[0] == ' ') {
                        Log::sql($query, $execute);
                    }

                    $query->execute($execute);
                    return $query;
                }

                throw new SqlException(tr('Specified query ":query" is not a string or PDOStatement class', [':query' => $query]));
            }

            if (!empty($core->register['Sql::debug_queries'])) {
                $core->register['Sql::debug_queries']--;
                $query = ' ' . $query;
            }

            if ($query[0] == ' ') {
                Log::sql($query, $execute);
            }

            if (!$execute) {
                // Just execute plain SQL query string.
                $pdo_statement = self::$interface->query($query);

            } else {
                // Execute the query with the specified $execute variables
                $pdo_statement = self::$interface->prepare($query);

                try {
                    $pdo_statement->execute($execute);

                } catch (Exception $e) {
                    // Failure is probably that one of the $execute array values is not scalar

                    // Check execute array for possible problems
                    foreach ($execute as $key => &$value) {
                        if (!is_scalar($value) and !is_null($value)) {
                            throw new SqlException(tr('Specified key ":value" in the execute array for query ":query" is NOT scalar or NULL! Value is ":value"', [
                                ':key' => str_replace(':', '.', $key),
                                ':query' => str_replace(':', '.', $query),
                                ':value' => str_replace(':', '.', $value)
                            ]));
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

                $core->executedQuery([
                    'time' => microtime(true) - $query_start,
                    'query' => self::show($query, $execute, true),
                    'function' => $function,
                    'file' => $file,
                    'line' => $line
                ]);
            }

            return $pdo_statement;

        } catch (Exception $e) {
            try {
                // Let Sql::error() try and generate more understandable errors
                Sql::error($e, $query, $execute, isset_get(self::$interface));

                if (!is_string($connector_name)) {
                    throw new SqlException(tr('Sql::query(): Specified connector name ":connector" for query ":query" is invalid, it should be a string', array(':connector' => $connector_name, ':query' => $query)), $e);
                }

                Sql::error($e, $query, $execute, isset_get(self::$interface));

            } catch (Exception $e) {
                throw new SqlException(tr('Sql::query(:connector): Query ":query" failed', array(':connector' => $connector_name, ':query' => $query)), $e);
            }
        }
    }



    /**
     * Builds and returns a query string from the specified query and execute parameters
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param bool $clean
     * @return string
     */
    public static function buildQueryString(string|PDOStatement $query, ?array $execute = null, bool $clean = true): string
    {
        if (is_object($query)) {
            if (!($query instanceof PDOStatement)) {
                throw new LogException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [':class' => get_class($query)]));
            }

            // Query to be logged is a PDO statement, extract the query
            $query = $query->queryString;
        }

        if ($clean) {
            $query = Strings::cleanWhiteSpace($query);
        }

        // Apply execution variables
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

            foreach ($execute as $key => $value) {
                if (is_string($value)) {
                    $value = addslashes($value);
                    $query = str_replace($key, '"' . Strings::Log($value) . '"', $query);

                } elseif (is_null($value)) {
                    $query = str_replace($key, ' ' . tr('NULL') . ' ', $query);

                } elseif (is_bool($value)) {
                    $query = str_replace($key, Strings::boolean($value), $query);

                } else {
                    if (!is_scalar($value)) {
                        throw new LogException(tr('Specified $execute key ":key" has non-scalar value ":value"', [':key' => $key, ':value' => $value]));
                    }

                    $query = str_replace($key, $value, $query);
                }
            }
        }

        return $query;
    }



    /**
     * Prepare specified query
     *
     * @param string $query
     * @return PDOStatement
     */
    public static function prepare(string $query): PDOStatement
    {
        return self::$interface->prepare($query);
    }



    /**
     * Fetch and return data from specified resource
     *
     * @param
     * @param bool $single_column
     * @param int $fetch_style
     * @return mixed|null
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
     * @param
     * @return
     */
    public static function get(string $query, array $execute = null): array
    {
        try {
            $result = Sql::query($query, $execute, $connector_name);

            if ($result->rowCount() > 1) {
                throw new SqlException(tr('Sql::get(): Failed for query ":query" to fetch single row, specified query result contains not 1 but ":count" results', array(':count' => $result->rowCount(), ':query' => Log::sql($result->queryString, $execute, true))), 'multiple');
            }

            return Sql::fetch($result);

        } catch (Exception $e) {
            if (is_object($query)) {
                $query = $query->queryString;
            }

            if ((strtolower(substr(trim($query), 0, 6)) !== 'select') and (strtolower(substr(trim($query), 0, 4)) !== 'show')) {
                throw new SqlException('Sql::get(): Query "' . Strings::log(Log::sql($query, $execute, true), 4096) . '" is not a select or show query and as such cannot return results', $e);
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
    public static function list($query, $execute = null, $numerical_array = false)
    {
        try {
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
     * Connect to database and do a DB version check.
     * If the database was already connected, then just ignore and continue.
     * If the database version check fails, then exception
     *
     * @param
     * @param bool $use_database
     * @return mixed|PDO
     */
    protected static function connect(bool $use_database = true)
    {
        try {
            /*
             * Does this connector require an SSH tunnel?
             */
            if (isset_get(self::$connector['ssh_tunnel']['required'])) {
                self::sshTunnel();
            }

            // Connect!
            self::$connector['pdo_attributes'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            self::$connector['pdo_attributes'][PDO::ATTR_USE_BUFFERED_QUERY] = !(boolean)self::$connector['buffered'];
            self::$connector['pdo_attributes'][PDO::ATTR_INIT_COMMAND] = 'SET NAMES ' . strtoupper(self::$connector['charset']);
            $retries = 7;

            while (--$retries >= 0) {
                try {
                    $connect_string = self::$connector['driver'] . ':host=' . self::$connector['host'] . (empty(self::$connector['port']) ? '' : ';port=' . self::$connector['port']) . ((empty(self::$connector['db']) or !$use_database) ? '' : ';dbname=' . self::$connector['db']);
                    $pdo = new PDO($connect_string, self::$connector['user'], self::$connector['pass'], self::$connector['pdo_attributes']);

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
                            if (isset_get(self::$connector['ssh_tunnel']['required'])) {
                                /*
                                 * The tunneling server has "AllowTcpForwarding"
                                 * set to "no" in the sshd_config, attempt auto
                                 * fix
                                 */
                                os_enable_ssh_tcp_forwarding(self::$connector['ssh_tunnel']['server']);
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
                $pdo->query('SET time_zone = "' . self::$connector['timezone'] . '";');

            } catch (Exception $e) {
                include(__DIR__ . '/handlers/sql-error-timezone.php');
            }

            if (!empty(self::$connector['mode'])) {
                $pdo->query('SET Sql::mode="' . self::$connector['mode'] . '";');
            }

            return $pdo;

        } catch (Exception $e) {
            return include(__DIR__ . '/handlers/sql-error-connect.php');
        }
    }



    /**
     * @return void
     */
    protected static function sshTunnel(): void
    {

    }



    /**
     * Connect with the main database
     *
     * @return mixed|void
     */
    public static function init()
    {
        global $_CONFIG, $core;

        try {
            if (!empty(self::$interface)) {
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
            self::$interface = Sql::connect($connector);

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
                            $r = self::$interface->query('SELECT `project`, `framework`, `offline_until` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                        } catch (Exception $e) {
                            if ($e->getCode() !== '42S02') {
                                if ($e->getMessage() === 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'offline_until\' in \'field list\'') {
                                    $r = self::$interface->query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

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
     * @param
     * @return
     */
    public static function close()
    {
        global $_CONFIG, $core;

        try {
            unset(self::$interface);

        } catch (Exception $e) {
            throw new SqlException(tr('Sql::close(): Failed for connector ":connector"', array(':connector' => $connector)), $e);
        }
    }


    /**
     * Import data from specified file
     *
     * @param string $file
     * @return void
     */
    public static function import(string $file): void
    {
        $tel = 0;
        $handle = File::open($file, 'r');

        while (($buffer = fgets($handle)) !== false) {
            $buffer = trim($buffer);

            if (!empty($buffer)) {
                self::$interface->query($buffer);

                $tel++;
                // :TODO:SVEN:20130717: Right now it updates the display for each record. This may actually slow down import. Make display update only every 10 records or so
                echo 'Importing SQL data (' . $file . ') : ' . number_format($tel) . "\n";
                //one line up!
                echo "\033[1A";
            }
        }

        echo "\nDone\n";

        if (!feof($handle)) {
            throw new SqlException(tr('Import of file ":file" unexpectedly halted', [':file' => $file]));
        }

        fclose($handle);
    }



    /**
     *
     *
     * @param array $source
     * @param array|string $columns
     * @return string
     */
    public static function columns(array $source, array|string $columns): string
    {
        $columns = Arrays::force($columns);
        $retval = array();

        foreach ($source as $key => $value) {
            if (in_array($key, $columns)) {
                $retval[] = '`' . $key . '`';
            }
        }

        if (!count($retval)) {
            throw new SqlException(tr('Specified source contains non of the specified columns ":columns"', [':columns' => $columns]));
        }

        return implode(', ', $retval);
    }



    /**
     *
     *
     * @param array|string $source
     * @param array|Strings $columns
     * @param string $prefix
     * @return array
     */
    public static function values(array|string $source, array|strings $columns, string $prefix = ':'): array
    {
        $columns = Arrays::force($columns);
        $retval = [];

        foreach ($source as $key => $value) {
            if (in_array($key, $columns) or ($key == 'id')) {
                $retval[$prefix . $key] = $value;
            }
        }

        return $retval;
    }



    /**
     *
     *
     * @return ?int
     */
    public static function insertId(): ?int
    {
        $insert_id = self::$interface->lastInsertId();

        if ($insert_id) {
            return (int) $insert_id;
        }

        return null;
    }


    /**
     *
     *
     * @param mixed $entry
     * @param bool $seo
     * @param bool $code
     * @return string
     */
    public static function getIdOrName(mixed $entry, bool $seo = true, bool $code = false): array
    {
        // TODO Figure out WTF this function is and what it is supposed to do
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
                throw new SqlException(tr('Invalid entry array specified'));
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
            throw new SqlException(tr('Invalid entry with type ":type" specified', [':type' => gettype($entry)]));
        }

        return $retval;
    }


    /**
     * Return a unique, non-existing ID for the specified table.column
     *
     * @param $table
     * @param string $column
     * @param int $max
     * @return int
     */
    public static function uniqueId(string $table, string $column = 'id', int $max = 10000000): int
    {
        $retries = 0;
        $maxretries = 50;

        while (++$retries < $maxretries) {
            $id = mt_rand(1, $max);

            // TODO Find a better algorithm than "Just try random shit until something sticks"
            if (!Sql::get('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :id', [':id' => $id])) {
                return $id;
            }
        }

        throw new SqlException(tr('Could not find a unique id in ":retries" retries', [':retries' => $maxretries]));
    }



    /**
     *
     *
     * @param $params
     * @param $columns
     * @param string $table
     * @return array
     */
    public static function filters($params, $columns, $table = ''): array
    {
        $retval = [
            'filters' => [],
            'execute' => []
        ];

        $filters = Arrays::keep($params, $columns);

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
    }



    /**
     * Return a sequential array that can be used in Sql::in
     *
     * @param array|string $source
     * @param string $column
     * @param bool $filter_null
     * @param bool $null_string
     * @return array
     */
    public static function in(array|string $source, string $column = ':value', bool $filter_null = false, bool $null_string = false): array
    {
        if (empty($source)) {
            throw new OutOfBoundsException(tr('Specified source is empty'));
        }

        $column = Strings::startsWith($column, ':');
        $source = Arrays::force($source);

        return Arrays::sequentialKeys($source, $column, $filter_null, $null_string);
    }



    /**
     * Helper for building Sql::in key value pairs
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $in
     * @param int|string|null $column_starts_with
     * @return string a comma delimited string of columns
     */
    public static function inColumns(array $in, int|string|null $column_starts_with = null): string
    {
        if ($column_starts_with) {
            // Only return those columns that start with this string
            foreach ($in as $key => $column) {
                if (!Strings::startsWith($key, $column_starts_with)) {
                    unset($in[$key]);
                }
            }
        }

        return implode(', ', array_keys($in));
    }



    /**
     * Try to get single data entry from memcached. If not available, get it from
     * MySQL and store results in memcached for future use
     *
     * @param $key
     * @param $query
     * @param bool $column
     * @param bool $execute
     * @param int $expiration_time
     * @return array|false|null
     */
    public static function getCached($key, $query, $column = false, $execute = false, $expiration_time = 86400)
    {
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
    }



    /**
     * Try to get data list from memcached. If not available, get it from
     * MySQL and store results in memcached for future use
     *
     * @param $key
     * @param $query
     * @param bool $execute
     * @param bool $numerical_array
     * @param null $connector
     * @param int $expiration_time
     * @return array|false
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
     * @param PDOStatement $r
     * @param string $column
     * @return string
     */
    public static function fetchColumn(PDOStatement $r, string $column): string
    {
        $row = Sql::fetch($r);

        if (!array_key_exists($column, $row)) {
            throw new SqlException(tr('Sql::fetchColumn(): Specified column ":column" does not exist', [':column' => $column]));
        }

        return $row[$column];
    }



    /**
     * Merge database entry with new posted entry, overwriting the old DB values,
     * while skipping the values specified in $skip
     *
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package sql
     *
     * @param array $database_entry
     * @param array $post
     * @param mixed $skip
     * @return array The specified datab ase entry, updated with all the data from the specified $_POST entry
     */
    public static function merge(array $database_entry, array $post, $skip = null): array
    {
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

        $skip = Arrays::force($skip);

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
    }


    /**
     * Use correct SQL in case NULL is used in queries
     *
     * @param $value
     * @param $label
     * @param bool $not
     * @return string
     */
    public static function is($value, $label, $not = false): string
    {
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
    }



    /**
     * Enable / Disable all query logging on mysql server
     *
     * @param bool $enable
     * @return void
     */
    public static function log(bool $enable)
    {
        if ($enable) {
            Sql::query('SET global log_output = "FILE";');
            Sql::query('SET global general_log_file="/var/log/mysql/queries.log";');
            Sql::query('SET global general_log = 1;');

        } else {
            Sql::query('SET global log_output = "OFF";');
        }
    }


    /**
     *
     *
     * @param string $table
     * @param string $column
     * @param int|string|null $value
     * @param int|null $id
     * @return bool
     */
    public static function rowExists(string $table, string $column, int|string|null $value, ?int $id = null): bool
    {
        if ($id) {
            return Sql::get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `id` != :id', true, [$column => $value, ':id' => $id]);
        }

        return Sql::get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . '', true, [$column => $value]));
    }



    /**
     * NOTE: Use only on huge tables (> 1M rows)
     *
     * Return table row count by returning results count for SELECT `id`
     * Results will be cached in a counts table
     *
     * @param
     * @return
     */
    public static function count($table, $where = '', $execute = null, $column = '`id`')
    {
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
    }



    /**
     * Returns what database currently is selected
     *
     * @param
     * @return
     */
    public static function currentDatabase(): string
    {
        return Sql::getColumn('SELECT DATABASE() AS `database` FROM DUAL;');
    }


    /**
     *
     *
     * @param string $table
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function randomId(string $table, int $min = 1, int $max = 2147483648): int
    {
        $exists = true;
        $id = -1; // Initialize id negatively to ensure
        $timeout = 50; // Don't do more than 50 tries on this!

        while ($exists and --$timeout > 0) {
            $id = mt_rand($min, $max);
            $exists = Sql::query('SELECT `id` FROM `' . $table . '` WHERE `id` = :id', [':id' => $id]);
        }

        return $id;
    }



    /**
     * Execute a query on a remote SSH server in a bash command
     *
     * @note: This does NOT support bound variables!
     * @todo: This method uses a password file which might be left behind if (for example) the connection would drop
     *        half way
     * @param string|Server $server
     * @param string $query
     * @param bool $root
     * @param bool $simple_quotes
     * @return array
     */
    public static function exec(string|Server $server, string $query, bool $root = false, bool $simple_quotes = false): array
    {
        try {
            $query = addslashes($query);

            if (!is_array($server)) {
                $server = Servers::get($server, true);
            }

            // Are we going to execute as root?
            if ($root) {
                MySql::createPasswordFile('root', $server['db_root_password'], $server);

            } else {
                MySql::createPasswordFile($server['db_username'], $server['db_password'], $server);
            }

            if ($simple_quotes) {
                $results = Servers::exec($server, 'mysql -e \'' . Strings::ends($query, ';') . '\'');

            } else {
                $results = Servers::exec($server, 'mysql -e \"' . Strings::ends($query, ';') . '\"');
            }

            MySql::deletePasswordFile($server);

            return $results;
        } catch (MysqlException $e) {
            // Ensure that the password file will be removed
            MySql::deletePasswordFile($server);
        }
    }



    ///*
    // *
    // *
    // * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
    // * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
    // * @category Function reference
    // * @package sql
    // *
    // * @return array
    // */
    //public static function exec_get($server, $query, $root = false, $simple_quotes = false) {
    //    try {
    //
    //    } catch (Exception $e) {
    //        throw new SqlException(tr('Sql::exec_get(): Failed'), $e);
    //    }
    //}



    /**
     *
     *
     * @param string $db_name
     * @return array
     */
    public static function getDatabase(string $db_name): array
    {
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

                             [':name' => $db_name]);

        if (!$database) {
            throw new SqlException(tr('Specified database ":database" does not exist', [':database' => $_GET['database']]));
        }

        return $database;
    }



    /**
     * Return connector data for the specified connector.
     *
     * Connector data will first be searched for in $_CONFIG[db][CONNECTOR]. If the connector is not found there, the Sql::connectors table will be searched. If the connector is not found there either, NULL will be returned
     *
     * @param string $connector_name The requested connector name
     * @return array The requested connector data. NULL if the specified connector does not exist
     */
    public static function getConnector(string $connector_name): array
    {
        if (!is_natural($connector_name)) {
            // Connector was specified by name
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
    }



    /**
     * Create an SQL connector in $_CONFIG['db'][$connector_name] = $data
     *
     * @param string $connector_name
     * @param array $connector
     * @return array The specified connector data, with all informatinon completed if missing
     */
    public static function makeConnector(string $connector_name, array $connector): array
    {
        if (empty($connector['ssh_tunnel'])) {
            $connector['ssh_tunnel'] = array();
        }

        if (Sql::getConnector($connector_name)) {
            if (empty($connector['overwrite'])) {
                throw new SqlException(tr('The specified connector name ":name" already exists', [':name' => $connector_name]));
            }
        }

        $connector = Sql::ensureConnector($connector);

        if ($connector['ssh_tunnel']) {
            $connector['ssh_tunnel']['required'] = true;
        }

        $_CONFIG['db'][$connector_name] = $connector;
        return $connector;
    }



    /**
     * Ensure all SQL connector fields are available
     *
     * @param array $connector
     * @return array The specified connector data with all fields available
     */
    public static function ensureConnector(array $connector): array
    {
        $template = [
            'driver' => 'mysql',
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
            'timezone' => 'UTC'];

        $connector['ssh_tunnel'] = Sql::merge($template['ssh_tunnel'], isset_get($connector['ssh_tunnel'], array()));
        $connector = Sql::merge($template, $connector);

        if (!is_array($connector['ssh_tunnel'])) {
            throw new SqlException(tr('Specified ssh_tunnel ":tunnel" should be an array but is a ":type"', [':tunnel' => $connector['ssh_tunnel'], ':type' => gettype($connector['ssh_tunnel'])]));
        }

        return $connector;
    }



    /**
     * Test SQL functions over SSH tunnel for the specified server
     *
     * @param string|Server $server The server that is to be tested
     * @return void
     */
    public static function testTunnel(string|Server $server): void
    {
        $connector_name = 'test';
        $port = 6000;
        $server = servers_get($server, true);

        if (!$server['database_accounts_id']) {
            throw new SqlException(tr('Cannot test SQL over SSH tunnel, server ":server" has no database account linked', [':server' => $server['domain']]));
        }

        Sql::makeConnector($connector_name, [
            'port' => $port,
            'user' => $server['db_username'],
            'pass' => $server['db_password'],
            'ssh_tunnel' => [
                'source_port' => $port,
                'domain' => $server['domain']
            ]
        ]);

        Sql::get('SELECT TRUE', true, null, $connector_name);
    }

    

    /**
     * Process SQL query errors
     *
     * @param SqlException $e The query exception
     * @param string|PDOStatement $query The executed query
     * @param array|null $execute The bound query variables
     * @return void
     */
    public static function error(Throwable $e, string|PDOStatement $query, ?array $execute = null)
    {
        global $_CONFIG, $core;

        if (!$e instanceof PDOException) {
            switch ($e->getCode()) {
                case 'forcedenied':
                    uncaught_exception($e, true);

                default:
                    /*
                     * This is likely not a PDO error, so it cannot be handled here
                     */
                    throw new SqlException('Not a PDO exception', $e);
            }
        }

        if ($query) {
            if ($execute) {
                if (!is_array($execute)) {
                    throw new SqlException(tr('The specified $execute parameter is NOT an array, it is an ":type"', [':type' => gettype($execute)]), $e);
                }

                foreach ($execute as $key => $value) {
                    if (!is_scalar($value) and !is_null($value)) {
                        /*
                         * This is automatically a problem!
                         */
                        throw new SqlException(tr('POSSIBLE ERROR: The specified $execute array contains key ":key" with non scalar value ":value"', [':key' => $key, ':value' => $value]), $e);
                    }
                }
            }
        }

        /*
         * Get error data
         */
        $error = self::errorInfo();

        if (($error[0] == '00000') and !$error[1]) {
            $error = $e->errorInfo;
        }

        switch ($e->getCode()) {
            case 'denied':
                // FALLTHROUGH
            case 'invalidforce':

                /*
                 * Some database operation has failed
                 */
                foreach ($e->getMessages() as $message) {
                    Log::error($message);
                }

                die(1);

            case 'HY093':
                /*
                 * Invalid parameter number: number of bound variables does not match number of tokens
                 *
                 * Get tokens from query
                 */
                preg_match_all('/:\w+/imus', $query, $matches);

                if (count($matches[0]) != count($execute)) {
                    throw new SqlException(tr('Query ":query" failed with error HY093, the number of query tokens does not match the number of bound variables. The query contains tokens ":tokens", where the bound variables are ":variables"', [':query' => $query, ':tokens' => implode(',', $matches['0']), ':variables' => implode(',', array_keys($execute))]), $e);
                }

                throw new SqlException(tr('Query ":query" failed with error HY093, One or more query tokens does not match the bound variables keys. The query contains tokens ":tokens", where the bound variables are ":variables"', [':query' => $query, ':tokens' => implode(',', $matches['0']), ':variables' => implode(',', array_keys($execute))]), $e);

            case '23000':
                /*
                 * 23000 is used for many types of errors!
                 */

// :TODO: Remove next 5 lines, 23000 cannot be treated as a generic error because too many different errors cause this one
//showdie($error)
//                /*
//                 * Integrity constraint violation: Duplicate entry
//                 */
//                throw new SqlException('sql_error(): Query "'.Strings::Log($query, 4096).'" tries to insert or update a column row with a unique index to a value that already exists', $e);

            default:
                switch (isset_get($error[1])) {
                    case 1044:
                        /*
                         * Access to database denied
                         */
                        if (!is_array($query)) {
                            if (empty($query['db'])) {
                                throw new SqlException(tr('Query ":query" failed, access to database denied', [':query' => $query]), $e);
                            }

                            throw new SqlException(tr('Cannot use database ":db", this user has no access to it', [':db' => $query['db']]), $e);
                        }

                        throw new SqlException(tr('Cannot use database with query ":query", this user has no access to it', [':query' => Sql::buildQueryString($query, $execute, true)]), $e);

                    case 1049:
                        /*
                         * Specified database does not exist
                         */
                        static $retry;

                        if (($core->register['script'] == 'init')) {
                            if ($retry) {
                                $e = new SqlException(tr('Cannot use database ":db", it does not exist and cannot be created automatically with the current user ":user"', [':db' => isset_get($query['db']), ':user' => isset_get($query['user'])]), $e);
                                $e->addMessages(tr('Possible reason can be that the configured user does not have the required GRANT to create database'));
                                $e->addMessages(tr('Possible reason can be that MySQL cannot create the database because the filesystem permissions of the mysql data files has been borked up (on linux, usually this is /var/lib/mysql, and this should have the user:group mysql:mysql)'));

                                throw $e;
                            }

                            /*
                             * We're doing an init, try to automatically create the database
                             */
                            $retry = true;
                            Log::warning('Database "'.$query['db'].'" does not exist, attempting to create it automatically');

                            self::query('CREATE DATABASE `:db` DEFAULT CHARSET=":charset" COLLATE=":collate";', [':db' => $query['db'], ':charset' => self::$connector['charset'], ':collate' => self::$connector['collate']]);
                            return self::connect($query);
                        }

                        throw new SqlException(tr('Cannot use database ":db", it does not exist', [':db' => $query['db']]), $e);

                    case 1052:
                        /*
                         * Integrity constraint violation
                         */
                        throw new SqlException(tr('Query ":query" contains an abiguous column', [':query' => Sql::buildQueryString($query, $execute, true)]), $e);

                    case 1054:
                        /*
                         * Column not found
                         */
                        throw new SqlException(tr('Query ":query" refers to a column that does not exist', [':query' => Sql::buildQueryString($query, $execute, true)]), $e);

                    case 1064:
                        /*
                         * Syntax error or access violation
                         */
                        if (str_contains(strtoupper($query), 'DELIMITER')) {
                            throw new SqlException(tr('Query ":query" contains the "DELIMITER" keyword. This keyword ONLY works in the MySQL console, and can NOT be used over MySQL drivers in PHP. Please remove this keword from the query', array(':query' => Sql::buildQueryString($query, $execute, true))), $e);
                        }

                        throw new SqlException(tr('Query ":query" has a syntax error', [':query' => Sql::buildQueryString($query, $execute, true)]), $e);

                    case 1072:
                        /*
                         * Adding index error, index probably does not exist
                         */
                        throw new SqlException(tr('Query ":query" failed with error 1072 with the message ":message"', [':query' => Sql::buildQueryString($query, $execute, true), ':message' => isset_get($error[2])]), $e);

                    case 1005:
                        //FALLTHROUGH
                    case 1217:
                        //FALLTHROUGH
                    case 1452:
                        /*
                         * Foreign key error, get the FK error data from mysql
                         */
                        try {
                            $fk = Sql::getColumn('SHOW ENGINE INNODB STATUS', 'Status', null));
                            $fk = Strings::from($fk, 'LATEST FOREIGN KEY ERROR');
                            $fk = Strings::from($fk, '------------------------');
                            $fk = Strings::until($fk, '------------');
                            $fk = str_replace("\n", ' ', $fk);

                        }catch(Exception $e) {
                            throw new SqlException(tr('Query ":query" failed with error 1005, but another error was encountered while trying to obtain FK error data', [':query' => Sql::buildQueryString($query, $execute, true)]), $e);
                        }

                        throw new SqlException(tr('Query ":query" failed with error 1005 with the message ":message"', [':query' => Sql::buildQueryString($query, $execute, true), ':message' => $fk]), $e);

                    case 1146:
                        /*
                         * Base table or view not found
                         */
                        throw new SqlException(tr('Query ":query" refers to a base table or view that does not exist', [':query' => Sql::buildQueryString($query, $execute, true)]), $e);

                    default:
                        if (!is_string($query)) {
                            if (!is_object($query) or !($query instanceof PDOStatement)) {
                                throw new SqlException('Specified query is neither a SQL string or a PDOStatement it seems to be a ":type"', [':type' => gettype($query)], 'invalid');
                            }

                            $query = $query->queryString;
                        }

                        throw new SqlException(tr('Query ":query" failed', array(':query' => Sql::buildQueryString(preg_replace('!\s+!', ' ', $query), $execute, true))), $e);

                        $body = "SQL STATE ERROR : \"".$error[0]."\"\n".
                            "DRIVER ERROR    : \"".$error[1]."\"\n".
                            "ERROR MESSAGE   : \"".$error[2]."\"\n".
                            "query           : \"".(PLATFORM_HTTP ? "<b>".Strings::Log(Sql::buildQueryString($query, $execute, true), 4096)."</b>" : Strings::Log(Sql::buildQueryString($query, $execute, true), 4096))."\"\n".
                            "date            : \"".date('d m y h:i:s')."\"\n";

                        if (isset($_SESSION)) {
                            $body .= "Session : ".print_r(isset_get($_SESSION), true)."\n";
                        }

                        $body .= "POST   : ".print_r($_POST  , true)."
                          GET    : ".print_r($_GET   , true)."
                          SERVER : ".print_r($_SERVER, true)."\n";

                        error_log('PHP SQL_ERROR: '.Strings::Log($error[2]).' on '.Strings::Log(Sql::buildQueryString($query, $execute, true), 4096));

                        if (!$_CONFIG['production']) {
                            throw new SqlException(nl2br($body), $e);
                        }

                        throw new SqlException(tr('An error has been detected, our staff has been notified about this problem.'), $e);
                }
        }
    }



    /**
     *
     *
     * @param int $limit
     * @return int
     */
    public static function validLimit(int $limit): int
    {
        $limit = force_natural($limit);

        if ($limit > $_CONFIG['db'][self::$connector]['limit_max']) {
            return $_CONFIG['db'][self::$connector]['limit_max'];
        }

        return $limit;
    }


    /**
     * Return a valid " LIMIT X, Y " string built from the specified parameters
     *
     * @param int|null $limit
     * @param int|null $page
     * @return string The SQL " LIMIT X, Y " string
     */
    protected static function limit(?int $limit = null, ?int $page = null): string
    {
        $limit = Paging::limit($limit);

        if (!$limit) {
            /*
             * No limits, so show all
             */
            return '';
        }

        return ' LIMIT ' . ((Paging::page($page) - 1) * $limit) . ', ' . $limit;
    }



    /**
     * Show the specified SQL query in a debug
     *
     * @param string|PDOStatement $query
     * @param ?array $execute
     * @param bool $return_only
     * @return mixed
     * @throws SqlException
     */
    public static function show(string|PDOStatement $query, ?array $execute = null, bool $return_only = false): mixed
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
                    throw new SqlException(tr('Log::sql(): Object of unknown class ":class" specified where PDOStatement was expected', array(':class' => get_class($query))), 'invalid');
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
                        throw new SqlException(tr('Log::sql(): Specified key ":key" has non-scalar value ":value"', array(':key' => $key, ':value' => $value)), 'invalid');
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

        return Debug::show(Strings::endsWith($query, ';'), 6);
    }
}
