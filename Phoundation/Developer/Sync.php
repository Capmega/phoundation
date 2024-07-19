<?php

/**
 * Sync
 *
 * This class contains functionalities to sync different environment with each other, facilitating development work that
 * sometimes requires to work with production data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */

declare(strict_types=1);

namespace Phoundation\Developer;

use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataTimeout;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Developer\Exception\SyncConfigurationException;
use Phoundation\Developer\Exception\SyncEnvironmentDoesNotExistsException;
use Phoundation\Developer\Exception\SyncException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Os\Processes\Commands\Interfaces\PhoInterface;
use Phoundation\Os\Processes\Commands\Pho;
use Phoundation\Os\Processes\Commands\Rsync;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Interfaces\ServerInterface;
use Phoundation\Servers\Server;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigFileDoesNotExistsException;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;

class Sync
{
    use TraitDataTimeout;

    /**
     * Sets if the system initializes after syncing
     *
     * @var bool $init
     */
    protected bool $init = true;

    /**
     * Sets if the system uses locking or not
     *
     * @var bool $lock
     */
    protected bool $lock = true;

    /**
     * Tracks the environment we're in
     *
     * @var string $environment
     */
    protected string $environment;

    /**
     * Tracks the configuration for the environment we're in
     *
     * @var array $configuration
     */
    protected array $configuration;

    /**
     * Tracks the configuration for the environment we're in
     *
     * @var array $environment_config
     */
    protected array $environment_config;

    /**
     * The state the remote environment is in
     *
     * Must be one of "not-exist", "partial", "full"
     *
     * @var string|bool $environment_state
     */
    protected string|bool $environment_state = false;

    /**
     * The remote server
     *
     * @var ServerInterface|null $server
     */
    protected ?ServerInterface $server = null;

    /**
     * The source temp path
     *
     * @var string|null $source_temp_path
     */
    protected ?string $source_temp_path = null;

    /**
     * The target temp path
     *
     * @var FsDirectoryInterface|null $target_temp_path
     */
    protected ?FsDirectoryInterface $target_temp_path = null;

    /**
     * Tracks the dump files to sync
     *
     * @var array $dump_files
     */
    protected array $dump_files = [];


    /**
     * Sync class constructor
     */
    public function __construct()
    {
        $this->timeout          = 3600;
        $this->target_temp_path = FsDirectory::getTemporaryObject();
    }


    /**
     * Returns if the system initializes after syncing
     *
     * @return bool
     */
    public function getInit(): bool
    {
        return $this->init;
    }


    /**
     * Sets if the system initializes after syncing
     *
     * @param bool $init
     *
     * @return Sync
     */
    public function setInit(bool $init): Sync
    {
        $this->init = $init;

        return $this;
    }


    /**
     * Returns the remote temporary path
     *
     * @return string|null
     */
    public function getSourceTempPath(): ?string
    {
        return $this->source_temp_path;
    }


    /**
     * Returns if the sync should use locking or not
     *
     * @return bool
     */
    public function getLock(): bool
    {
        return $this->lock;
    }


    /**
     * Sets if the sync should use locking or not
     *
     * @param bool $lock
     *
     * @return Sync
     */
    public function setLock(bool $lock): Sync
    {
        $this->lock = $lock;

        return $this;
    }


    /**
     * Sync from the specified environment to this environment
     *
     * @param string $environment
     *
     * @return static
     */
    public function from(string $environment): static
    {
        Log::information(tr('Synchronizing from environment ":environment" to this environment ":local"', [
            ':environment' => $environment,
            ':local'       => ENVIRONMENT,
        ]));

        return $this->initConfiguration($environment)
                    ->scan($this->server)
                    ->lock($this->server)
                    ->dumpAllDatabases($this->server)
                    ->unlock($this->server)
                    ->copyDumps($this->server, null)
                    ->copyContent($this->server, null)
                    ->cleanTemporary($this->server)
                    ->importAllConnectors(null)
                    ->init(null)
                    ->clearCaches(null);
    }


    /**
     * Clears caches for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function clearCaches(?ServerInterface $server): static
    {
        Log::action(tr('Clearing caches on environment ":server"', [
            ':server' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-clear-caches');

//        $this->getPhoCommand($server)
//             ->setPhoCommands('cache clear')
//             ->executeReturnString();

        return $this->executeHook('pre-clear-caches');
    }


    /**
     * Returns the environment for the specified server
     *
     * @param ServerInterface|null $server
     *
     * @return string
     */
    protected function getEnvironmentForServer(?ServerInterface $server): string
    {
        return $server ? $this->environment : ENVIRONMENT;
    }


    /**
     * Execute the specified hook(s)
     *
     * @param array|string $hooks
     *
     * @return static
     */
    protected function executeHook(array|string $hooks): static
    {
        if ($this->configuration['execute_hooks']) {
            Hook::new('sync')->execute($hooks);
        }

        return $this;
    }


    /**
     * Returns a new Sync object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Returns a new Pho command process
     *
     * @param ServerInterface|null $server
     * @param array|string|null $pho_commands
     * @return PhoInterface
     */
    protected function getPhoCommand(?ServerInterface $server, array|string|null $pho_commands = null): PhoInterface
    {
        if ($server) {
            return Pho::new($this->configuration['path'] . 'pho')
                      ->setPhoCommands($pho_commands)
                      ->setEnvironment($this->environment)
                      ->setServer($server)
                      ->setSudo($this->configuration['sudo']);
        }

        return Pho::new(DIRECTORY_ROOT . 'pho')
                  ->setPhoCommands($pho_commands);
    }


    /**
     * Imports the Redis connectors for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function init(?ServerInterface $server): static
    {
        Log::action(tr('Executing system initialization on environment ":server"', [
            ':server' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-init');

        return $this->executeHook('post-init');
    }


    /**
     * Imports all the connectors for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function importAllConnectors(?ServerInterface $server): static
    {
        Log::action(tr('Importing connector dumps on environment ":server"', [
            ':server' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-import-connectors');

        // Get connectors from target environment
        Config::setEnvironment($this->getEnvironmentForServer($server));
        $connectors = Config::getArray('databases.connectors');

        // Return Config to default environment
        Config::setEnvironment(ENVIRONMENT);

        // Dump all connectors
        foreach ($this->dump_files as $connector_name => $file) {
            $connector = Connector::newFromSource($connectors[$connector_name]);
            $connector->setName($connector_name);

            if ($connector->getType() === 'memcached') {
                // Memcached is volatile, contains only temp data, and cannot (and should not) be dumped
                continue;
            }

            if (!$connector->getSync() and ($connector->getName() !== 'system')) {
                // This connector should not be sync. The connector "system" will always be synced, though!
                Log::warning(tr('Not importing database ":database" because it should not be synced', [
                    ':database' => $connector->getDatabase(),
                ]));
                continue;
            }

            $this->importConnector($server, $file, $connector);
        }

        return $this->executeHook('post-import-connectors');
    }


    /**
     * Import the specified connector
     *
     * @param ServerInterface|null $server
     * @param string               $file
     * @param ConnectorInterface   $connector
     *
     * @return $this
     */
    public function importConnector(?ServerInterface $server, string $file, ConnectorInterface $connector): static
    {
        $file = FsFile::new($this->target_temp_path . $file, $this->target_temp_path->getRestrictions());

        Log::action(tr('Importing ":driver" database with connector ":connector" for environment ":environment" from file ":file"', [
            ':driver'      => $connector->getDriver(),
            ':environment' => $this->getEnvironmentForServer($server),
            ':connector'   => $connector->getDisplayName(),
            ':file'        => $file,
        ]));

        // Execute the dump on the specified server
        $this->executeHook('pre-import-connector')
             ->getPhoCommand($server, 'databases import')
             ->addArguments([
                 '-L', Log::getThreshold(),
                 '--connector',
                 $connector->getName(),
                 '--database',
                 $connector->getDatabase(),
                 '--file',
                 $file->getPath(),
             ])
             ->executePassthru();

        return $this->executeHook('post-import-connector');
    }


    /**
     * Clean all the connector dumps for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function cleanTemporary(?ServerInterface $server): static
    {
        Log::action(tr('Cleaning all temporary data for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        return $this->executeHook('pre-clean-temporary')
                    ->clearTemporaryPath($server)
                    ->executeHook('post-clean-temporary');
    }


    /**
     * Clears the temporary path for the specified server
     *
     * @param ServerInterface|null $server
     *
     * @return $this
     */
    protected function clearTemporaryPath(?ServerInterface $server): static
    {
        $this->getPhoCommand($server)
             ->setPhoCommands(['system', 'temporary', 'clear'])
             ->addArguments($this->source_temp_path)
             ->executeNoReturn();

        return $this;
    }


    /**
     * Copy all connectors
     *
     * @param ServerInterface|null $from
     * @param ServerInterface|null $to
     *
     * @return static
     */
    protected function copyContent(?ServerInterface $from, ?ServerInterface $to): static
    {
        Log::action(tr('Copying all content from environment ":from" to ":to"', [
            ':from' => $this->getEnvironmentForServer($from),
            ':to'   => $this->getEnvironmentForServer($to),
        ]));

        $this->executeHook('pre-copy-content');

        return $this->executeHook('post-copy-content');
    }


    /**
     * Copy all connectors
     *
     * @param ServerInterface|null $from
     * @param ServerInterface|null $to
     *
     * @return static
     */
    protected function copyDumps(?ServerInterface $from, ?ServerInterface $to): static
    {
        Log::action(tr('Copying all dumped connector from environment ":from" to ":to"', [
            ':from' => $this->getEnvironmentForServer($from),
            ':to'   => $this->getEnvironmentForServer($to),
        ]));

        $this->executeHook('pre-copy-connectors');

        foreach ($this->dump_files as $file) {
            // Build source / target strings
            if ($from) {
                // We're syncing FROM a server TO LOCAL
                $source = $from->getHostname() . ':' . $this->source_temp_path . $file;
                $target = $this->target_temp_path . $file;

            } else {
                // We're syncing FROM LOCAL TO a server
                $source = $this->target_temp_path . $file;
                $target = $from->getHostname() . ':' . $this->source_temp_path . $file;
            }

            // Execute rsync
            Rsync::new()
                 ->setSource($source)
                 ->setTarget($target)
                 ->setArchive(true)
                 ->setVerbose(true)
                 ->setSourceServer($from)
                 ->setTargetServer($to)
                 ->setRemoteSudo((bool) $this->configuration['sudo'])
                 ->execute();

            $file = FsFile::new($target, $this->target_temp_path->getRestrictions());

            if ($file->exists()){
                Log::success(tr('Received target file ":file" with size ":size"', [
                    ':file' => $file,
                    ':size' => Numbers::getHumanReadableAndPreciseBytes($file->getSize()),
                ]));

            } else {
                Log::warning(tr('Failed to receive target file ":file"', [
                    ':file' => $file,
                ]));
            }
        }

        return $this->executeHook('post-copy-connectors');
    }


    /**
     * Lock all connectors in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function unlock(?ServerInterface $server): static
    {
        Log::action(tr('Unlocking environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        return $this->executeHook('pre-unlock')
                    ->unlockSystem($server)
                    ->unlockSql($server)
                    ->executeHook('post-unlock');
    }


    /**
     * Unlock SQL connectors for read/write for normal use
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function unlockSql(?ServerInterface $server): static
    {
        Log::action(tr('Unlocking SQL connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));
        $this->executeHook('pre-unlock-sql');

        return $this->executeHook('post-unlock-sql');
    }


    /**
     * Lock all connectors in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function unlockSystem(?ServerInterface $server): static
    {
        Log::action(tr('Unlocking system for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-lock-system')
             ->getPhoCommand($server)
             ->setPhoCommands(['system', 'modes', 'reset'])
             ->executeReturnString();

        return $this->executeHook('post-unlock-system');
    }


    /**
     * Dumps all the connectors for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function dumpAllDatabases(?ServerInterface $server): static
    {
        Log::action(tr('Dumping all configured connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-dump-all-connectors');

        // Get connectors from target environment
        Config::setEnvironment($this->getEnvironmentForServer($server));
        $connectors = Config::getArray('databases.connectors');

        // Return Config to default environment
        Config::setEnvironment(ENVIRONMENT);

        // Dump all connectors
        foreach ($connectors as $name => $connector) {
            if ($this->getDumpConnector($name)) {
                $connector = Connector::newFromSource($connector);
                $connector->setName($name);

                if ($connector->getType() === 'memcached') {
                    // Memcached is volatile, contains only temp data, and cannot (and should not) be dumped
                    continue;
                }

                if (!$connector->getSync() and ($connector->getName() !== 'system')) {
                    // This connector should not be sync. The connector "system" will always be synced, though!
                    Log::warning(tr('Not dumping database ":database" because it should not be synced', [
                        ':database' => $connector->getDatabase(),
                    ]));
                    continue;
                }

                $this->dumpConnector($server, $connector);
            }
        }

        return $this->executeHook('post-dump-all-connectors');
    }


    /**
     * Returns true if this connector should be dumped
     *
     * @param string $connector_name
     *
     * @return bool
     */
    protected function getDumpConnector(string $connector_name): bool
    {
        if (!isset($this->configuration['sync']['connectors'])) {
            return true;
        }

        if ($this->configuration['sync']['connectors'] === false) {
            return false;
        }

        if ($this->configuration['sync']['connectors'] === true) {
            return true;
        }

        if (!is_array($this->configuration['sync']['connectors'])) {
            throw new SyncConfigurationException(tr('Connectors configuration is invalid, it should be a boolean (true or false), or an array but contains ":content"', [
                ':connector' => $connector_name,
                ':content'   => $this->configuration['sync']['connectors'],
            ]));
        }

        // From here it's an array
        return in_array($connector_name, $this->configuration['sync']['connectors']);
    }


    /**
     * Dumps the specified connector
     *
     * @param ServerInterface|null $server
     * @param ConnectorInterface   $connector
     *
     * @return static
     */
    protected function dumpConnector(?ServerInterface $server, ConnectorInterface $connector): static
    {
        Log::action(tr('Dumping ":driver" database with connector ":connector" for environment ":environment"', [
            ':driver'      => $connector->getDriver(),
            ':environment' => $this->getEnvironmentForServer($server),
            ':connector'   => $connector->getDisplayName(),
        ]));

        // Create a temporary dump filename
        $file = $this->addDumpFile($connector->getName(), $connector->getDatabase() . '.' . $connector->getType() . '.gz');

        // Execute the dump on the specified server
        $this->executeHook('pre-dump-connector')
             ->getPhoCommand($server)
             ->setPhoCommands(['databases', 'export'])
             ->addArguments([
                 '--connector',
                 $connector->getName(),
                 '--database',
                 $connector->getDatabase(),
                 '--file',
                 $this->source_temp_path . $file,
                 '--gzip',
             ])
             ->executeReturnString();

        return $this->executeHook('post-dump-connector');
    }


    /**
     * Adds the specified file to the list of files to sync
     *
     * @param string $connector_name
     * @param string $file
     *
     * @return string
     */
    protected function addDumpFile(string $connector_name, string $file): string
    {
        $this->dump_files[$connector_name] = $file;

        return $file;
    }


    /**
     * Lock all connectors in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function lock(?ServerInterface $server): static
    {
        Log::action(tr('Locking environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        return $this->executeHook('pre-lock')
                    ->lockSystem($server)
                    ->lockConnectors($server)
                    ->executeHook('post-lock');
    }


    /**
     * Lock all connectors in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function lockConnectors(?ServerInterface $server): static
    {
        Log::action(tr('Locking connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        return $this->executeHook('pre-lock-connectors')
                    ->lockSql($server)
                    ->lockRedis($server)
                    ->lockMongoDb($server)
                    ->executeHook('post-lock-connectors');
    }


    /**
     * Lock MongoDb connectors in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function lockMongoDb(?ServerInterface $server): static
    {
        Log::action(tr('Locking MongoDB connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-lock-mongodb');

        return $this->executeHook('post-lock-mongodb');
    }


    /**
     * Lock specified SQL connector in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function lockRedis(?ServerInterface $server): static
    {
        Log::action(tr('Locking Redis connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-lock-redis');

        return $this->executeHook('post-lock-redis');
    }


    /**
     * Lock specified SQL connector in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function lockSql(?ServerInterface $server): static
    {
        Log::action(tr('Locking SQL connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-lock-sql');

        return $this->executeHook('post-lock-sql');
    }


    /**
     * Lock site and connector in readonly for this projects so that we can dump them
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function lockSystem(?ServerInterface $server): static
    {
        Log::action(tr('Locking system for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-lock-system')
             ->getPhoCommand($server)
             ->setPhoCommands(['system', 'modes', 'readonly', 'enable'])
             ->executeReturnString();

        return $this->executeHook('post-lock-system');
    }


    /**
     * Scans the remote environment
     *
     * @param ServerInterface $server
     *
     * @return static
     */
    public function scan(ServerInterface $server): static
    {
        Log::action(tr('Scanning project installation on server ":server"', [
            ':server' => $this->server->getDisplayName(),
        ]));

        $this->executeHook('pre-scan');

        $result = Process::new('ls')
                         ->setServer($server)
                         ->setSudo($this->configuration['sudo'])
                         ->addArgument(Strings::slash($this->configuration['path']))
                         ->executeReturnString();

        if ($result) {
            $result = Process::new('ls')
                             ->setServer($server)
                             ->setSudo($this->configuration['sudo'])
                             ->addArgument(Strings::slash($this->configuration['path']) . 'pho')
                             ->executeReturnString();

            if ($result) {
                $this->environment_state = true;

                Log::success(tr('Target environment ":environment" path ":path" is fully available', [
                    ':environment' => $this->environment,
                    ':path'        => $this->configuration['path'],
                ]));

            } else {
                // The main project directory exists, but the ./pho command does not
                $this->environment_state = 'partial';

                Log::warning(tr('Target environment ":environment" path ":path" is partially available', [
                    ':environment' => $this->environment,
                    ':path'        => $this->configuration['path'],
                ]));
            }

        } else {
            // This project doesn't exist yet
            $this->environment_state = false;

            Log::warning(tr('Target environment ":environment" path ":path" is not available', [
                ':environment' => $this->environment,
                ':path'        => $this->configuration['path'],
            ]));
        }

        return $this->initTemporaryPath($server)
                    ->executeHook('post-scan');
    }


    /**
     * Initialized the temporary path for the specified server
     *
     * @param ServerInterface|null $server
     *
     * @return $this
     */
    protected function initTemporaryPath(?ServerInterface $server): static
    {
        $path = $this->getPhoCommand($server)
                     ->setPhoCommands(['system', 'temporary', 'get'])
                     ->addArguments('-Q')
                     ->executeReturnArray();

        $this->source_temp_path = Strings::slash(Arrays::firstValue($path));

        return $this;
    }


    /**
     * Initializes the configuration
     *
     * @param string $environment
     *
     * @return static
     */
    protected function initConfiguration(string $environment): static
    {
        Log::action(tr('Reading configuration for environment ":environment"', [
            ':environment' => $environment,
        ]));

        try {
            $this->environment   = $environment;
            $this->configuration = Config::forSection('deploy', $environment)->get();

            // Return Config to the default section
            Config::setSection('', ENVIRONMENT);

        } catch (ConfigFileDoesNotExistsException $e) {
            // Return Config to the default section
            Config::setSection('', ENVIRONMENT);

            throw SyncEnvironmentDoesNotExistsException::new(tr('The specified target environment ":environment" does not exist', [
                ':environment' => $environment,
            ]), $e)->makeWarning();
        }

        Arrays::ensure($this->configuration, 'server,ssh_accounts_name,path,user,group');
        Arrays::default($this->configuration, 'sudo', false);
        Arrays::default($this->configuration, 'rsync_parallel', false);

        // Check path configuration
        $this->configuration['path'] = trim((string) $this->configuration['path']);

        if (!$this->configuration['path']) {
            throw SyncConfigurationException::new(tr('The specified target environment ":environment" has no project path specified', [
                ':environment' => $environment,
            ]))->makeWarning();
        }

        $this->configuration['path'] = Strings::slash($this->configuration['path']);

        // Parse sudo configuration
        if ($this->configuration['sudo']) {
            if ($this->configuration['sudo'] === true) {
                $this->configuration['sudo'] = 'sudo -Eu root';

            } else {
                $this->configuration['sudo'] = trim($this->configuration['sudo']);

                if (!str_starts_with($this->configuration['sudo'], 'sudo')) {
                    $this->configuration['sudo'] = 'sudo -Eu ' . $this->configuration['sudo'];
                }
            }

            $this->configuration['sudo'] .= ' ';

        } else {
            $this->configuration['sudo'] = null;
        }

        if (!$this->configuration['server']) {
            // The target environment has no server configured
            throw SyncException::new(tr('The environment ":environment" has no server configured', [
                ':environment' => $environment,
            ]))->makeWarning();
        }

        // Setup remote server, MUST be from configuration!
        try {
            $server       = Config::get('servers.' . Config::escape($this->configuration['server']));
            $this->server = Server::newFromSource($server);

        } catch (ConfigPathDoesNotExistsException) {
            throw SyncException::new(tr('The configured server ":server" for environment ":environment" does not exist', [
                ':environment' => $environment,
                ':server'      => $this->configuration['server']
            ]))->makeWarning();
        }

        // Setup SSH account
        try {
            $account = null;

            if ($this->configuration['ssh_accounts_name']) {
                // Ignore the default SSH account for this server, use the one from configuration
                $account = Config::get('ssh.accounts.' . $this->configuration['ssh_accounts_name']);
                $this->server->setSshAccount($account);
            }

        } catch (ConfigPathDoesNotExistsException) {
            throw SyncException::new(tr('The configured SSH account ":account" for environment ":environment" does not exist', [
                ':environment' => $environment,
                ':account'     => $this->configuration['ssh_accounts_name']
            ]))->makeWarning();
        }

        // Does this server have an SSH account after all this?
        if (!$this->server->getSshAccount()) {
            // The server has no SSH account configured, and no SSH account was configured
            throw SyncException::new(tr('Cannot sync with server ":server" for environment ":environment", server has no SSH account configured and no SSH account was specified', [
                ':environment' => $environment,
                ':server'      => $this->server->getId(),
            ]))->makeWarning();
        }

        return $this;
    }


    /**
     * Sync from this environment to the specified environment
     *
     * @param string $environment
     *
     * @return static
     */
    public function to(string $environment): static
    {
        return $this->initConfiguration($environment)
                    ->scan($this->server)
                    ->lock(null)
                    ->dumpAllDatabases(null)
                    ->unlock(null)
                    ->copyDumps(null, $this->server)
                    ->copyContent(null, $this->server)
                    ->cleanTemporary(null)
                    ->importAllConnectors($this->server)
                    ->init($this->server)
                    ->clearCaches($this->server);
    }


    /**
     * Unlock Redis connectors for read/write for normal use
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function unlockRedis(?ServerInterface $server): static
    {
        Log::action(tr('Unlocking Redis connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-unlock-redis');

        return $this->executeHook('post-unlock-redis');
    }


    /**
     * Unlock MongoDb connectors for read/write for normal use
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function unlockMongoDb(?ServerInterface $server): static
    {
        Log::action(tr('Unlocking MongoDB connectors for environment ":environment"', [
            ':environment' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-unlock-mongodb');

        return $this->executeHook('post-unlock-mongodb');
    }


    /**
     * Imports the SQL connectors for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function importSql(?ServerInterface $server): static
    {
        Log::action(tr('Importing SQL dumps on environment ":server"', [
            ':server' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-import-sql');

        return $this->executeHook('post-import-sql');
    }


    /**
     * Imports the Mongo connectors for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function importMongoDb(?ServerInterface $server): static
    {
        Log::action(tr('Importing MongoDB dumps on environment ":server"', [
            ':server' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-import-mongodb');

        return $this->executeHook('post-import-mongodb');
    }


    /**
     * Imports the Redis connectors for this project
     *
     * @param ServerInterface|null $server
     *
     * @return static
     */
    protected function importRedis(?ServerInterface $server): static
    {
        Log::action(tr('Importing Redis dumps on environment ":server"', [
            ':server' => $this->getEnvironmentForServer($server),
        ]));

        $this->executeHook('pre-import-redis');

        return $this->executeHook('post-import-redis');
    }
}
