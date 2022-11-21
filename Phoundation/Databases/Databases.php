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
     * The register with all NullDb database instances
     *
     * @var array $null_db
     */
    protected static array $null_db = [];



    /**
     * Access SQL database instances
     *
     * @param string|null $instance
     * @param bool $use_database
     * @return Sql
     * @throws Exception
     */
    public static function Sql(?string $instance, bool $use_database = true): Sql
    {
        if (!$instance) {
            // Default to system instance
            $instance = 'system';
        }

        if (!array_key_exists($instance, self::$sql)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$sql[$instance] = new Sql($instance, $use_database);
        }

        return self::$sql[$instance];
    }



    /**
     * Access Memcached database instances
     *
     * @param string|null $instance
     * @return Mc
     * @throws Exception
     */
    public static function Mc(?string $instance): Mc
    {
        if (!$instance) {
            // Default to system instance
            $instance = 'system';
        }

        if (!array_key_exists($instance, self::$mc)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$mc[$instance] = new Mc($instance);
        }

        return self::$mc[$instance];
    }



    /**
     * Access Redis database instances
     *
     * @param string|null $instance
     * @return Redis
     * @throws Exception
     */
    public static function Redis(?string $instance): Redis
    {
        if (!$instance) {
            // Default to system instance
            $instance = 'system';
        }

        if (!array_key_exists($instance, self::$redis)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$redis[$instance] = new Redis($instance);
        }

        return self::$redis[$instance];
    }



    /**
     * Access Mongo database instances
     *
     * @param string|null $instance
     * @return Mongo
     * @throws Exception
     */
    public static function Mongo(?string $instance): Mongo
    {
        if (!$instance) {
            // Default to system instance
            $instance = 'system';
        }

        if (!array_key_exists($instance, self::$mongo)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$mongo[$instance] = new Mongo($instance);
        }

        return self::$mongo[$instance];
    }



    /**
     * Access NullDb database instances
     *
     * @param string|null $instance
     * @return NullDb
     * @throws Exception
     */
    public static function NullDb(?string $instance): NullDb
    {
        if (!$instance) {
            // Default to system instance
            $instance = 'system';
        }

        if (!array_key_exists($instance, self::$null_db)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            self::$null_db[$instance] = new NullDb($instance);
        }

        return self::$null_db[$instance];
    }
}