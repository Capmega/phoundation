<?php

namespace Phoundation\Databases;

use Memcached;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Databases\Exception\MemcachedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;


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
     * Initialize the class object through the constructor.
     *
     * MC constructor.
     */
    protected function __construct(?string $instance_name = null)
    {
        if (!class_exists('Memcached')) {
            throw new PhpModuleNotAvailableException(tr('The PHP module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php5-memcached; sudo php5enmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        // Get the configuration for the specified instance
        if (!$instance_name) {
            $instance_name = 'core';
        }

        // Get instance information and connect to memcached servers
        $this->instance_name = $instance_name;
        $this->readConfiguration();
        $this->setConnections($this->configuration['connectors']);
        $this->connect();
    }



    /**
     * Quick access to Mc instances
     *
     * @param string|null $instance_name
     * @return Mc
     */
    public static function database(?string $instance_name = null): Mc
    {
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



    /*
     *
     */
    public function set(mixed $value, string $key, ?string $namespace = null, ?int $expires = null)
    {
        $key = $this->buildKey($key, $namespace);
        $expires = $expires ?? $this->configuration['expires'];

        $this->memcached->set($this->configuration['prefix'] . $key, $value, $expires);
        Log::success(tr('Wrote key ":key"', [':key' => $key]), 3);

        return $value;
    }


    /*
     *
     */
    public function add($value, $key, $namespace = null, $expiration_time = null)
    {
        global $_CONFIG, $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            if ($namespace) {
                $namespace = memcached_namespace($namespace) . '_';
            }

            if ($expiration_time === null) {
                /*
                 * Use default cache expire time
                 */
                $expiration_time = $_CONFIG['memcached']['expire_time'];
            }

            if (!$core->register['memcached']->add($_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key, $value, $expiration_time)) {
                // :TODO: Exception?
            }

            log_console(tr('memcached_add(): Added key ":key"', array(':key' => $_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key)), 'VERYVERBOSE/green');
            return $value;

        } catch (Exception $e) {
            throw new MemcachedException('memcached_add(): failed', $e);
        }
    }


    /*
     *
     */
    public function replace($value, $key, $namespace = null, $expiration_time = null)
    {
        global $_CONFIG, $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            if ($namespace) {
                $namespace = memcached_namespace($namespace) . '_';
            }

            if ($expiration_time === null) {
                /*
                 * Use default cache expire time
                 */
                $expiration_time = $_CONFIG['memcached']['expire_time'];
            }

            if (!$core->register['memcached']->replace($_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key, $value, $expiration_time)) {

            }

            return $value;

        } catch (Exception $e) {
            throw new MemcachedException('memcached_replace(): failed', $e);
        }
    }


    /*
     *
     */
    public function get($key, $namespace = null)
    {
        global $_CONFIG, $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            $data = $core->register['memcached']->get($_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key);

            if ($data) {
                log_console(tr('memcached_get(): Returned data for key ":key"', array(':key' => $_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key)), 'VERYVERBOSE/green');

            } else {
                log_console(tr('memcached_get(): Found no data for key ":key"', array(':key' => $_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key)), 'VERYVERBOSE/green');
            }

            return $data;

        } catch (Exception $e) {
            throw new MemcachedException('memcached_get(): Failed', $e);
        }
    }


    /*
     * Delete the specified key or namespace
     */
    public function delete($key, $namespace = null)
    {
        global $_CONFIG, $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            if (!$key) {
                if (!$namespace) {

                }

                /*
                 * Delete the entire namespace
                 */
                return memcached_namespace($namespace, true);
            }

            return $core->register['memcached']->delete($_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key);

        } catch (Exception $e) {
            throw new MemcachedException('memcached_delete(): Failed', $e);
        }
    }


    /*
     * clear the entire memcache
     */
    public function clear($delay = 0)
    {
        global $_CONFIG, $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            $core->register['memcached']->flush($delay);

        } catch (Exception $e) {
            throw new MemcachedException('memcached_clear(): Failed', $e);
        }
    }


    /*
     * Increment the value of the specified key
     */
    public function increment($key, $namespace = null)
    {
        global $_CONFIG, $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            $core->register['memcached']->increment($_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key);

        } catch (Exception $e) {
            throw new MemcachedException('memcached_increment(): Failed', $e);
        }
    }


    /*
     * Return a key for the namespace. We don't use the namespace itself as part of the key because
     * with an alternate key, its very easy to invalidate namespace keys by simply assigning a new
     * value to the namespace key
     */
    public function namespace($namespace, $delete = false)
    {
        global $_CONFIG;
        static $keys = array();

        try {
            if (!$namespace or !$_CONFIG['memcached']['namespaces']) {
                return '';
            }

            if (array_key_exists($namespace, $keys)) {
                return $keys[$namespace];
            }

            $key = memcached_get('ns:' . $namespace);

            if (!$key) {
                $key = (string)microtime(true);
                memcached_add($key, 'ns:' . $namespace);

            } elseif ($delete) {
                /*
                 * "Delete" the key by incrementing (and so, changing) the value of the namespace key.
                 * Since this will change the name of all keys using this namespace, they are no longer
                 * accessible and with time will be dumped automatically by memcached to make space for
                 * newer keys.
                 */
                try {
                    memcached_increment($namespace);
                    $key = memcached_get('ns:' . $namespace);

                } catch (Exception $e) {
                    /*
                     * Increment failed, so in all probability the key did not exist. It could have been
                     * deleted by a parrallel process, for example
                     */
                    switch ($e->getCode()) {
                        case '':
                            // :TODO: Implement correctly. For now, just notify
                        default:
                            notify($e);
                    }
                }
            }

            $keys[$namespace] = $key;
            return $key;

        } catch (Exception $e) {
            throw new MemcachedException('memcached_namespace(): Failed', $e);
        }
    }


    /*
     * Return statistics for memcached
     */
    public function stats()
    {
        global $core;

        try {
            if (!memcached_connect()) {
                return false;
            }

            if (empty($core->register['memcached'])) {
                /*
                 * Not connected to a memcached server!
                 */
                return null;
            }

            return $core->register['memcached']->getStats();

        } catch (Exception $e) {
            throw new MemcachedException('memcached_stats(): Failed', $e);
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
        $this->configuration = Config::get('memcached.instances.' . $this->instance_name);

        // Ensure that all required keys are available
        Arrays::ensure($this->configuration, 'connections');
        Arrays::default($this->configuration['expires'], 86400);
        Arrays::default($this->configuration['prefix'], gethostname());

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
        foreach ($this->configuration['connnections'] as &$connection) {
            Arrays::ensure($connection, 'host,port');
        }
    }



    /**
     * Connect to the memcached servers
     */
    protected static  function connect()
    {
        if (empty($core->register['memcached'])) {
            // Memcached disabled?
            if (!Config::get('memcached.enabled', true)) {
                Log::warning('Not using memcached, its disabled by configuration "memcached.enabled"');

            } else {
                $failed = 0;

                /*
                 * Connect to all memcached servers, but only if no servers were added yet
                 * (this should normally be the case)
                 */
                if (!$core->register['memcached']->getServerList()) {
                    $core->register['memcached']->addServers($_CONFIG['memcached']['servers']);
                }

                /*
                 * Check connection status of memcached servers
                 * (To avoid memcached servers being down and nobody knows about it)
                 */
                //:TODO: Maybe we should check this just once every 10 connects or so? is it really needed?
                try {
                    foreach ($core->register['memcached']->getStats() as $server => $server_data) {
                        if ($server_data['pid'] < 0) {
                            /*
                             * Could not connect to this memcached server. Notify, and remove from the connections list
                             */
                            $failed++;

                            notify(array('code' => 'warning/not-available',
                                'groups' => 'developers',
                                'title' => tr('Memcached server not available'),
                                'message' => tr('memcached_connect(): Failed to connect to memcached server ":server"', array(':server' => $server))));
                        }
                    }

                } catch (Exception $e) {
                    /*
                     * Server status check failed, I think its safe
                     * to assume that no memcached server is working.
                     * Fake "all severs failed" so that memcached won't
                     * be used
                     */
                    $failed = count($_CONFIG['memcached']['servers']);
                }

                if ($failed >= count($_CONFIG['memcached']['servers'])) {
                    /*
                     * All memcached servers failed to connect!
                     * Send error notification
                     */
                    notify(array('code' => 'not-available',
                        'groups' => 'developers',
                        'title' => tr('Memcached server not available'),
                        'message' => tr('memcached_connect(): Failed to connect to all ":count" memcached servers', array(':server' => count($_CONFIG['memcached']['servers'])))));

                    return false;
                }
            }
        }

        return $core->register['memcached'];
    }



    /**
     * Build the Mc key from the specified key and namespace
     *
     * @todo Add support for namespaces
     * @param string $key
     * @param string $namespace
     * @return string
     */
    protected function buildKey(string $key, string $namespace): string
    {
        return $key;
    }
}