<?php

namespace Phoundation\Databases;

use Exception;
use Phoundation\Databases\Sql\Sql;




/**
 * Databases class
 *
 * This class is the quick access to all database instances, SQL or NoSQL alike
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Databases
{
    /**
     * The register with all SQL database instances
     *
     * @var array $sql
     */
    protected static array $sql = [];

    /**
     * The register with all Memcached instances
     *
     * @var array $mc
     */
    protected static array $mc = [];

    /**
     * The register with all Redis database instances
     *
     * @var array $redis
     */
    protected static array $redis = [];

    /**
     * The register with all Mongo database instances
     *
     * @var array $mongo
     */
    protected static array $mongo = [];


    /**
     * Access SQL database instances
     *
     * @param string|null $interface
     * @param bool $use_database
     * @return Sql
     * @throws Exception
     */
    public static function Sql(?string $interface, bool $use_database = true): Sql
    {
        if (!$interface) {
            // Default to system instance
            $interface = 'system';
        }

        if (!array_key_exists($interface, self::$sql)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$sql[$interface] = new Sql($interface, $use_database);
        }

        return self::$sql[$interface];
    }



    /**
     * Access Memcached database instances
     *
     * @param string|null $interface
     * @return Mc
     * @throws Exception
     */
    public static function Mc(?string $interface): Mc
    {
        if (!$interface) {
            // Default to system instance
            $interface = 'system';
        }

        if (!array_key_exists($interface, self::$mc)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$mc[$interface] = new Mc($interface);
        }

        return self::$mc[$interface];
    }



    /**
     * Access Redis database instances
     *
     * @param string|null $interface
     * @return Redis
     * @throws Exception
     */
    public static function Redis(?string $interface): Redis
    {
        if (!$interface) {
            // Default to system instance
            $interface = 'system';
        }

        if (!array_key_exists($interface, self::$redis)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$redis[$interface] = new Redis($interface);
        }

        return self::$redis[$interface];
    }



    /**
     * Access Mongo database instances
     *
     * @param string|null $interface
     * @return Mongo
     * @throws Exception
     */
    public static function Mongo(?string $interface): Mongo
    {
        if (!$interface) {
            // Default to system instance
            $interface = 'system';
        }

        if (!array_key_exists($interface, self::$mongo)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$mongo[$interface] = new Mongo($interface);
        }

        return self::$mongo[$interface];
    }
}