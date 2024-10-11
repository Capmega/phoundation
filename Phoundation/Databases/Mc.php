<?php

/**
 * Class Mc
 *
 * This is the default MemCached object
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases;

use Memcached;
use Phoundation\Core\Log\Log;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Interfaces\DatastoreInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Utils\Exception\ConfigurationInvalidException;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Throwable;


class Mc implements DatastoreInterface
{
    /**
     * PHP Memcached drivers
     *
     * @var Memcached|null $memcached
     */
    protected ?Memcached $memcached = null;

    /**
     * Memcached instance name
     *
     * @var string|null $instance_name
     */
    protected ?string $instance_name = null;

    /**
     * Memcached configuration
     *
     * @var array|null $configuration
     */
    protected ?array $configuration = null;

    /**
     * Actove memcached connections for this instance
     *
     * @var array $connections
     */
    protected array $connections = [];

    /**
     *
     * @var MemcachedNamespace|null $namespace
     */
    protected ?MemcachedNamespace $namespace = null;


    /**
     * Initialize the class object through the constructor.
     *
     * MC constructor.
     *
     * @note Instance always defaults to "system" if not specified
     *
     * @param ConnectorInterface|string|null $connector
     */
    public function __construct(ConnectorInterface|string|null $connector = null)
    {
        if (!class_exists('Memcached')) {
            throw new PhpModuleNotAvailableException(tr('The PHP module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php-memcached; sudo phpenmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        // Get the configuration for the specified instance. Always default to "system"
        if (!$connector) {
            $connector = 'mc-system';
        }

        // Get instance information and connect to memcached servers
        $this->instance_name = $connector;
        $this->memcached     = new Memcached();
        $this->namespace     = new MemcachedNamespace($this);

        try {
            $this->readConfiguration();
            $this->setConnections($this->configuration['connections']);
            $this->connect();

        } catch (ConfigPathDoesNotExistsException) {
            Log::warning(tr('Disabling memcached connector ":connector", no configuration found', [
                ':connector' => $connector,
            ]), 3);
        }
    }


    /**
     * Read the configuration for this instance
     *
     * @return void
     */
    protected function readConfiguration(): void
    {
        // Read the configuration
        $this->configuration = Config::getArray('databases.connectors.' . $this->instance_name);

        // Ensure that all required keys are available
        Arrays::ensure($this->configuration, 'connections');
        Arrays::default($this->configuration, 'expires', 86400);
        Arrays::default($this->configuration, 'prefix', gethostname());

        // Default connections to localhost if nothing was defined
        if (empty($this->configuration['connections'])) {
            throw ConfigPathDoesNotExistsException::new(tr('No memcached connections configured for instance ":instance"', [
                ':instance' => $this->instance_name,
            ]))->makeWarning();
        }

        if (!is_array($this->configuration['connections'])) {
            throw new OutOfBoundsException(tr('Invalid memcached connections configured for instance ":instance", it should be an array but is an ":type"', [
                ':instance' => $this->instance_name,
                ':type'     => gettype($this->configuration['connections']),
            ]));
        }

        // Ensure all connections are valid
        foreach ($this->configuration['connections'] as $weight => &$connection) {
            if (!is_array($connection)) {
                if ($connection) {
                    throw new ConfigurationInvalidException(tr('Configuration path ":path" contains invalid information', [
                        ':path' => 'databases.connectors.' . $this->instance_name . '.connections.' . $weight,
                    ]));
                }

                // Empty connector information, default to empty array and fill up below
                $connection = [];
            }

            Arrays::default($connection, 'host', '127.0.0.1');
            Arrays::default($connection, 'port', 11211);
        }
    }


    /**
     * Connect to the memcached servers
     *
     * @return void
     */
    protected function connect(): void
    {
        if (empty($this->memcached)) {
            // This is wonky, how did we get here without memcached being loaded?
            throw new OutOfBoundsException(tr('Cannot connect to memcached servers, memcached driver has not been loaded'));
        }

        // Memcached disabled?
        if (!Config::get('databases.memcached.enabled', true)) {
            Log::warning('Not using memcached, its disabled by configuration "memcached.enabled"', 4);

        } else {
            $failed = 0;

            // Connect to all memcached servers, but only if no servers were added yet (this should normally be the case)
            foreach ($this->configuration['connections'] as $weight => $connection) {
                try {
                    $this->memcached->addServer($connection['host'], $connection['port'], $weight);
                    $this->connections[] = $connection;

                } catch (Throwable $e) {
                    Log::warning(tr('Failed to connect to memcached server ":host::port" in configuration directory ":directory"', [
                        ':host'      => $connection['host'],
                        ':port'      => $connection['port'],
                        ':directory' => 'databases.connectors.' . $this->instance_name . '.connections.' . $weight,
                    ]));
                    Log::error($e);
                    $failed++;
                }
            }

            if ($failed) {
                if (!$this->memcached->getServerList()) {
                    // We haven't been able to connect to any memcached server at all!
                    Log::warning(tr('Failed to connect to any memcached server'), 10);
                    Notification::new()
                                ->setUrl('developer/incidents.html')
                                ->setMode(EnumDisplayMode::warning)
                                ->setCode('not-available')
                                ->addRole('developer')
                                ->setTitle(tr('Memcached server not available'))
                                ->setMessage(tr('Failed to connect to all ":count" memcached servers', [':server' => count($this->configuration['connections'])]))
                                ->send();
                }
            }
        }
    }


    /**
     * Return the data for the specified key (and optionally namespace)
     *
     * @param string      $key
     * @param string|null $namespace
     *
     * @return mixed|void
     */
    public function get(string $key, ?string $namespace = null)
    {
        if (!$this->connections) {
            return;
        }

        $data = $this->memcached->get($this->namespace->getKey($key, $namespace));

        if ($data) {
            Log::success(tr('Returned data for key ":key"', [':key' => $this->namespace->getKey($key, $namespace)]), 3);

        } else {
            Log::success(tr('Found no data for key ":key"', [':key' => $this->namespace->getKey($key, $namespace)]), 3);
        }

        return $data;
    }


    /**
     * Return the active Mc connections
     *
     * @return array
     */
    public function getActiveConnections(): array
    {
        return $this->connections;
    }


    /**
     * Return the configured Mc connections
     *
     * @return array
     */
    public function getConnections(): array
    {
        return $this->configuration['connections'];
    }


    /**
     * Set the configured Mc connections
     *
     * @note This method will reset the currently existing connections
     *
     * @param array $connections
     *
     * @return static
     */
    public function setConnections(array $connections): static
    {
        $this->configuration['connections'] = [];

        return $this->addConnections($connections);
    }


    /**
     * Clear the configured Mc connections
     *
     * @return static
     */
    public function clearConnections(): static
    {
        $this->configuration['connections'] = [];

        return $this;
    }


    /**
     * Add the multiple specified connections
     *
     * @param array $connections
     *
     * @return static
     */
    public function addConnections(array $connections): static
    {
        foreach ($connections as $connection => $configuration) {
            $this->addConnection($connection, $configuration);
        }

        return $this;
    }


    /**
     * Add the specified connection
     *
     * @param string|int $connection_name
     * @param array      $configuration
     *
     * @return static
     */
    public function addConnection(string|int $connection_name, array $configuration): static
    {
        $this->configuration['connections'][$connection_name] = $configuration;

        return $this;
    }


    /**
     * Return configuration data.
     *
     * If the $key is specified, only the configuration data for that specified key will be returned
     *
     * @param string|null $key
     *
     * @return string|array
     */
    public function getConfiguration(?string $key = null): string|array
    {
        if ($key) {
            return isset_get($this->configuration[$key]);
        }

        return $this->configuration;
    }


    /**
     * Set the specified data to the specified key (and optionally the specified namespace)
     *
     * @param mixed       $value
     * @param string      $key
     * @param string|null $namespace
     * @param int|null    $expires
     *
     * @return mixed
     */
    public function set(mixed $value, string $key, ?string $namespace = null, ?int $expires = null)
    {
        $key     = $this->namespace->getKey($key);
        $expires = $expires ?? $this->configuration['expires'];

        $this->memcached->set($key, $value, $expires);

        Log::success(tr('Wrote ":bytes" bytes for key ":key"', [
            ':key'   => $key,
            ':bytes' => (is_scalar($value) ? strlen((string) $value) : count($value)),
        ]), 3);

        return $value;
    }


    /**
     *
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param string|null           $namespace
     * @param int|null              $expires
     *
     * @return false|mixed
     */
    public function add(mixed $value, string|float|int|null $key, ?string $namespace = null, ?int $expires = null): mixed
    {
        if (!$this->connections) {
            return $value;
        }

        $key     = $this->namespace()->getKey($key, $namespace);
        $expires = $expires ?? $this->configuration['expires'];

        if (!$this->memcached->add($value, $key, $expires)) {
            Log::warning(tr('Failed to add ":bytes" bytes value to key ":key"', [
                ':key'   => $key,
                ':bytes' => strlen($value),
            ]));
        }

        return $value;
    }


    /**
     * Return the memcached namespace object
     *
     * @return MemcachedNamespace
     */
    public function namespace(): MemcachedNamespace
    {
        return $this->namespace;
    }


    /**
     *
     *
     * @param mixed       $value
     * @param string      $key
     * @param string|null $namespace
     * @param int|null    $expires
     *
     * @return false|mixed
     */
    public function replace(mixed $value, string $key, ?string $namespace = null, ?int $expires = null): mixed
    {
        if (!$this->connections) {
            return $value;
        }

        $key     = $this->namespace()->getKey($key, $namespace);
        $expires = $expires ?? $this->configuration['expires'];

        if (!$this->memcached->replace($key, $value, $expires)) {
            Log::warning(tr('Failed to replace key ":key" with ":bytes" bytes value', [
                ':key'   => $key,
                ':bytes' => strlen($value),
            ]));
        }

        return $value;
    }


    /**
     * Delete the specified key or namespace
     *
     * @param string      $key
     * @param string|null $namespace
     *
     * @return void
     */
    public function delete(string $key, ?string $namespace = null): void
    {
        if (!$this->connections) {
            return;
        }

        if (!$key) {
            if (!$namespace) {
                // Delete what, exactly?
                throw new OutOfBoundsException('Cannot delete memcached entry, no key specified');
            }

            // Delete the entire namespace
            $this->namespace()->delete($namespace);

        } else {
            // Delete the key only
            $this->memcached->delete($this->namespace->getKey($key));
        }
    }


    /**
     * Clear the entire memcache
     *
     * @param int $delay
     */
    public function clear(int $delay = 0): void
    {
        if (!$this->connections) {
            return;
        }
        $this->memcached->flush($delay);
    }


    /**
     * Flush all cached memcache data
     *
     * @param int $delay
     *
     * @return static
     */
    public function flush(int $delay = 0): static
    {
        $this->memcached->flush($delay);

        return $this;
    }


    /**
     * Increment the value of the specified key
     *
     * @param string      $key
     * @param string|null $namespace
     *
     * @return void
     */
    public function increment(string $key, ?string $namespace = null): void
    {
        if (!$this->connections) {
            return;
        }

        $this->memcached->increment($this->namespace->getKey($key, $namespace));
    }


    /**
     * Return statistics for this memcached instance
     *
     * @return array
     */
    public function stats(): array
    {
        $stats = $this->memcached->getStats();

        if (!$stats) {
            // No stats data available, but this might be FALSE, return [] to ensure proper datatype for return
            return [];
        }

        return $stats;
    }


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static
    {
        throw new UnderConstructionException();

        return $this;
    }
}
