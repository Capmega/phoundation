<?php

namespace Phoundation\Databases;

use Exception;
use Paging;
use PDO;
use PDOException;
use PDOStatement;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\LogException;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Core\Timers;
use Phoundation\Databases\Exception\MysqlException;
use Phoundation\Databases\Sql\Exception\SqlColumnDoesNotExistsException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Filesystem\File;
use Phoundation\Processes\Commands;
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
     * Identifier of this instance
     *
     * @var string|null $instance_name
     */
    protected ?string $instance_name = null;

    /**
     * Database instances store
     *
     * @var array $instance
     */
    protected static array $instances = [];

    /**
     * Connector configuration
     *
     * @var array $configuration
     */
    protected array $configuration = [];

    /**
     * The actual database interface
     *
     * @var PDO|null $interface
     */
    protected ?PDO $interface = null;

    /**
     * True if a database is in use
     *
     * @var bool $using_database
     */
    protected bool $using_database = false;

    /**
     * The database interface
     *
     * @var PDO|null $pdo
     */
    protected ?PDO $pdo = null;



    /**
     * Sql constructor
     *
     * @param string|null $instance_name
     * @return void
     */
    protected function __construct(?string $instance_name = null)
    {
        if (!class_exists('PDO')) {
            /*
             * Wulp, PDO class not available, PDO driver is not loaded somehow
             */
            throw new SqlException('Could not find the "PDO" class, does this PHP have PDO available?');
        }

        if (!defined('PDO::MY$this->ATTR_USE_BUFFERED_QUERY')) {
            /*
             * Wulp, MySQL library is not available
             */
            throw new SqlException('Could not find the "MySQL" library for PDO. To install this on Ubuntu derivatives, please type "sudo apt install php-mysql');
        }

        if ($instance_name === null) {
            $instance_name = 'core';
        }

        // Clean connector name, get connector configuration and ensure all required config data is there
        $this->instance_name = $instance_name;
        $this->configuration = Config::get('databases.instances.' . $instance_name);
        Arrays::ensure($this->configuration, ['driver', 'host', 'user', 'pass', 'charset']);
    }



    /**
     * Quick access to Mc instances. Defaults to "system" instance
     *
     * @param string|null $instance_name
     * @return Sql
     */
    public static function database(?string $instance_name = null): Sql
    {
        if (!$instance_name) {
            // Always default to system instance
            $instance_name = 'system';
        }

        if (!self::$instances[$instance_name]) {
            self::$instances[$instance_name] = new Sql($instance_name);
        }

        return self::$instances[$instance_name];
    }



    /**
     * Executes specified query and returns a PDOStatement object
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return PDOStatement
     */
    public function query(string|PDOStatement $query, ?array $execute = null): PDOStatement
    {
        try {
            Log::notice(tr('Executing query ":query"', [':query' => $query]));

            // PDO statement can be specified instead of a query
            if (!is_string($query)) {
                if (Config::get('databases.sql.debug', false) or ($query->queryString[0] == ' ')) {
                    // Log query
                    Log::sql($query, $execute);
                }

                Timers::get('query')->startLap($query->queryString);
                $query->execute($execute);
                Timers::get('query')->stopLap($query->queryString);
                return $query;
            }

            // Log all queries?
            if (Debug::enabled() and Config::get('debug.queries')) {
                $query = ' ' . $query;
            }

            if ($query[0] == ' ') {
                Log::sql($query, $execute);
            }

            Timers::get('query')->startLap($query);

            if (!$execute) {
                // Just execute plain SQL query string.
                $pdo_statement = $this->interface->query($query);

            } else {
                // Execute the query with the specified $execute variables
                $pdo_statement = $this->interface->prepare($query);

                try {
                    $pdo_statement->execute($execute);

                } catch (Exception $e) {
                    // Failure is probably that one of the $execute array values is not scalar

                    // Check execute array for possible problems
                    foreach ($execute as $key => $value) {
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
                 // Get current function / file@line. If current function is actually an include then assume this is the
                 // actual script that was executed by route()
                Debug::addStatistic()
                    ->setQuery($this->show($query, $execute, true))
                    ->setTime(Timers::get('queries')->stopLap($query));
            }

            return $pdo_statement;

        } catch (Throwable $e) {
            // Let $this->error() try and generate more understandable errors
            $this->errorQuery($e, $query, $execute);
            throw new SqlException(tr('Query failed'), previous: $e);
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
    public function buildQueryString(string|PDOStatement $query, ?array $execute = null, bool $clean = true): string
    {
        if (is_object($query)) {
            if (!($query instanceof PDOStatement)) {
                throw new SqlException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [':class' => get_class($query)]));
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
    public function prepare(string $query): PDOStatement
    {
        return $this->interface->prepare($query);
    }



    /**
     * Fetch and return one complete row from specified resource
     *
     * @param PDOStatement $resource
     * @param int $fetch_style
     * @return array|string
     */
    public function fetch(PDOStatement $resource, int $fetch_style = PDO::FETCH_ASSOC): ?array
    {
        $result = $resource->fetch($fetch_style);

        if ($result === false) {
            // There are no entries
            return null;
        }

        // Return all columns
        return $result;
    }



    /**
     * Fetch and return one column from specified resource
     *
     * @param PDOStatement $resource
     * @param string $column
     * @param int $fetch_style
     * @return string|null
     */
    public function fetchColumn(PDOStatement $resource, string $column, int $fetch_style = PDO::FETCH_ASSOC): ?string
    {
        $result = $this->fetch($resource, $fetch_style);

        if ($result) {
            if (!array_key_exists($column, $result)) {
                throw new SqlException(tr('Failed to fetch single column ":column" from query ":query", specified query result does not contain the requested column', [':column' => $column, ':query' => $resource->queryString]));
            }

            return $result[$column];
        }

        // There are no entries
        return null;
    }


    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array|null
     */
    public function get(string|PDOStatement $query, array $execute = null): ?array
    {
        $result = $this->query($query, $execute);

        switch ($result->rowCount()) {
            case 0:
                // No results. This is probably okay, but do check if the query was a select or show query, jsut to
                // be sure
                $this->ensureShowSelect($query);
                return null;

            case 1:
                return $this->fetch($result);

            default:
                // Multiple results, this is always bad for a function that should only return one result!
                $this->ensureShowSelect($query);
                throw new SqlMultipleResultsException(tr('Failed for query ":query" to fetch single row, specified query result contains not 1 but ":count" results', [':count' => $result->rowCount(), ':query' => $this->buildQueryString($result->queryString, $execute)]));
        }
    }



    /**
     * Get the value of a single column from a single row for the specified query
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param string|null $column
     * @return string|null
     */
    public function getColumn(string|PDOStatement $query, array $execute = null, ?string $column = null): ?string
    {
        $result = $this->get($query, $execute);

        if (!$result) {
            // No results
            return null;
        }

        if ($column) {
            // Column was specified, so we can process multiple columns in the results
            if (array_key_exists($column, $result)) {
                return $result[$column];
            }

            // Specified column doesn't exist
            throw new SqlColumnDoesNotExistsException('Cannot return column ":column", it does not exist in the result set for query ":query"', [':query' => $query, ':column' => $column]);
        } else {
            // No column was specified, so we MUST have received only one column!
            if (count($result) > 1) {
                // The query returned multiple columns
                throw new SqlException('The query ":query" returned ":count" columns while $this->getColumn() can only return one single column', [':query' => $query, ':count' => count($result)]);
            }

            return Arrays::firstValue($result);
        }
    }


    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param bool $numerical_array
     * @return array
     */
    public function list(string|PDOStatement $query, ?array $execute = null, bool $numerical_array = false): array
    {
        if (is_object($query)) {
            $r = $query;
            $query = $r->queryString;

        } else {
            $r = $this->query($query, $execute);
        }

        $retval = [];

        while ($row = $this->fetch($r)) {
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
    }


    /**
     * Connect to database and do a DB version check.
     * If the database was already connected, then just ignore and continue.
     * If the database version check fails, then exception
     *
     * @param bool $use_database
     * @return PDO
     * @throws SqlException
     */
    protected function connect(bool $use_database = true): PDO
    {
        try {
            /*
             * Does this connector require an SSH tunnel?
             */
            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                $this->sshTunnel();
            }

            // Connect!
            $this->configuration['pdo_attributes'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            $this->configuration['pdo_attributes'][PDO::ATTR_USE_BUFFERED_QUERY] = !(boolean)$this->configuration['buffered'];
            $this->configuration['pdo_attributes'][PDO::ATTR_INIT_COMMAND] = 'SET NAMES ' . strtoupper($this->configuration['charset']);
            $retries = 7;

            while (--$retries >= 0) {
                try {
                    $connect_string = $this->configuration['driver'] . ':host=' . $this->configuration['host'] . (empty($this->configuration['port']) ? '' : ';port=' . $this->configuration['port']) . ((empty($this->configuration['db']) or !$use_database) ? '' : ';dbname=' . $this->configuration['db']);
                    $pdo = new PDO($connect_string, $this->configuration['user'], $this->configuration['pass'], $this->configuration['pdo_attributes']);

                    Log::success(tr('Connected with PDO connect string ":string"', [':string' => $connect_string]), 3);
                    break;

                } catch (Exception $e) {
                    Log::error(tr('Failed to connect with PDO connect string ":string"', [':string' => $connect_string]));
                    Log::error($e);

                    $message = $e->getMessage();

                    if (!str_contains($message, 'errno=32')) {
                        if ($e->getMessage() == 'ERROR 2013 (HY000): Lost connection to MySQL server at \'reading initial communication packet\', system error: 0') {
                            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                                /*
                                 * The tunneling server has "AllowTcpForwarding"
                                 * set to "no" in the sshd_config, attempt auto
                                 * fix
                                 */
                                Commands::server($this->configuration['server'])->enableTcpForwarding($this->configuration['ssh_tunnel']['server']);
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
                     * This is a workaround for the weird PHP MySQL error "PDO::__construct(): send of 5 bytes failed
                     * with errno=32 Broken pipe". So far we have not been able to find a fix for this but we have noted
                     * that you always have to connect 3 times, and the 3rd time the bug magically disappears. The
                     * workaround will detect the error and retry up to 3 times to work around this issue for now.
                     *
                     * Over time, it has appeared that the cause of this issue may be that MySQL is chewing on a huge
                     * and slow query which prevents it from accepting new connections. This is not confirmed yet, but
                     * very likely. Either way, this "fix" still fixes the issue..
                     *
                     * This error seems to happen when MySQL is VERY busy processing queries. Wait a little before
                     * trying again
                     */
                    usleep(100000);
                }
            }

            try {
                $this->pdo->query('SET time_zone = "' . $this->configuration['timezone'] . '";');

            } catch (Throwable $e) {
                global $core;

                if (!Core::readRegister('no_time_zone') and (Core::compareRegister('init', 'script'))) {
                    throw $e;
                }

                /*
                 * Indicate that time_zone settings failed (this will subsequently be used by the init system to automatically initialize that as well)
                 */
                unset(Core::register['no_time_zone']);
                Core::register['time_zone_fail'] = true;
            }

            if (!empty($this->configuration['mode'])) {
                $pdo->query('SET $this->mode="' . $this->configuration['mode'] . '";');
            }

            return $pdo;

        } catch (Throwable $e) {
            $this->errorConnect($e);
        }
    }



    /**
     * @return void
     */
    protected function sshTunnel(): void
    {

    }



    /**
     * Connect with the main database
     *
     * @return mixed|void
     */
    public function init()
    {
        global $_CONFIG, $core;

        try {
            if (!empty($this->interface)) {
                /*
                 * Already connected to requested DB
                 */
                return $this->configuration_name;
            }

            /*
             * Get a database configuration connector and ensure its valid
             */
            $this->configuration = $this->ensureConnector($_CONFIG['db'][$this->configuration_name]);

            /*
             * Set the MySQL rand() seed for this session
             */
            // :TODO: On PHP7, update to random_int() for better cryptographic numbers
            $_SESSION['$this->random_seed'] = mt_rand();

            /*
             * Connect to database
             */
            log_console(tr('Connecting with SQL connector ":name"', array(':name' => $this->configuration_name)), 'VERYVERBOSE/cyan');
            $this->interface = $this->connect($this->configuration);

            /*
             * This is only required for the system connection
             */
            if ((PLATFORM_CLI) and (Core::register['script'] == 'init') and FORCE and !empty($this->configuration['init'])) {
                include(__DIR__ . '/handlers/sql-init-force.php');
            }

            /*
             * Check current init data?
             */
            if (empty(Core::register['skip_init_check'])) {
                if (!defined('FRAMEWORKDBVERSION')) {
                    /*
                     * Get database version
                     *
                     * This can be disabled by setting $_CONFIG[db][CONNECTORNAME][init] to false
                     */
                    if (!empty($_CONFIG['db'][$this->configuration_name]['init'])) {
                        try {
                            $r = $this->interface->query('SELECT `project`, `framework`, `offline_until` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                        } catch (Exception $e) {
                            if ($e->getCode() !== '42S02') {
                                if ($e->getMessage() === 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'offline_until\' in \'field list\'') {
                                    $r = $this->interface->query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

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
                                log_console(tr('$this->init(): No versions table found or no versions in versions table found, assumed empty database ":db"', array(':db' => $_CONFIG['db'][$this->configuration_name]['db'])), 'yellow');

                                define('FRAMEWORKDBVERSION', 0);
                                define('PROJECTDBVERSION', 0);

                                $this->using_database = true;

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
                                    $this->using_database = true;
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
                            log_console(tr('$this->init(): Found framework code version ":frameworkcodeversion" and framework database version ":frameworkdbversion"', array(':frameworkcodeversion' => FRAMEWORKCODEVERSION, ':frameworkdbversion' => FRAMEWORKDBVERSION)));
                            log_console(tr('$this->init(): Found project code version ":projectcodeversion" and project database version ":projectdbversion"', array(':projectcodeversion' => PROJECTCODEVERSION, ':projectdbversion' => PROJECTDBVERSION)));
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

            return $this->configuration_name;

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
    public function close()
    {
        global $_CONFIG, $core;

        try {
            unset($this->interface);

        } catch (Exception $e) {
            throw new SqlException(tr('$this->close(): Failed for connector ":connector"', array(':connector' => $this->configuration)), $e);
        }
    }


    /**
     * Import data from specified file
     *
     * @param string $file
     * @return void
     */
    public function import(string $file): void
    {
        $tel = 0;
        $handle = File::open($file, 'r');

        while (($buffer = fgets($handle)) !== false) {
            $buffer = trim($buffer);

            if (!empty($buffer)) {
                $this->interface->query($buffer);

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
    public function columns(array $source, array|string $columns): string
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
    public function values(array|string $source, array|strings $columns, string $prefix = ':'): array
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
    public function insertId(): ?int
    {
        $insert_id = $this->interface->lastInsertId();

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
    public function getIdOrName(mixed $entry, bool $seo = true, bool $code = false): array
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
    public function uniqueId(string $table, string $column = 'id', int $max = 10000000): int
    {
        $retries = 0;
        $maxretries = 50;

        while (++$retries < $maxretries) {
            $id = mt_rand(1, $max);

            // TODO Find a better algorithm than "Just try random shit until something sticks"
            if (!$this->get('SELECT `' . $column . '` FROM `' . $table . '` WHERE `' . $column . '` = :id', [':id' => $id])) {
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
    public function filters($params, $columns, $table = ''): array
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
     * Return a sequential array that can be used in $this->in
     *
     * @param array|string $source
     * @param string $column
     * @param bool $filter_null
     * @param bool $null_string
     * @return array
     */
    public function in(array|string $source, string $column = ':value', bool $filter_null = false, bool $null_string = false): array
    {
        if (empty($source)) {
            throw new OutOfBoundsException(tr('Specified source is empty'));
        }

        $column = Strings::startsWith($column, ':');
        $source = Arrays::force($source);

        return Arrays::sequentialKeys($source, $column, $filter_null, $null_string);
    }



    /**
     * Helper for building $this->in key value pairs
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
    public function inColumns(array $in, int|string|null $column_starts_with = null): string
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
    public function getCached($key, $query, $column = false, $execute = false, $expiration_time = 86400)
    {
        if (($value = Mc::get($key, '$this->')) === false) {
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

            $value = $this->get($query, $column, $execute, $this->configuration);

            Mc::set($value, $key, '$this->', $expiration_time);
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
     * @param null $this->configuration
     * @param int $expiration_time
     * @return array|false
     */
    public function listCached($key, $query, $execute = false, $numerical_array = false, $this->configuration = null, $expiration_time = 86400)
    {
        try {
            $this->configuration = $this->connectorName($this->configuration);

            if (($list = Mc::get($key, '$this->')) === false) {
                /*
                 * Keyword data not found in cache, get it from MySQL with
                 * specified query and store it in cache for next read
                 */
                $list = $this->list($query, $execute, $numerical_array, $this->configuration);

                Mc::set($list, $key, '$this->', $expiration_time);
            }

            return $list;

        } catch (SqlException $e) {
            throw new SqlException('$this->list_cached(): Failed', $e);
        }
    }


    /**
     * Fetch and return data from specified resource
     *
     * @param PDOStatement $r
     * @param string $column
     * @return string
     */
    public function fetchColumn(PDOStatement $r, string $column): string
    {
        $row = $this->fetch($r);

        if (!array_key_exists($column, $row)) {
            throw new SqlException(tr('$this->fetchColumn(): Specified column ":column" does not exist', [':column' => $column]));
        }

        return $row[$column];
    }



    /**
     * Merge database entry with new posted entry, overwriting the old DB values,
     * while skipping the values specified in $skip
     *
     * @param array $database_entry
     * @param array $post
     * @param mixed $skip
     * @return array|null The specified datab ase entry, updated with all the data from the specified $_POST entry
     */
    public function merge(array $database_entry, array $post, array|string|null $skip = null): ?array
    {
        if (!$post) {
            // No post was done, there is nothing to merge
            return $database_entry;
        }

        if ($skip === null) {
            $skip = 'id,status';
        }

        if (!is_array($database_entry)) {
            if ($database_entry !== null) {
                throw new SqlException(tr('Specified database source data type should be an array but is a ":type"', [':type' => gettype($database_entry)]));
            }

            // Nothing to merge
            $database_entry = [];
        }

        if (!is_array($post)) {
            if ($post !== null) {
                throw new SqlException(tr('Specified post source data type should be an array but is a ":type"', [':type' => gettype($post)]));
            }

            // Nothing to merge
            $post = [];
        }

        $skip = Arrays::force($skip);

        // Copy all POST variables over DB. Skip POST variables that have NULL value
        foreach ($post as $key => $value) {
            if (in_array($key, $skip)) {
                continue;
            }

            $database_entry[$key] = $value;
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
    public function is($value, $label, $not = false): string
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
    public function log(bool $enable)
    {
        if ($enable) {
            $this->query('SET global log_output = "FILE";');
            $this->query('SET global general_log_file="/var/log/mysql/queries.log";');
            $this->query('SET global general_log = 1;');

        } else {
            $this->query('SET global log_output = "OFF";');
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
    public function rowExists(string $table, string $column, int|string|null $value, ?int $id = null): bool
    {
        if ($id) {
            return $this->get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `id` != :id', true, [$column => $value, ':id' => $id]);
        }

        return $this->get('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . '', true, [$column => $value]));
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
    public function count($table, $where = '', $execute = null, $column = '`id`')
    {
        $expires = $_CONFIG['$this->large']['cache']['expires'];
        $hash = hash('sha1', $table . $where . $column . json_encode($execute));
        $count = $this->get('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', array(':hash' => $hash));

        if ($count) {
            return $count;
        }

        /*
         * Count value was not found cached, count it directly
         */
        $count = $this->get('SELECT COUNT(' . $column . ') AS `count` FROM `' . $table . '` ' . $where, 'count', $execute);

        $this->query('INSERT INTO `counts` (`createdby`, `count`, `hash`, `until`)
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
    public function currentDatabase(): string
    {
        return $this->getColumn('SELECT DATABASE() AS `database` FROM DUAL;');
    }


    /**
     *
     *
     * @param string $table
     * @param int $min
     * @param int $max
     * @return int
     */
    public function randomId(string $table, int $min = 1, int $max = 2147483648): int
    {
        $exists = true;
        $id = -1; // Initialize id negatively to ensure
        $timeout = 50; // Don't do more than 50 tries on this!

        while ($exists and --$timeout > 0) {
            $id = mt_rand($min, $max);
            $exists = $this->query('SELECT `id` FROM `' . $table . '` WHERE `id` = :id', [':id' => $id]);
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
    public function exec(string|Server $server, string $query, bool $root = false, bool $simple_quotes = false): array
    {
        try {
            $query = addslashes($query);

            if (!is_array($server)) {
                $server = Servers::get($server, true);
            }

            // Are we going to execute as root?
            if ($root) {
                My$this->createPasswordFile('root', $server['db_root_password'], $server);

            } else {
                My$this->createPasswordFile($server['db_username'], $server['db_password'], $server);
            }

            if ($simple_quotes) {
                $results = Servers::exec($server, 'mysql -e \'' . Strings::ends($query, ';') . '\'');

            } else {
                $results = Servers::exec($server, 'mysql -e \"' . Strings::ends($query, ';') . '\"');
            }

            My$this->deletePasswordFile($server);

            return $results;
        } catch (MysqlException $e) {
            // Ensure that the password file will be removed
            My$this->deletePasswordFile($server);
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
    //public function exec_get($server, $query, $root = false, $simple_quotes = false) {
    //    try {
    //
    //    } catch (Exception $e) {
    //        throw new SqlException(tr('$this->exec_get(): Failed'), $e);
    //    }
    //}



    /**
     *
     *
     * @param string $db_name
     * @return array
     */
    public function getDatabase(string $db_name): array
    {
        $database = $this->get('SELECT  `databases`.`id`,
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
     * Connector data will first be searched for in $_CONFIG[db][CONNECTOR]. If the connector is not found there, the $this->connectors table will be searched. If the connector is not found there either, NULL will be returned
     *
     * @param string $this->configuration_name The requested connector name
     * @return array The requested connector data. NULL if the specified connector does not exist
     */
    public function getConfiguration(string $this->configuration_name): array
    {
        if (!is_natural($this->configuration_name)) {
            // Connector was specified by name
            if (isset($_CONFIG['db'][$this->configuration_name])) {
                return $_CONFIG['db'][$this->configuration_name];
            }

            $where = ' `name` = :name ';
            $execute = array(':name' => $this->configuration_name);

        } else {
            /*
             * Connector was specified by id
             */
            $where = ' `id` = :id ';
            $execute = array(':id' => $this->configuration_name);
        }

        $this->configuration = $this->get('SELECT `id`,
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

                              FROM   `$this->connectors`

                              WHERE  ' . $where,

            null, $execute, 'core');

        if ($this->configuration) {
            $this->configuration['ssh_tunnel'] = array('required' => $this->configuration['ssh_tunnel_required'],
                'source_port' => $this->configuration['ssh_tunnel_source_port'],
                'hostname' => $this->configuration['ssh_tunnel_hostname']);

            unset($this->configuration['ssh_tunnel_required']);
            unset($this->configuration['ssh_tunnel_source_port']);
            unset($this->configuration['ssh_tunnel_hostname']);

            $_CONFIG['db'][$this->configuration_name] = $this->configuration;
        }

        return $this->configuration;
    }



    /**
     * Create an SQL connector in $_CONFIG['db'][$this->configuration_name] = $data
     *
     * @param string $this->configuration_name
     * @param array $this->configuration
     * @return array The specified connector data, with all informatinon completed if missing
     */
    public function makeConfiguration(string $configuration_name, array $configuration): array
    {
        if (empty($configuration['ssh_tunnel'])) {
            $configuration['ssh_tunnel'] = array();
        }

        if ($this->getConfiguration($configuration_name)) {
            if (empty($configuration['overwrite'])) {
                throw new SqlException(tr('The specified connector name ":name" already exists', [':name' => $configuration_name]));
            }
        }

        $configuration = $this->ensureConnector($configuration);

        if ($configuration['ssh_tunnel']) {
            $configuration['ssh_tunnel']['required'] = true;
        }

        Config::set('database.instances.' . $configuration_name, $configuration);
        return $configuration;
    }



    /**
     * Ensure all SQL connector fields are available
     *
     * @param array $configuration
     * @return array The specified $configuration data with all fields validated
     */
    public function ensureConfiguration(array $configuration): array
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

        $this->configuration['ssh_tunnel'] = $this->merge($template['ssh_tunnel'], isset_get($this->configuration['ssh_tunnel'], array()));
        $this->configuration = $this->merge($template, $this->configuration);

        if (!is_array($this->configuration['ssh_tunnel'])) {
            throw new SqlException(tr('Specified ssh_tunnel ":tunnel" should be an array but is a ":type"', [':tunnel' => $this->configuration['ssh_tunnel'], ':type' => gettype($this->configuration['ssh_tunnel'])]));
        }

        return $this->configuration;
    }



    /**
     * Test SQL functions over SSH tunnel for the specified server
     *
     * @param string|Server $server The server that is to be tested
     * @return void
     */
    public function testTunnel(string|Server $server): void
    {
        $this->configuration_name = 'test';
        $port = 6000;
        $server = servers_get($server, true);

        if (!$server['database_accounts_id']) {
            throw new SqlException(tr('Cannot test SQL over SSH tunnel, server ":server" has no database account linked', [':server' => $server['domain']]));
        }

        $this->makeConnector($this->configuration_name, [
            'port' => $port,
            'user' => $server['db_username'],
            'pass' => $server['db_password'],
            'ssh_tunnel' => [
                'source_port' => $port,
                'domain' => $server['domain']
            ]
        ]);

        $this->get('SELECT TRUE', true, null, $this->configuration_name);
    }

    

    protected function errorConnect(Throwable $e)
    {
        if ($e->getMessage() == 'could not find driver') {
            throw new PhpModuleNotAvailableException(tr('Failed to connect with ":driver" driver, it looks like its not available', [':driver' => $this->configuration['driver']]));
        }

        Log::Warning(tr('Encountered exception ":e" while connecting to database server, attempting to resolve', array(':e' => $e->getMessage())));

        /*
         * Check that all connector values have been set!
         */
        foreach (array('driver', 'host', 'user', 'pass') as $key) {
            if (empty($this->configuration[$key])) {
                throw new ConfigException(tr('The database configuration has key ":key" missing, check your database configuration in :rootconfig/', [':key' => $key, ':root' => ROOT]));
            }
        }

        switch ($e->getCode()) {
            case 1049:
                /*
                 * Database not found!
                 */
                $this->using_database = true;

                if (!(PLATFORM_CLI and ((Core::register['script'] == 'init') or (Core::register['script'] == 'sync')))) {
                    throw $e;
                }

                Log::warning(tr('Database base server conntection failed because database ":db" does not exist. Attempting to connect without using a database to correct issue', [':db' => $this->configuration['db']]));

                /*
                 * We're running the init script, so go ahead and create the DB already!
                 */
                $db  = $this->configuration['db'];
                unset($this->configuration['db']);
                $pdo = sql_connect($this->configuration);

                Log::warning(tr('Successfully connected to database server. Attempting to create database ":db"', [':db' => $db]));

                $pdo->query('CREATE DATABASE `'.$db.'`');

                Log::warning(tr('Reconnecting to database server with database ":db"', [':db' => $db]));

                $this->configuration['db'] = $db;
                return $this->connect(true);

            case 2002:
                /*
                 * Connection refused
                 */
                if (empty($this->configuration['ssh_tunnel']['required'])) {
                    throw new SqlException(tr('sql_connect(): Connection refused for host ":hostname::port"', array(':hostname' => $this->configuration['host'], ':port' => $this->configuration['port'])), $e);
                }

                /*
                 * This connection requires an SSH tunnel. Check if the tunnel process still exists
                 */
                load_libs('cli,servers');

                if (!cli_pidgrep($tunnel['pid'])) {
                    $server     = servers_get($this->configuration['ssh_tunnel']['domain']);
                    $registered = ssh_host_is_known($server['hostname'], $server['port']);

                    if ($registered === false) {
                        throw new SqlException(tr('sql_connect(): Connection refused for host ":hostname" because the tunnel process was canceled due to missing server fingerprints in the ROOT/data/ssh/known_hosts file and `ssh_fingerprints` table. Please register the server first', array(':hostname' => $this->configuration['ssh_tunnel']['domain'])), $e);
                    }

                    if ($registered === true) {
                        throw new SqlException(tr('sql_connect(): Connection refused for host ":hostname" on local port ":port" because the tunnel process either started too late or already died. The server has its SSH fingerprints registered in the ROOT/data/ssh/known_hosts file.', array(':hostname' => $this->configuration['ssh_tunnel']['domain'], ':port' => $this->configuration['port'])), $e);
                    }

                    /*
                     * The server was not registerd in the ROOT/data/ssh/known_hosts file, but was registered in the ssh_fingerprints table, and automatically updated. Retry to connect
                     */
                    return sql_connect($this->configuration, $use_database);
                }

//:TODO: SSH to the server and check if the msyql process is up!
                throw new SqlException(tr('sql_connect(): Connection refused for SSH tunnel requiring host ":hostname::port". The tunnel process is available, maybe the MySQL on the target server is down?', array(':hostname' => $this->configuration['host'], ':port' => $this->configuration['port'])), $e);

            case 2006:
                /*
                 * MySQL server went away
                 *
                 * Check if tunnel PID is still there
                 * Check if target server supports TCP forwarding.
                 * Check if the tunnel is still responding to TCP requests
                 */
                if (empty($this->configuration['ssh_tunnel']['required'])) {
                    /*
                     * No SSH tunnel was required for this connector
                     */
                    throw $e;
                }

                load_libs('servers,linux');

                $server  = servers_get($this->configuration['ssh_tunnel']['domain']);
                $allowed = linux_get_ssh_tcp_forwarding($server);

                if (!$allowed) {
                    /*
                     * SSH tunnel is required for this connector, but tcp fowarding
                     * is not allowed. Allow it and retry
                     */
                    if (!$server['allow_sshd_modification']) {
                        throw new SqlException(tr('sql_connect(): Connector ":connector" requires SSH tunnel to server, but that server does not allow TCP fowarding, nor does it allow auto modification of its SSH server configuration', array(':connector' => $this->configuration)), 'configuration');
                    }

                    log_console(tr('Connector ":connector" requires SSH tunnel to server ":server", but that server does not allow TCP fowarding. Server allows SSH server configuration modification, attempting to resolve issue', array(':server' => $this->configuration['ssh_tunnel']['domain'])), 'yellow');

                    /*
                     * Now enable TCP forwarding on the server, and retry connection
                     */
                    linux_set_ssh_tcp_forwarding($server, true);
                    log_console(tr('Enabled TCP fowarding for server ":server", trying to reconnect to MySQL database', array(':server' => $this->configuration['ssh_tunnel']['domain'])), 'yellow');

                    if ($this->configuration['ssh_tunnel']['pid']) {
                        log_console(tr('Closing previously opened SSH tunnel to server ":server"', array(':server' => $this->configuration['ssh_tunnel']['domain'])), 'yellow');
                        ssh_close_tunnel($this->configuration['ssh_tunnel']['pid']);
                    }

                    return sql_connect($this->configuration);
                }

                /*
                 * Check if the tunnel process is still up and about
                 */
                if (!cli_pid($this->configuration['ssh_tunnel']['pid'])) {
                    throw new SqlException(tr('sql_connect(): SSH tunnel process ":pid" is gone', array(':pid' => $this->configuration['ssh_tunnel']['pid'])), 'failed');
                }

                /*
                 * Check if we can connect over the tunnel to the remote SSH
                 */
                $results = inet_telnet(array('host' => '127.0.0.1',
                    'port' => $this->configuration['ssh_tunnel']['source_port']));

// :TODO: Implement further error handling.. From here on, appearently inet_telnet() did NOT cause an exception, so we have a result.. We can check the result for mysql server data and with that confirm that it is working, but what would.. well, cause a problem, because if everything worked we would not be here...

            default:
                throw new SqlException('Failed to create PDO SQL object', previous: $e);
        }
    }



    /**
     * Process SQL query errors
     *
     * @param SqlException $e The query exception
     * @param string|PDOStatement $query The executed query
     * @param array|null $execute The bound query variables
     * @return void
     */
    protected function errorQuery(Throwable $e, string|PDOStatement $query, ?array $execute = null): void
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
        $error = $this->errorInfo();

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

                        throw new SqlException(tr('Cannot use database with query ":query", this user has no access to it', [':query' => $this->buildQueryString($query, $execute, true)]), $e);

                    case 1049:
                        /*
                         * Specified database does not exist
                         */
                        $retry;

                        if ((Core::register['script'] == 'init')) {
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

                            $this->query('CREATE DATABASE `:db` DEFAULT CHARSET=":charset" COLLATE=":collate";', [':db' => $query['db'], ':charset' => $this->configuration['charset'], ':collate' => $this->configuration['collate']]);
                            return $this->connect($query);
                        }

                        throw new SqlException(tr('Cannot use database ":db", it does not exist', [':db' => $query['db']]), $e);

                    case 1052:
                        /*
                         * Integrity constraint violation
                         */
                        throw new SqlException(tr('Query ":query" contains an abiguous column', [':query' => $this->buildQueryString($query, $execute, true)]), $e);

                    case 1054:
                        /*
                         * Column not found
                         */
                        throw new SqlException(tr('Query ":query" refers to a column that does not exist', [':query' => $this->buildQueryString($query, $execute, true)]), $e);

                    case 1064:
                        /*
                         * Syntax error or access violation
                         */
                        if (str_contains(strtoupper($query), 'DELIMITER')) {
                            throw new SqlException(tr('Query ":query" contains the "DELIMITER" keyword. This keyword ONLY works in the MySQL console, and can NOT be used over MySQL drivers in PHP. Please remove this keword from the query', array(':query' => $this->buildQueryString($query, $execute, true))), $e);
                        }

                        throw new SqlException(tr('Query ":query" has a syntax error', [':query' => $this->buildQueryString($query, $execute, true)]), $e);

                    case 1072:
                        /*
                         * Adding index error, index probably does not exist
                         */
                        throw new SqlException(tr('Query ":query" failed with error 1072 with the message ":message"', [':query' => $this->buildQueryString($query, $execute, true), ':message' => isset_get($error[2])]), $e);

                    case 1005:
                        // no-break
                    case 1217:
                        // no-break
                    case 1452:
                        /*
                         * Foreign key error, get the FK error data from mysql
                         */
                        try {
                            $fk = $this->getColumn('SHOW ENGINE INNODB STATUS', 'Status', null));
                            $fk = Strings::from($fk, 'LATEST FOREIGN KEY ERROR');
                            $fk = Strings::from($fk, '------------------------');
                            $fk = Strings::until($fk, '------------');
                            $fk = str_replace("\n", ' ', $fk);

                        }catch(Exception $e) {
                            throw new SqlException(tr('Query ":query" failed with error 1005, but another error was encountered while trying to obtain FK error data', [':query' => $this->buildQueryString($query, $execute, true)]), $e);
                        }

                        throw new SqlException(tr('Query ":query" failed with error 1005 with the message ":message"', [':query' => $this->buildQueryString($query, $execute, true), ':message' => $fk]), $e);

                    case 1146:
                        /*
                         * Base table or view not found
                         */
                        throw new SqlException(tr('Query ":query" refers to a base table or view that does not exist', [':query' => $this->buildQueryString($query, $execute, true)]), $e);

                    default:
                        if (!is_string($query)) {
                            if (!is_object($query) or !($query instanceof PDOStatement)) {
                                throw new SqlException('Specified query is neither a SQL string or a PDOStatement it seems to be a ":type"', [':type' => gettype($query)], 'invalid');
                            }

                            $query = $query->queryString;
                        }

                        throw new SqlException(tr('Query ":query" failed', array(':query' => $this->buildQueryString(preg_replace('!\s+!', ' ', $query), $execute, true))), $e);

                        $body = "SQL STATE ERROR : \"".$error[0]."\"\n".
                            "DRIVER ERROR    : \"".$error[1]."\"\n".
                            "ERROR MESSAGE   : \"".$error[2]."\"\n".
                            "query           : \"".(PLATFORM_HTTP ? "<b>".Strings::Log($this->buildQueryString($query, $execute, true), 4096)."</b>" : Strings::Log($this->buildQueryString($query, $execute, true), 4096))."\"\n".
                            "date            : \"".date('d m y h:i:s')."\"\n";

                        if (isset($_SESSION)) {
                            $body .= "Session : ".print_r(isset_get($_SESSION), true)."\n";
                        }

                        $body .= "POST   : ".print_r($_POST  , true)."
                          GET    : ".print_r($_GET   , true)."
                          SERVER : ".print_r($_SERVER, true)."\n";

                        error_log('PHP SQL_ERROR: '.Strings::Log($error[2]).' on '.Strings::Log($this->buildQueryString($query, $execute, true), 4096));

                        if (!$_CONFIG['production']) {
                            throw new SqlException(nl2br($body), $e);
                        }

                        throw new SqlException(tr('An error has been detected, our staff has been notified about this problem.'), $e);
                }
        }
    }



    /**
     * Ensure that the specified limit is below or equal to the maximum configured limit
     *
     * @param int $limit
     * @return int
     */
    public function validLimit(int $limit): int
    {
        $limit = force_natural($limit);

        if ($limit > $this->configuration['limit_max']) {
            return $this->configuration['limit_max'];
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
    protected function limit(?int $limit = null, ?int $page = null): string
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
    public function show(string|PDOStatement $query, ?array $execute = null, bool $return_only = false): mixed
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
                    throw new SqlException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [':class' => get_class($query)]));
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

        if (empty(Core::register['clean_debug'])) {
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



    /**
     * Ensure that the specified query is either a select query or a show query
     *
     * @param string|PDOStatement $query
     * @return void
     */
    protected function ensureShowSelect(string|PDOStatement $query): void
    {
        if (is_object($query)) {
            $query = $query->queryString;
        }

        $query = strtolower(substr(trim($query), 10));

        if (!str_starts_with($query, 'select') and !str_starts_with($query, 'show')) {
            throw new SqlException('Query "' . Strings::log(Log::sql($query, $execute, true), 4096) . '" is not a SELECT or SHOW query and as such cannot return results');
        }
    }
}