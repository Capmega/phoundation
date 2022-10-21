<?php

namespace Phoundation\Databases;

use Phoundation\Core\Config;
use Phoundation\Databases\Exception\MongoException;

/**
 * Class Mongo
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Mongo
{
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
    public function __construct()
    {
        $this->connections = Config::get('mongo.connections');
    }



    /**
     * Return the configured Mongo connections
     *
     * @return array
     */
    public static function getConnections(): array
    {
        return self::$connections;
    }



    /**
     * Set the configured Mongo connections
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
}