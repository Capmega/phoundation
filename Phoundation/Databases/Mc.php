<?php

namespace Phoundation\Databases;

use Phoundation\Core\Config;
use Phoundation\Databases\Exception\MemcachedException;

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
     * Singleton variable
     *
     * @var ?Mc $instance
     */
    protected static ?Mc $instance = null;

    /**
     * Instances store
     *
     * @var array $instances
     */
    protected static array $instances = [];

    /**
     * Connections store
     *
     * @var array $connections
     */
    protected static array $connections = [];



    /**
     * Initialize the class object through the constructor.
     *
     * MC constructor.
     */
    protected function __construct()
    {
        $this->connections = Config::get('memcached.connections');
    }


    /**
     * Singleton, ensure to always return the same Mc object.
     *
     * @param string|null $instance_name
     * @return Mc
     */
    public static function getInstance(?string $instance_name = null): Mc
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }


    /**
     * Returns a Mc object for the specified database / server
     *
     * In case another than the core database and server is needed
     *
     * @param string $database_name
     * @return Mc
     */
    public static function database(string $database_name): Mc
    {
        if (!array_key_exists($database_name, self::$databases)) {
            throw new MemcachedException('The specified Mongo database ":db" does not exist', [':db' => $database_name]);
        }

        return self::$databases[$database_name];
    }



    /**
     * Return the configured Mc connections
     *
     * @return array
     */
    public static function getConnections(): array
    {
        return self::$connections;
    }



    /**
     * Set the configured Mc connections
     *
     * @note This method will reset the currently existing connections
     * @param array $connections
     * @return void
     */
    public static function setConnections(array $connections): void
    {
        self::$connections = [];
        self::addConnections($connections);
    }



    /**
     * Add the multiple specified connections
     *
     * @param array $connections
     * @return void
     */
    public static function addConnections(array $connections): void
    {
        foreach ($connections as $connection => $configuration) {
            self::addConnection($connection, $configuration);
        }
    }



    /**
     * Add the specified connection
     *
     * @param string $connection_name
     * @param array $configuration
     * @return void
     */
    public static function addConnection(string $connection_name, array $configuration): void
    {
        self::$connections[$connection_name] = $configuration;
    }



    /**
     * Remove the connection with the specified connection name
     *
     * @param string $connection_name
     * @return void
     */
    public static function removeConnections(string $connection_name): void
    {
        unset(self::$connections[$connection_name]);
    }

















    /*
         * Initialize the library
         * Automatically executed by libs_load()
         */
    public function  __constructor()
    {
        try {
            if (!class_exists('Memcached')) {
                throw new MemcachedException(tr('memcached_library_init(): php module "memcached" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php5-memcached; sudo php5enmod memcached" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'), 'not_available');
            }

        } catch (Exception $e) {
            throw new MemcachedException('memcached_library_init(): failed', $e);
        }
    }


    /*
     * Connect to the memcached server
     */
    public function connect()
    {
        global $_CONFIG, $core;

        try {
            if (empty($core->register['memcached'])) {
                /*
                 * Memcached disabled?
                 */
                if (!$_CONFIG['memcached']) {
                    $core->register['memcached'] = false;
                    log_file('memcached_connect(): Not using memcached, its disabled by configuration $_CONFIG[memcached]', 'yellow');

                } else {
                    $failed = 0;
                    $core->register['memcached'] = new Memcached;

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

        } catch (Exception $e) {
            throw new MemcachedException('memcached_connect(): failed', $e);
        }
    }


    /*
     *
     */
    public function put($value, $key, $namespace = null, $expiration_time = null)
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

            $core->register['memcached']->set($_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key, $value, $expiration_time);
            log_console(tr('memcached_put(): Wrote key ":key"', array(':key' => $_CONFIG['memcached']['prefix'] . memcached_namespace($namespace) . $key)), 'VERYVERBOSE/green');

            return $value;

        } catch (Exception $e) {
            throw new MemcachedException('memcached_put(): failed', $e);
        }
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
}