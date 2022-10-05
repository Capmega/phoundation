<?php

namespace Phoundation\Databases;

use Memcached;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Notify\Notification;
use Throwable;



/**
 * Class Mc
 *
 * This is the default MemCached object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Mc
{
    /**
     * Identifier of this instance
     *
     * @var string|null $instance_name
     */
    protected ?string $instance_name = null;

    /**
     * Instances store
     *
     * @var array $instances
     */
    protected static array $instances = [];

    /**
     * PHP Memcached drivers
     *
     * @var Memcached| null $memcached
     */
    protected ?Memcached $memcached = null;

    /**
     * Memcached configuration
     *
     * @var array $configuration
     */
    protected array $configuration = [];

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
     * @param string|null $instance_name
     */
    protected function __construct(?string $instance_name = null)
    {
        if (!class_exists('Memcached')) {
            throw new PhpModuleNotAvailableException(tr('The PHP module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php5-memcached; sudo php5enmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        // Get the configuration for the specified instance. Always default to "system"
        if (!$instance_name) {
            $instance_name = 'system';
        }

        // Get instance information and connect to memcached servers
        $this->instance_name = $instance_name;
        $this->namespace = new MemcachedNamespace($this);
        $this->readConfiguration();
        $this->setConnections($this->configuration['connectors']);
        $this->connect();
    }



    /**
     * Quick access to Mc instances. Defaults to "system" instance
     *
     * @param string|null $instance_name
     * @return Mc
     */
    public static function database(?string $instance_name = null): Mc
    {
        if (!$instance_name) {
            // Always default to system instance
            $instance_name = 'system';
        }

        if (!self::$instances[$instance_name]) {
            self::$instances[$instance_name] = new Mc($instance_name);
        }

        return self::$instances[$instance_name];
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
     * @param array $connections
     * @return void
     */
    public function setConnections(array $connections): void
    {
        $this->configuration['connections'] = [];
        $this->addConnections($connections);
    }



    /**
     * Add the multiple specified connections
     *
     * @param array $connections
     * @return void
     */
    public function addConnections(array $connections): void
    {
        foreach ($connections as $connection => $configuration) {
            $this->addConnection($connection, $configuration);
        }
    }



    /**
     * Add the specified connection
     *
     * @param string $connection_name
     * @param array $configuration
     * @return void
     */
    public function addConnection(string $connection_name, array $configuration): void
    {
        $this->configuration['connections'][$connection_name] = $configuration;
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
     * Return configuration data.
     *
     * If the $key is specified, only the configuration data for that specified key will be returned
     *
     * @param string|null $key
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
     * @param mixed $value
     * @param string $key
     * @param string|null $namespace
     * @param int|null $expires
     * @return mixed
     */
    public function set(mixed $value, string $key, ?string $namespace = null, ?int $expires = null)
    {
        $key = $this->namespace->getKey($key);
        $expires = $expires ?? $this->configuration['expires'];

        $this->memcached->set($key, $value, $expires);
        Log::success(tr('Wrote ":bytes" bytes for key ":key"', [':key' => $key, ':bytes' => strlen($value)]), 3);

        return $value;
    }



    /**
     *
     *
     * @param mixed $value
     * @param string $key
     * @param string|null $namespace
     * @param int|null $expires
     * @return false|mixed
     */
    public function add(mixed $value, string $key, ?string $namespace = null, ?int $expires = null): mixed
    {
        if (!$this->connections) {
            return $value;
        }

        $key = $this->namespace()->getKey($key, $namespace);
        $expires = $expires ?? $this->configuration['expires'];

        if (!$this->memcached->add($key, $value, $expires)) {
            Log::warning(tr('Failed to add ":bytes" bytes value to key ":key"', [':key' => $key, ':bytes' => strlen($value)]));
        }

        return $value;
    }



    /**
     *
     *
     * @param mixed $value
     * @param string $key
     * @param string|null $namespace
     * @param int|null $expires
     * @return false|mixed
     */
    public function replace(mixed $value, string $key, ?string $namespace = null, ?int $expires = null): mixed
    {
        if (!$this->connections) {
            return $value;
        }

        $key = $this->namespace()->getKey($key, $namespace);
        $expires = $expires ?? $this->configuration['expires'];

        if (!$this->memcached->replace($key, $value, $expires)) {
            Log::warning(tr('Failed to replace key ":key" with ":bytes" bytes value', [':key' => $key, ':bytes' => strlen($value)]));
        }

        return $value;
    }



    /**
     * Return the data for the specified key (and optionally namespace)
     *
     * @param string $key
     * @param string|null $namespace
     * @return mixed|void
     */
    public function get(string $key, ?string $namespace = null)
    {
        if (!$this->connections) {
            return ;
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
     * Delete the specified key or namespace
     *
     * @param string $key
     * @param string|null $namespace
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
            return ;
        }

        $this->memcached->flush($delay);
    }



    /**
     * Increment the value of the specified key
     *
     * @param string $key
     * @param string|null $namespace
     * @return void
     */
    public function increment(string $key, ?string $namespace = null): void
    {
        if (!$this->connections) {
            return ;
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

        if (!$stats){
            // No stats data available, but this might be FALSE, return [] to ensure proper datatype for return
            return [];
        }

        return $stats;
    }



    /**
     * Read the configuration for this instance
     *
     * @return void
     */
    protected function readConfiguration(): void
    {
        // Read the configuration
        $this->configuration = Config::get('memcached.instances.' . $this->instance_name);

        // Ensure that all required keys are available
        Arrays::ensure($this->configuration, 'connections');
        Arrays::default($this->configuration, 'expires', 86400);
        Arrays::default($this->configuration, 'prefix', gethostname());

        // Default connections to localhost if nothing was defined
        if (!$this->configuration['connnections']) {
            Log::warning(tr('No memcached connections configured for instance ":instance", defaulting to localhost::11211', [':instance' => $this->instance_name]));

            $this->configuration['connnections'] = [
                'host' => '127.0.0.1',
                'port' => '11211'
            ];
        }

        if (!is_array($this->configuration['connnections'])) {
            throw new OutOfBoundsException(tr('Invalid memcached connections configured for instance ":instance", it should be an array but is an ":type"', [':instance' => $this->instance_name, ':type' => gettype($this->configuration['connnections'])]));
        }

        // Ensure all connections are valid
        foreach ($this->configuration['servers'] as &$servers) {
            Arrays::ensure($servers, 'host,port,weight');
        }
    }



    /**
     * Connect to the memcached servers
     *
     * @return void
     */
    protected function connect(): void
    {
        if (!$this->memcached->getServerList()) {
            Log::warning('Not connecting to memcached servers again, this instance is already connected');
        }

        if (empty($this->memcached)) {
            // Memcached disabled?
            if (!Config::get('memcached.enabled', true)) {
                Log::warning('Not using memcached, its disabled by configuration "memcached.enabled"');

            } else {
                $failed = 0;

                // Connect to all memcached servers, but only if no servers were added yet (this should normally be the case)
                foreach ($this->configuration['servers'] as $server) {
                    try {
                        $this->memcached->addServer($server['host'], $server['port'], $server['weight']);
                        $this->connections[] = $server;
                    } catch (Throwable $e) {
                        Log::warning(tr('Failed to connect to memcached server ":host::port"', [':host' => $server['host'], 'port' => $server['port']]));
                        $failed++;
                    }
                }

                if ($failed) {
                    if (!$this->memcached->getServerList()) {
                        // We haven't been able to connect to any memcached server at all!
                        Log::warning(tr('Failed to connect to any memcached server'), 10);

                        Notification::getInstance()
                            ->setCode('not-available')
                            ->addGroup('developers')
                            ->setTitle(tr('Memcached server not available'))
                            ->setMessage(tr('Failed to connect to all ":count" memcached servers', [':server' => count($this->configuration['servers'])]))
                            ->send();
                    }
                }
            }
        }
    }
}