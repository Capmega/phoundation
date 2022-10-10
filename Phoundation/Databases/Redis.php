<?php

namespace Phoundation\Databases;

use Phoundation\Core\Config;
use Phoundation\Databases\Exception\RedisException;

/**
 * Class Redis
 *
 * This is the default Redis object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Redis
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
        $this->connections = Config::get('redis.connections');
    }



    /**
     * Returns a Redis object for the specified database / server
     *
     * In case another than the core database and server is needed
     *
     * @param string $database_name
     * @return Redis
     * @throws RedisException
     */
    public static function db(string $database_name): Redis
    {
        if (!array_key_exists($database_name, self::$databases)) {
            throw new RedisException('The specified Redis database ":db" does not exist', [':db' => $database_name]);
        }

        return self::$databases[$database_name];
    }



    /**
     * Wrapper to Redis::db()
     *
     * @see Redis::db()
     * @param string|null $instance_name
     * @return Redis
     */
    public static function database(?string $instance_name = null): Redis
    {
        return self::db($instance_name);
    }



    /**
     * Return the configured Redis connections
     *
     * @return array
     */
    public function getConnections(): array
    {
        return self::$connections;
    }



    /**
     * Set the configured Redis connections
     *
     * @note This method will reset the currently existing connections
     * @param array $connections
     * @return void
     */
    public function setConnections(array $connections): void
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
    public function addConnections(array $connections): void
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
    public function addConnection(string $connection_name, array $configuration): void
    {
        self::$connections[$connection_name] = $configuration;
    }



    /**
     * Remove the connection with the specified connection name
     *
     * @param string $connection_name
     * @return void
     */
    public function removeConnections(string $connection_name): void
    {
        unset(self::$connections[$connection_name]);
    }



}