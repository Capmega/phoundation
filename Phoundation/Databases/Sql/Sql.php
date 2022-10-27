<?php

namespace Phoundation\Databases\Sql;

use Exception;
use Paging;
use PDO;
use PDOException;
use PDOStatement;
use Phoundation\Cli\Cli;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Exception\LogException;
use Phoundation\Core\Log;
use Phoundation\Core\Meta;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Core\Timers;
use Phoundation\Databases\Sql\Exception\SqlColumnDoesNotExistsException;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\Schema\Schema;
use Phoundation\Date\DateTime;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Filesystem\File;
use Phoundation\Initialize\Initialize;
use Phoundation\Notifications\Notification;
use Phoundation\Processes\Commands;
use Phoundation\Servers\Server;
use Phoundation\Servers\Servers;
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
     * True if the SQL static data has been initialized
     *
     * @var bool $init
     */
    protected static bool $init = false;

    /**
     * Identifier of this instance
     *
     * @var string|null $instance_name
     */
    protected ?string $instance_name = null;

    /**
     * All SQL database configuration
     *
     * @var array $configuration
     */
    protected array $configuration = [];

    /**
     * Registers what database is in use
     *
     * @var string|null $using_database
     */
    protected ?string $using_database = null;

    /**
     * The PDO database interface
     *
     * @var PDO|null $pdo
     */
    protected ?PDO $pdo = null;

    /**
     * Schema object to access SQL database schema
     *
     * @var Schema
     */
    protected Schema $schema;



    /**
     * Sql constructor
     *
     * @param string|null $instance_name
     * @param bool $use_database
     * @throws Throwable
     */
    public function __construct(?string $instance_name = null, bool $use_database = true)
    {
        if ($instance_name === null) {
            $instance_name = 'system';
        }

        // Clean connector name, get connector configuration and ensure all required config data is there
        $this->instance_name = $instance_name;
        $this->configuration = self::readConfiguration($instance_name);
        $this->connect($use_database);
    }



    /**
     * Returns the configuration for this SQL object
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }



    /**
     * Reads, validates structure and returns the configuration for the specified instance
     *
     * @param string $instance
     * @return array
     */
    protected function readConfiguration(string $instance): array
    {
        try {
            $configuration = Config::get('databases.sql.instances.' . $instance);
        } catch (ConfigNotExistsException $e) {
            throw new SqlException(tr('The specified instance ":instance" is not configured', [
                ':instance' => $instance
            ]));
        }

        // Validate configuration
        if (!is_array($configuration)) {
            throw new ConfigException(tr('The configuration for the specified SQL database instance ":instance" is invalid, it should be an array', [
                ':instance' => $instance
            ]));
        }

        $this->configuration = $configuration;

// TODO Add support for instace configuration stored in database
//        $this->configuration = $this->get('SELECT `id`,
//                                     `created_on`,
//                                     `created_by`,
//                                     `meta_id`,
//                                     `status`,
//                                     `name`,
//                                     `seoname`,
//                                     `servers_id`,
//                                     `hostname`,
//                                     `driver`,
//                                     `database`,
//                                     `user`,
//                                     `password`,
//                                     `autoincrement`,
//                                     `buffered`,
//                                     `charset`,
//                                     `collate`,
//                                     `limit_max`,
//                                     `mode`,
//                                     `ssh_tunnel_required`,
//                                     `ssh_tunnel_source_port`,
//                                     `ssh_tunnel_hostname`,
//                                     `usleep`,
//                                     `pdo_attributes`,
//                                     `timezone`
//
//                              FROM   `$this->connectors`
//
//                              WHERE  ' . $where,
//
//            null, $execute, 'core');

        $template = [
            'driver'           => 'mysql',
            'host'             => '127.0.0.1',
            'port'             => null,
            'name'             => '',
            'user'             => '',
            'pass'             => '',
            'autoincrement'    => 1,
            'init'             => false,
            'buffered'         => false,
            'charset'          => 'utf8mb4',
            'collate'          => 'utf8mb4_general_ci',
            'limit_max'        => 10000,
            'mode'             => 'PIPES_AS_CONCAT,IGNORE_SPACE',
            'ssh_tunnel'       => [
                'required'    => false,
                'source_port' => null,
                'hostname'    => '',
                'usleep'      => 1200000
            ],
            'pdo_attributes'   => [],
            'version'          => '0.0.0',
            'timezone'         => 'UTC'
        ];

        // Copy the configuration options over the template
        $configuration = Sql::merge($template, $configuration);

        switch ($configuration['driver']) {
            case 'mysql':
                // Do we have a MySQL driver available?
                if (!defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                    // Whelp, MySQL library is not available
                    throw new PhpModuleNotAvailableException('Could not find the "MySQL" library for PDO. To install this on Ubuntu derivatives, please type "sudo apt install php-mysql');
                }

                // Apply MySQL specific requirements that always apply
                $configuration['pdo_attributes'][PDO::ATTR_ERRMODE]                  = PDO::ERRMODE_EXCEPTION;
                $configuration['pdo_attributes'][PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = !$configuration['buffered'];
                $configuration['pdo_attributes'][PDO::MYSQL_ATTR_INIT_COMMAND]       = 'SET NAMES ' . strtoupper($configuration['charset']);
                break;

            default:
                // Here be dragons!
                Log::warning(tr('WARNING: ":driver" DRIVER MAY WORK BUT IS NOT SUPPORTED!', [
                    ':driver' => $configuration['driver']
                ]));
        }

        return $configuration;
    }



//    /**
//     * Create an SQL connector in $_CONFIG['db'][$this->instance_name] = $data
//     *
//     * @param string $instance_name
//     * @param array $configuration
//     * @return array The specified connector data, with all informatinon completed if missing
//     */
//    public function makeConfiguration(string $instance_name, array $configuration): array
//    {
//        if (empty($configuration['ssh_tunnel'])) {
//            $configuration['ssh_tunnel'] = array();
//        }
//
//        if ($this->getConfiguration($instance_name)) {
//            if (empty($configuration['overwrite'])) {
//                throw new SqlException(tr('The specified connector name ":name" already exists', [':name' => $instance_name]));
//            }
//        }
//
//        $configuration = $this->ensureConnector($configuration);
//
//        if ($configuration['ssh_tunnel']) {
//            $configuration['ssh_tunnel']['required'] = true;
//        }
//
//        Config::set('database.instances.' . $instance_name, $configuration);
//        return $configuration;
//    }


    /**
     * Connect to database and do a DB version check.
     * If the database was already connected, then just ignore and continue.
     * If the database version check fails, then exception
     *
     * @param bool $use_database
     * @return void
     * @throws Throwable
     */
    protected function connect(bool $use_database = true): void
    {
        try {
            if (!empty($this->pdo)) {
                // Already connected to requested DB
                return;
            }

            // Does this connector require an SSH tunnel?
            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                $this->sshTunnel();
            }

            // Connect!
            $retries = 7;

            while (--$retries >= 0) {
                try {
                    $connect_string = $this->configuration['driver'] . ':host=' . $this->configuration['host'] . (empty($this->configuration['port']) ? '' : ';port=' . $this->configuration['port']) . (($use_database and $this->configuration['name']) ? ';dbname=' . $this->configuration['name'] : '');
                    $this->pdo = new PDO($connect_string, $this->configuration['user'], $this->configuration['pass'], $this->configuration['pdo_attributes']);

                    Log::success(tr('Connected to instance ":instance" with PDO connect string ":string"', [
                        ':instance' => $this->instance_name,
                        ':string' => $connect_string
                    ]), 3);
                    break;

                } catch (Exception $e) {
                    Log::error(tr('Failed to connect to instance ":instance" with PDO connect string ":string", error follows below', [
                        ':instance' => $this->instance_name,
                        ':string' => $connect_string
                    ]));
                    Log::error($e);

                    $message = $e->getMessage();

                    if (!str_contains($message, 'errno=32')) {
                        if ($e->getMessage() == 'ERROR 2013 (HY000): Lost connection to MySQL server at \'reading initial communication packet\', system error: 0') {
                            if (isset_get($this->configuration['ssh_tunnel']['required'])) {
                                // The tunneling server has "AllowTcpForwarding" set to "no" in the sshd_config, attempt
                                // auto fix
                                Commands::server($this->configuration['server'])->enableTcpForwarding($this->configuration['ssh_tunnel']['server']);
                                continue;
                            }
                        }

                        // Continue throwing the exception as normal, we'll retry to connect!
                        throw $e;
                    }

                    /*
                     * This is a workaround for the weird PHP MySQL error "PDO::__construct(): send of 5 bytes failed
                     * with errno=32 Broken pipe". So far we have not been able to find a fix for this, but we have
                     * noted that you always have to connect 3 times, and the 3rd time the bug magically disappears. The
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

            // Yay, we're using the database!
            $this->using_database = $this->configuration['name'];

            try {
                $this->pdo->query('SET time_zone = "' . $this->configuration['timezone'] . '";');

            } catch (Throwable $e) {
                Log::warning(tr('Failed to set timezone for database instance ":instance" with error ":e"', [':instance' => $this->instance_name, ':e' => $e->getMessage()]));

                if (!Core::readRegister('no_time_zone') and (Core::compareRegister('init', 'system', 'script'))) {
                    throw $e;
                }

                // Indicate that time_zone settings failed (this will subsequently be used by the init system to
                // automatically initialize that as well)
                // TODO Write somewhere else than Core "system" register as that will be readonly
                Core::deleteRegister('system', 'no_time_zone');
                Core::writeRegister(true, 'system', 'time_zone_fail');
            }

            if (!empty($this->configuration['mode'])) {
                $this->pdo->query('SET sql_mode="' . $this->configuration['mode'] . '";');
            }

        } catch (Throwable $e) {
            if ($e->getMessage() == 'could not find driver') {
                throw new PhpModuleNotAvailableException(tr('Failed to connect with ":driver" driver, it looks like its not available', [':driver' => $this->configuration['driver']]));
            }

            Log::Warning(tr('Encountered exception ":e" while connecting to database server, attempting to resolve', array(':e' => $e->getMessage())));

            // We failed to use the specified database, oh noes!
            switch ($e->getCode()) {
                case 1044:
                    // Access to database denied
                    throw new SqlException(tr('Cannot access database ":db", this user has no access to it', [
                        ':db' => $this->configuration['name']
                    ]), $e);

                case 1049:
                    throw new SqlException(tr('Cannot use database ":db", it does not exist', [
                        ':db' => $this->configuration['name']
                    ]), $e);

                case 2002:
                    // Connection refused
                    if (empty($this->configuration['ssh_tunnel']['required'])) {
                        throw new SqlException(tr('Connection refused for host ":hostname::port"', [
                            ':hostname' => $this->configuration['host'],
                            ':port' => $this->configuration['port']
                        ]), $e);
                    }

                    // This connection requires an SSH tunnel. Check if the tunnel process still exists
                    if (!Cli::PidGrep($tunnel['pid'])) {
                        $server     = servers_get($this->configuration['ssh_tunnel']['domain']);
                        $registered = ssh_host_is_known($server['hostname'], $server['port']);

                        if ($registered === false) {
                            throw new SqlException(tr('sql_connect(): Connection refused for host ":hostname" because the tunnel process was canceled due to missing server fingerprints in the ROOT/data/ssh/known_hosts file and `ssh_fingerprints` table. Please register the server first', array(':hostname' => $this->configuration['ssh_tunnel']['domain'])), $e);
                        }

                        if ($registered === true) {
                            throw new SqlException(tr('sql_connect(): Connection refused for host ":hostname" on local port ":port" because the tunnel process either started too late or already died. The server has its SSH fingerprints registered in the ROOT/data/ssh/known_hosts file.', array(':hostname' => $this->configuration['ssh_tunnel']['domain'], ':port' => $this->configuration['port'])), $e);
                        }

                        // The server was not registerd in the ROOT/data/ssh/known_hosts file, but was registered in the
                        // ssh_fingerprints table, and automatically updated. Retry to connect
                        $this->connect();
                        return;
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

                    $server  = Servers::get($this->configuration['ssh_tunnel']['domain']);
                    $allowed = Cli::getSshTcpForwarding($server);

                    if (!$allowed) {
                        /*
                         * SSH tunnel is required for this connector, but tcp fowarding
                         * is not allowed. Allow it and retry
                         */
                        if (!$server['allow_sshd_modification']) {
                            throw new SqlException(tr('Connector ":connector" requires SSH tunnel to server, but that server does not allow TCP fowarding, nor does it allow auto modification of its SSH server configuration', [':connector' => $this->configuration]));
                        }

                        Log::warning(tr('Connector ":connector" requires SSH tunnel to server ":server", but that server does not allow TCP fowarding. Server allows SSH server configuration modification, attempting to resolve issue', [':server' => $this->configuration['ssh_tunnel']['domain']]));

                        /*
                         * Now enable TCP forwarding on the server, and retry connection
                         */
                        linux_set_ssh_tcp_forwarding($server, true);
                        Log::warning(tr('Enabled TCP fowarding for server ":server", trying to reconnect to MySQL database', [':server' => $this->configuration['ssh_tunnel']['domain']]));

                        if ($this->configuration['ssh_tunnel']['pid']) {
                            Log::warning(tr('Closing previously opened SSH tunnel to server ":server"', [':server' => $this->configuration['ssh_tunnel']['domain']]));
                            Ssh::closeTunnel($this->configuration['ssh_tunnel']['pid']);
                        }

                        $this->connect();
                    }

                    // Check if the tunnel process is still up and about
                    if (!Cli::Pid($this->configuration['ssh_tunnel']['pid'])) {
                        throw new SqlException(tr('SSH tunnel process ":pid" is gone', [':pid' => $this->configuration['ssh_tunnel']['pid']]));
                    }

                    // Check if we can connect over the tunnel to the remote SSH
                    $results = Inet::telnet([
                        'host' => '127.0.0.1',
                        'port' => $this->configuration['ssh_tunnel']['source_port']
                    ]);

// :TODO: Implement further error handling.. From here on, appearently inet_telnet() did NOT cause an exception, so we have a result.. We can check the result for mysql server data and with that confirm that it is working, but what would.. well, cause a problem, because if everything worked we would not be here...

                default:
                    throw new SqlException(tr('Failed to connect to the SQL connector ":instance"', [':instance' => $this->instance_name]), previous: $e);
            }
        }
    }



    /**
     * Returns the name of the database that currently is in use by this database object
     *
     * @return string|null
     */
    public function getDatabase(): ?string
    {
        return $this->using_database;
    }



    /**
     * Returns an SQL schema object for this instance
     *
     * @return Schema
     */
    public function schema(): Schema
    {
        if (empty($this->schema)) {
            $this->schema = new Schema($this->instance_name);
        }

        return $this->schema;
    }



    /**
     * Connect with the main database
     *
     * @return void
     * @throws Exception|Throwable
     */
    public function init(): void
    {
        try {
            $this->connect();

            // Set the MySQL rand() seed for this session
            $_SESSION['sql_random_seed'] = random_int(PHP_INT_MIN, PHP_INT_MAX);

            // Connect to database
            Log::action(tr('Connecting to SQL instance ":name"', [':name' => $this->instance_name]), 2);

            // This is only required for the system connection
            if (Initialize::isInitializing()) {
                // We're doing an init. Check if we have a database, and if we don't, create one
            }

            // Check current init data?
            if (empty(Core::readRegister('system', 'skip_init_check'))) {
                if (!defined('FRAMEWORKDBVERSION')) {
                    /*
                     * Get database version
                     *
                     * This can be disabled by setting $_CONFIG[db][CONNECTORNAME][init] to false
                     */
                    if (!empty($_CONFIG['db'][$this->instance_name]['init'])) {
                        try {
                            $r = $this->pdo->query('SELECT `project`, `framework`, `offline_until` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

                        } catch (Exception $e) {
                            if ($e->getCode() !== '42S02') {
                                if ($e->getMessage() === 'SQLSTATE[42S22]: Column not found: 1054 Unknown column \'offline_until\' in \'field list\'') {
                                    $r = $this->pdo->query('SELECT `project`, `framework` FROM `versions` ORDER BY `id` DESC LIMIT 1;');

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
                                Log::warning(tr('No versions table found or no versions in versions table found, assumed empty database ":db"', [':db' => $this->configuration['name']]));

                                define('FRAMEWORKDBVERSION', 0);
                                define('PROJECTDBVERSION', 0);

                                $this->using_database = $this->configuration['name'];

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
                            Initialize::processVersionFail($e);
                        }

                        /*
                         * On console, show current versions
                         */
                        if ((PLATFORM_CLI) and Debug::enabled()) {
                            Log::notice(tr('Found framework code version ":Core::FRAMEWORKCODEVERSION" and framework database version ":frameworkdbversion"', [':Core::FRAMEWORKCODEVERSION' => Core::FRAMEWORKCODEVERSION, ':frameworkdbversion' => FRAMEWORKDBVERSION]));
                            Log::notice(tr('Found project code version ":projectcodeversion" and project database version ":projectdbversion"', [':projectcodeversion' => PROJECTCODEVERSION, ':projectdbversion' => PROJECTDBVERSION]));
                        }


                        /*
                         * Validate code and database version. If both FRAMEWORK and PROJECT versions of the CODE and DATABASE do not match,
                         * then check exactly what is the version difference
                         */
                        if ((Core::FRAMEWORKCODEVERSION != FRAMEWORKDBVERSION) or (PROJECTCODEVERSION != PROJECTDBVERSION)) {
                            Initialize::processVersionDiff();
                        }
                    }
                }

            } else {
                // We were told NOT to do an init check. Assume database framework and project versions are the same as
                //their code variants
                define('FRAMEWORKDBVERSION', Core::FRAMEWORKCODEVERSION);
                define('PROJECTDBVERSION'  , PROJECTCODEVERSION);
            }

            return;

        } catch (Exception $e) {
            // From here it is probably connector issues
            $e = new SqlException('Init failed', $e);

            try {
                $this->errorConnect($e);

            }catch(Exception $e) {
                throw new SqlException('Init failed', $e);
            }
        }
    }



    /**
     * Use the specified database
     *
     * @param string|null $database The database to use. If none was specifed, the configured system database will be
     *                              used
     * @return void
     * @throws Throwable
     */
    public function use(?string $database = null): void
    {
        $database = $this->getDatabaseName($database);
        $this->using_database = $database;

        try {
            $this->pdo->query('USE `' . $database . '`');
        } catch (Throwable $e) {
            // We failed to use the specified database, oh noes!
            switch ($e->getCode()) {
                case 1044:
                    // Access to database denied
                    throw new SqlException(tr('Cannot access database ":db", this user has no access to it', [
                        ':db' => $database
                    ]), $e);

                case 1049:
                    throw new SqlException(tr('Cannot use database ":db", it does not exist', [':db' => $this->configuration['name']]), $e);
            }

            throw $e;
        }
    }



    /**
     * Returns the specified database name or the configured system database name
     *
     * @param string|null $database
     * @return string
     */
    protected function getDatabaseName(?string $database): string
    {
        if ($database) {
            return $database;
        }

        return Config::get('databases.sql.instances.system.name');
    }



    /**
     * @return void
     */
    protected function sshTunnel(): void
    {

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
        static $retry = 0;

        try {
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
            if (Config::get('databases.sql.debug', false)) {
                $query = ' ' . $query;
            }

            if ($query[0] == ' ') {
                Log::sql($query, $execute);
            }

            Timers::get('query')->startLap();

            if (!$execute) {
                // Just execute plain SQL query string.
                $pdo_statement = $this->pdo->query($query);

            } else {
                // Execute the query with the specified $execute variables
                $pdo_statement = $this->pdo->prepare($query);

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
                    ->setTime(Timers::get('queries')->stopLap());
            }

            $retry = 0;
            return $pdo_statement;

        } catch (Throwable $e) {
            if (!$e instanceof PDOException) {
                switch ($e->getCode()) {
                    case 'forcedenied':
                        throw $e;

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
                            // This is automatically a problem!
                            throw new SqlException(tr('POSSIBLE ERROR: The specified $execute array contains key ":key" with non scalar value ":value"', [':key' => $key, ':value' => $value]), $e);
                        }
                    }
                }
            }

            // Get error data
            $error = $this->pdo->errorInfo();

            if (($error[0] == '00000') and !$error[1]) {
                $error = $e->errorInfo;
            }

            switch ($e->getCode()) {
                case 'denied':
                    // no-break
                case 'invalidforce':

                    /*
                     * Some database operation has failed
                     */
                    foreach ($e->getMessages() as $message) {
                        Log::error($message);
                    }

                    die(1);

                case 'HY093':
                    // Invalid parameter number: number of bound variables does not match number of tokens
                    // Get tokens from query
// TODO Check here what tokens do not match to make debugging easier
                    preg_match_all('/:\w+/imus', $query, $matches);

                    if (count($matches[0]) != count($execute)) {
                        throw new SqlException(tr('Query ":query" failed with error HY093, the number of query tokens does not match the number of bound variables. The query contains tokens ":tokens", where the bound variables are ":variables"', [
                            ':query' => $query,
                            ':tokens' => implode(',', $matches['0']),
                            ':variables' => implode(',', array_keys($execute))
                        ]), $e);
                    }

                    throw new SqlException(tr('Query ":query" failed with error HY093, One or more query tokens does not match the bound variables keys. The query contains tokens ":tokens", where the bound variables are ":variables"', [
                        ':query' => $query,
                        ':tokens' => implode(',', $matches['0']),
                        ':variables' => implode(',', array_keys($execute))
                    ]), $e);

                case '23000':
                    // 23000 is used for many types of errors!

// :TODO: Remove next 5 lines, 23000 cannot be treated as a generic error because too many different errors cause this one
//showdie($error)
//                // Integrity constraint violation: Duplicate entry
//                throw new SqlException('sql_error(): Query "'.Strings::Log($query, 4096).'" tries to insert or update a column row with a unique index to a value that already exists', $e);

                default:
                    switch (isset_get($error[1])) {
                        case 1052:
                            // Integrity constraint violation
                            throw new SqlException(tr('Query ":query" contains an abiguous column', [
                                ':query' => $this->buildQueryString($query, $execute, true)
                            ]), $e);

                        case 1054:
                            // Column not found
                            throw new SqlException(tr('Query ":query" refers to a column that does not exist', [
                                ':query' => $this->buildQueryString($query, $execute, true)
                            ]), $e);

                        case 1064:
                            // Syntax error or access violation
                            if (str_contains(strtoupper($query), 'DELIMITER')) {
                                throw new SqlException(tr('Query ":query" contains the "DELIMITER" keyword. This keyword ONLY works in the MySQL console, and can NOT be used over MySQL drivers in PHP. Please remove this keword from the query', [
                                    ':query' => $this->buildQueryString($query, $execute, true)
                                ]), $e);
                            }

                            throw new SqlException(tr('Query ":query" has a syntax error: ":error"', [
                                ':query' => $this->buildQueryString($query, $execute),
                                ':error' => Strings::from($error[2], 'syntax; ')
                            ], false), $e);

                        case 1072:
                            // Adding index error, index probably does not exist
                            throw new SqlException(tr('Query ":query" failed with error 1072 with the message ":message"', [
                                ':query'   => $this->buildQueryString($query, $execute, true),
                                ':message' => isset_get($error[2])
                            ]), $e);

                        case 1005:
                            // no-break
                        case 1217:
                            // no-break
                        case 1452:
                            // Foreign key error, get the FK error data from mysql
                            try {
                                $fk = $this->getColumn('SHOW ENGINE INNODB STATUS');
                                $fk = Strings::from($fk, 'LATEST FOREIGN KEY ERROR');
                                $fk = Strings::from($fk, '------------------------');
                                $fk = Strings::until($fk, '------------');
                                $fk = str_replace("\n", ' ', $fk);

                            }catch(Exception $e) {
                                throw new SqlException(tr('Query ":query" failed with error 1005, but another error was encountered while trying to obtain FK error data', [
                                    ':query' => $this->buildQueryString($query, $execute, true)
                                ]), $e);
                            }

                            throw new SqlException(tr('Query ":query" failed with error 1005 with the message ":message"', [
                                ':query'   => $this->buildQueryString($query, $execute, true),
                                ':message' => $fk
                            ]), $e);

                        case 1146:
                            // Base table or view not found
                            throw new SqlException(tr('Query ":query" refers to a base table or view that does not exist', [
                                ':query' => $this->buildQueryString($query, $execute, true)
                            ]), $e);
                    }
            }

            // Okay wut? Something went badly wrong
            global $argv;

            Notification::create()
                ->setCode('SQL_QUERY_ERROR')
                ->setGroups('developers')
                ->setTitle('SQL Query error')
                ->setMessage('
                SQL STATE ERROR : "' . $error[0] . '"
                DRIVER ERROR    : "' . $error[1] . '"
                ERROR MESSAGE   : "' . $error[2] . '"
                query           : "' . Strings::Log($this->buildQueryString($query, $execute, true)) . '"
                date            : "' . date('d m y h:i:s'))
                ->setDetails([
                    '$argv'     => $argv,
                    '$_GET'     => $_GET,
                    '$_POST'    => $_POST,
                    '$_SERVER'  => $_SERVER,
                    '$query'    => $query,
                    '$_SESSION' => $_SESSION
                ])
                ->log()
                ->send();

            throw new SqlException(tr('Query ":query" failed', [
                ':query' => $this->buildQueryString($query, $execute)
            ]), $e);

        }
    }


    /**
     * Write the specified data row in the specified table
     *
     * This is a simplified insert / update method to speed up writing basic insert or update queries. If the
     * $update_row[id] contains a value, the method will try to update instead of insert
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $insert_row
     * @param array $update_row
     * @return int|null
     * @throws Throwable
     */
    public function write(string $table, array $insert_row, array $update_row): ?int
    {
        if (isset_get($update_row['id'])) {
            $this->update($table, $update_row);
            return $update_row['id'];
        }

        return $this->insert($table, $insert_row);
    }



    /**
     * Insert the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     *
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $row
     * @return int
     * @throws Throwable
     */
    public function insert(string $table, array $row): int
    {
        // Set meta fields
        if (array_key_exists('meta_id', $row)) {
            $row['meta_id'] = Meta::init();
        }

        if (array_key_exists('createdon_by', $row)) {
            $row['createdon_by'] = Session::currentUser()->getId();
        }

        // Build bound variables for query
        $columns = $this->columns($row);
        $values  = $this->values($row);
        $keys    = $this->keys($row);

        $this->query('INSERT INTO `' . $table . '` (' . $columns . ') VALUES (' . $keys . ')', $values);

        return $this->pdo->lastInsertId();
    }



    /**
     * Insert the specified data row in the specified table
     *
     * This is a simplified insert method to speed up writing basic insert queries
     * @note This method assumes that the specifies rows are correct to the specified table. If columns not pertaining
     *       to this table are in the $row value, the query will automatically fail with an exception!
     * @param string $table
     * @param array $row
     * @return int
     * @throws Throwable
     */
    public function update(string $table, array $row): int
    {
        // Set meta fields
        if (array_key_exists('modified_on', $row)) {
            $row['modified_on'] = time();
        }

        if (array_key_exists('modified_by', $row)) {
            $row['modified_by'] = Session::currentUser()->getId();
        }

        // Build bound variables for query
        $keys   = $this->updateColumns($row);
        $values = $this->values($row);

        $this->query('UPDATE `' . $table . '` SET (' . $keys . ')', $values);

        return $this->pdo->lastInsertId();
    }



    /**
     * Builds and returns a query string from the specified query and execute parameters
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param bool $clean
     * @return string
     */
    public function buildQueryString(string|PDOStatement $query, ?array $execute = null, bool $clean = false): string
    {
        if (is_object($query)) {
            if (!($query instanceof PDOStatement)) {
                throw new SqlException(tr('Object of unknown class ":class" specified where PDOStatement was expected', [':class' => get_class($query)]));
            }

            // Query to be logged is a PDO statement, extract the query
            $query = $query->queryString;
        }

        $query = trim($query);

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
        return $this->pdo->prepare($query);
    }



    /**
     * Fetch data with default PDO::FETCH_ASSOC instead of PDO::FETCH_BOTH
     *
     * @param PDOStatement $resource
     * @param int $fetch_style
     * @return array|null
     * @throws Throwable
     */
    public function fetch(PDOStatement $resource, int $fetch_style = PDO::FETCH_ASSOC): ?array
    {
        $result = $resource->fetch($fetch_style);

        if ($result === false) {
            // There are no entries
            return null;
        }

        // Return data
        return $result;
    }



    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @return array|null
     * @throws SqlMultipleResultsException
     */
    public function get(string|PDOStatement $query, array $execute = null): ?array
    {
        $result = $this->query($query, $execute);

        switch ($result->rowCount()) {
            case 0:
                // No results. This is probably okay, but do check if the query was a select or show query, just to
                // be sure
                $this->ensureShowSelect($query, $execute);
                return null;

            case 1:
                return $this->fetch($result);

            default:
                // Multiple results, this is always bad for a function that should only return one result!
                $this->ensureShowSelect($query, $execute);
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
     * Returns the version for the selected database, if available.
     *
     * If the selected database does not have any database version information available, null will be returned
     *
     * @return string|null
     */
    public function getDatabaseVersion(): ?string
    {
// TODO Implement
        return null;
    }



    /**
     * Execute query and return only the first row
     *
     * @param string|PDOStatement $query
     * @param array|null $execute
     * @param bool $numerical_array
     * @return array
     * @throws Throwable
     */
    public function list(string|PDOStatement $query, ?array $execute = null, bool $numerical_array = false): array
    {
        if (is_object($query)) {
            $resource = $query;

        } else {
            $resource = $this->query($query, $execute);
        }

        $return = [];

        while ($row = $this->fetch($resource)) {
            if (is_scalar($row)) {
                $return[] = $row;

            } else {
                switch ($numerical_array ? 0 : count($row)) {
                    case 0:
                        /*
                         * Force numerical array
                         */
                        $return[] = $row;
                        break;

                    case 1:
                        $return[] = array_shift($row);
                        break;

                    case 2:
                        $return[array_shift($row)] = array_shift($row);
                        break;

                    default:
                        $return[array_shift($row)] = $row;
                }
            }
        }

        return $return;
    }



    /**
     * Close the connection for the specified connector
     *
     * @param string $instance_name
     * @return void
     */
    public function close(string $instance_name): void
    {
        unset(self::$instances[$instance_name]);
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
                $this->pdo->query($buffer);

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
     * Return a list of the specified $columns from the specified source
     *
     * @param array $source
     * @param string|null $prefix
     * @return string
     */
    public function updateColumns(array $source, ?string $prefix = null): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            $return[] = '`' . $prefix . $key . '` = :' . $key;
        }

        return implode(', ', $return);
    }



    /**
     * Return a list of the specified $columns from the specified source
     *
     * @param array $source
     * @param string|null $prefix
     * @return string
     */
    public function columns(array $source, ?string $prefix = null): string
    {
        $return = [];

        foreach ($source as $key => $value) {
            $return[] = '`' . $prefix . $key . '`';
        }

        return implode(', ', $return);
    }



    /**
     * Converts the specified row data into a PDO bound variables compatible key > values array
     *
     * @param array|string $source
     * @param string|null $prefix
     * @return string
     */
    public function keys(array|string $source, ?string $prefix = null): string
    {
        $return  = [];

        foreach ($source as $key => $value) {
            $return[':' . $prefix . $key] = $value;
        }

        $return = array_keys($return);
        $return = implode(', ', $return);

        return $return;
    }



    /**
     * Converts the specified row data into a PDO bound variables compatible key > values array
     *
     * @param array|string $source
     * @param string|null $prefix
     * @return array
     */
    public function values(array|string $source, ?string $prefix = null): array
    {
        $return  = [];

        foreach ($source as $key => $value) {
            $return[':' . $prefix . $key] = $value;
        }

        return $return;
    }



    /**
     * Get the current last insert id for this SQL database instance
     *
     * @return ?int
     */
    public function insertId(): ?int
    {
        $insert_id = $this->pdo->lastInsertId();

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
            $return['where'] = '`id` = :id';
            $return['execute'] = array(':id' => $entry);

        } elseif (is_string($entry)) {
            if ($seo) {
                if ($code) {
                    $return['where'] = '`name` = :name OR `seoname` = :seoname OR `code` = :code';
                    $return['execute'] = array(':code' => $entry,
                        ':name' => $entry,
                        ':seoname' => $entry);

                } else {
                    $return['where'] = '`name` = :name OR `seoname` = :seoname';
                    $return['execute'] = array(':name' => $entry,
                        ':seoname' => $entry);
                }

            } else {
                if ($code) {
                    $return['where'] = '`name` = :name OR `code` = :code';
                    $return['execute'] = array(':code' => $entry,
                        ':name' => $entry);

                } else {
                    $return['where'] = '`name` = :name';
                    $return['execute'] = array(':name' => $entry);
                }
            }

        } else {
            throw new SqlException(tr('Invalid entry with type ":type" specified', [':type' => gettype($entry)]));
        }

        return $return;
    }



    /**
     * Return a unique, non-existing ID for the specified table.column
     *
     * @param string $table
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
     * @todo Reimplement this without $params
     * @param $array
     * @param $columns
     * @param string $table
     * @return array
     */
    public function filters(array $array, string|array $columns, string $table = ''): array
    {
        $return = [
            'filters' => [],
            'execute' => []
        ];

        $filters = Arrays::keep($array, $columns);

        foreach ($filters as $key => $value) {
            $safe_key = str_replace('`.`', '_', $key);

            if ($value === null) {
                $return['filters'][] = ($table ? '`' . $table . '`.' : '') . '`' . $key . '` IS NULL';

            } else {
                $return['filters'][] = ($table ? '`' . $table . '`.' : '') . '`' . $key . '` = :' . $safe_key;
                $return['execute'][':' . $safe_key] = $value;
            }
        }

        return $return;
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
     * @param string $key
     * @param string $query
     * @param bool $column
     * @param array|null $execute
     * @param int $expiration_time
     * @return array|null
     */
    public function getCached(string $key, string $query, bool $column = false, ?array $execute = null, int $expiration_time = 86400): ?array
    {
        if (($value = Mc::db($this->getDatabase())->get($key, '$this->')) === false) {
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

            Mc::db($this->getDatabase())->set($value, $key, '$this->', $expiration_time);
        }

        return $value;
    }

    

    /**
     * Try to get data list from memcached. If not available, get it from
     * MySQL and store results in memcached for future use
     *
     * @param string $key
     * @param string $query
     * @param array|null $execute
     * @param bool $numerical_array
     * @param int $expiration_time
     * @return array|null
     */
    public function listCached(string $key, string $query, ?array $execute = null, bool $numerical_array = false, int $expiration_time = 86400): ?array
    {
        if (($list = Mc::db($this->getDatabase())->get($key, '$this->')) === false) {
            /*
             * Keyword data not found in cache, get it from MySQL with
             * specified query and store it in cache for next read
             */
            $list = $this->list($query, $execute, $numerical_array, $this->configuration);

            Mc::db($this->getDatabase())->set($list, $key, '$this->', $expiration_time);
        }

        return $list;
    }



    /**
     * Merge database entry with new posted entry, overwriting the old DB values,
     * while skipping the values specified in $skip
     *
     * @param array|null $database_entry
     * @param array|null $post
     * @param array|string|null $skip
     * @param bool $recurse
     * @return array|null The specified datab ase entry, updated with all the data from the specified $_POST entry
     */
    public static function merge(?array $database_entry, ?array $post, array|string|null $skip = null, bool $recurse = true): ?array
    {
        if (!$post) {
            if (!is_array($post)) {
                throw new SqlException(tr('Specified post source data type should be an array but is a ":type"', [':type' => gettype($post)]));
            }

            // No post was done, there is nothing to merge
            return $database_entry;
        }

        if (!is_array($database_entry)) {
            if ($database_entry !== null) {
                throw new SqlException(tr('Specified database source data type should be an array or NULL but is a ":type"', [':type' => gettype($database_entry)]));
            }

            // Database entry is empty
            $database_entry = [];
        }

        // By default, do not copy the id, meta_id and status columns
        if ($skip === null) {
            $skip = 'id,meta_id,status';
        }

        $skip = Arrays::force($skip);

        // Copy all POST variables over DB. Skip POST variables that have NULL value
        foreach ($database_entry as $key => $value) {
            if (in_array($key, $skip)) {
                // This can be skipped
                continue;
            }

            if (!array_key_exists($key, $post)) {
                // This key doesn't exist in post, continue to the next
                continue;
            }

            if (is_array($value)) {
                // This entry is an array, do a recursive merge if post was specified too
                if (!is_array($post[$key])) {
                    // Whoops, $post format is invalid
                    throw new OutOfBoundsException(tr('Specified post entry key ":key" is invalid, it should be an array but is a ":type"', [':key' => $key, ':type' => gettype($post[$key])]));
                }

                // Recurse
                if ($recurse) {
                    $database_entry[$key] = Sql::merge($value, $post[$key], $skip, $recurse);
                }
            } elseif (is_scalar($post[$key]) or ($post[$key] === null)) {
                if (is_scalar($value) or ($value === null)) {
                    // Copy post key to database entry
                    $database_entry[$key] = $post[$key];
                } else {
                    // Whoops, $post format is invalid
                    throw new OutOfBoundsException(tr('Specified post entry key ":key" is invalid, it should be an array but is a ":type"', [':key' => $key, ':type' => gettype($post[$key])]));
                }

            } else {
                // Invalid datatype
                throw new OutOfBoundsException(tr('Specified post entry key ":key" has an invalid datatype, it should be one of NULL, string, int, float, or bool but is a ":type"', [':key' => $key, ':type' => gettype($post[$key])]));
            }
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
    public function is($value, $label, bool $not = false): string
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
            return (bool) $this->getColumn('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column . ' AND `id` != :id', [$column => $value, ':id' => $id]);
        }

        return (bool) $this->getColumn('SELECT `id` FROM `' . $table . '` WHERE `' . $column . '` = :' . $column, [$column => $value]);
    }



    /**
     * NOTE: Use only on huge tables (> 1M rows)
     *
     * Return table row count by returning results count for SELECT `id`
     * Results will be cached in a counts table
     *
     * @param string $table
     * @param string $where
     * @param array|null $execute
     * @param string $column
     * @return int
     */
    public function count(string $table, string $where = '', ?array $execute = null, string $column = '`id`'): int
    {
        $expires = Config::get('databases.cache.expires');
        $hash = hash('sha1', $table . $where . $column . json_encode($execute));
        $count = $this->get('SELECT `count` FROM `counts` WHERE `hash` = :hash AND `until` > NOW()', 'count', array(':hash' => $hash));

        if ($count) {
            return $count;
        }

        // Count value was not found cached, count it directly
        $count = $this->get('SELECT COUNT(' . $column . ') AS `count` FROM `' . $table . '` ' . $where, 'count', $execute);

        // TODO Use a query cache class
        $this->query('INSERT INTO `counts` (`created_by`, `count`, `hash`, `until`)
                            VALUES               (:created_by , :count , :hash , NOW() + INTERVAL :expires SECOND)
         
                            ON DUPLICATE KEY UPDATE `count`      = :update_count,
                                                    `modified_on` = NOW(),
                                                    `modified_by` = :update_modified_by,
                                                    `until`      = NOW() + INTERVAL :update_expires SECOND',

                            [
                                ':created_by' => isset_get($_SESSION['user']['id']),
                                ':hash' => $hash,
                                ':count' => $count,
                                ':expires' => $expires,
                                ':update_expires' => $expires,
                                ':update_modified_by' => isset_get($_SESSION['user']['id']),
                                ':update_count' => $count
                            ]);

        return $count;
    }



    /**
     * Returns what database currently is selected
     *
     * @return string
     */
    public function currentDatabase(): string
    {
        return $this->getColumn('SELECT DATABASE() AS `database` FROM DUAL;');
    }



    /**
     * Return a random but existing row id from the specified table
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
     * Returns information about the specified database
     *
     * @param string $database
     * @return array
     */
    public function getDatabaseInformation(string $database): array
    {
        $return = $this->get('SELECT  `databases`.`id`,
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

                                  [':name' => $database]);

        if (!$return) {
            throw new SqlException(tr('Specified database ":database" does not exist', [':database' => $database]));
        }

        return $return;
    }



    /**
     * Test SQL functions over SSH tunnel for the specified server
     *
     * @param string|Server $server The server that is to be tested
     * @return void
     */
    public function testTunnel(string|Server $server): void
    {
        $this->instance_name = 'test';
        $port = 6000;
        $server = servers_get($server, true);

        if (!$server['database_accounts_id']) {
            throw new SqlException(tr('Cannot test SQL over SSH tunnel, server ":server" has no database account linked', [':server' => $server['domain']]));
        }

        $this->makeConnector($this->instance_name, [
            'port' => $port,
            'user' => $server['db_username'],
            'pass' => $server['db_password'],
            'ssh_tunnel' => [
                'source_port' => $port,
                'domain' => $server['domain']
            ]
        ]);

        $this->get('SELECT TRUE', true, null, $this->instance_name);
    }



    /**
     * Drop the database for this connector
     *
     * @return void
     */
    public function drop(): void
    {
        $this->query('DROP DATABASE ' . $this->configuration['name']);
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

        if (!Core::readRegister('debug', 'clean')) {
            $query = str_replace("\n", ' ', $query);
            $query = Strings::nodouble($query, ' ', '\s');
        }

        // Debug::enabled() already logs the query, don't log it again
        if (!Debug::enabled()) {
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
    protected function ensureShowSelect(string|PDOStatement $query, ?array $execute): void
    {
        if (is_object($query)) {
            $query = $query->queryString;
        }

        $query = strtolower(substr(trim($query), 0, 10));

        if (!str_starts_with($query, 'select') and !str_starts_with($query, 'show')) {
            throw new SqlException('Query "' . Strings::log(Log::sql($query, $execute, true), 4096) . '" is not a SELECT or SHOW query and as such cannot return results');
        }
    }
}
