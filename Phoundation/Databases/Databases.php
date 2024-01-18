<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Exception;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Exception\SqlConnectorException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\UnderConstructionException;


/**
 * Databases class
 *
 * This class is the quick access to all database connectors, SQL or NoSQL alike
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Databases
{
    /**
     * The register with all SQL database connectors
     *
     * @var array $sql
     */
    protected static array $sql = [];

    /**
     * The register with all Memcached connectors
     *
     * @var array $mc
     */
    protected static array $mc = [];

    /**
     * The register with all Redis database connectors
     *
     * @var array $redis
     */
    protected static array $redis = [];

    /**
     * The register with all Mongo database connectors
     *
     * @var array $mongo
     */
    protected static array $mongo = [];

    /**
     * The register with all NullDb database connectors
     *
     * @var array $null_db
     */
    protected static array $null_db = [];


    /**
     * Returns an array with the available drivers
     *
     * @return array
     */
    public static function getDrivers(): array
    {
        return ['mysql', 'redis', 'mongo', 'mongodb', 'elastic', 'elasticsearch'];
    }


    /**
     * Returns a Database connector for the specified connector
     *
     * @param ConnectorInterface $connector
     * @param bool $use_database
     * @return DatabaseInterface
     * @throws Exception
     */
    public static function fromConnector(ConnectorInterface $connector, bool $use_database = true): DatabaseInterface
    {
        return match ($connector->getType()) {
            'sql'   => Databases::Sql($connector, $use_database),
            default => throw new UnderConstructionException(),
        };
    }


    /**
     * Access SQL database connectors
     *
     * @param ConnectorInterface|string $connector
     * @param bool $use_database
     * @return SqlInterface
     */
    public static function Sql(ConnectorInterface|string $connector = 'system', bool $use_database = true): SqlInterface
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system';
        }

        if ($connector instanceof ConnectorInterface) {
            $connector_name = $connector->getName();

            if (!$connector_name) {
                throw new SqlConnectorException(tr('Specified connector ":connector" has empty name', [
                    ':connector' => $connector->getSource()
                ]));
            }

        } else {
            // The connector specified was a connector name
            $connector_name = $connector;
        }

        if (!array_key_exists($connector_name, static::$sql)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it.
            // Connect and add it
            static::$sql[$connector_name] = new Sql($connector, $use_database);
        }

        return static::$sql[$connector_name];
    }


    /**
     * Access Memcached database connectors
     *
     * @param string|null $connector
     * @return Mc
     * @throws Exception
     */
    public static function Mc(?string $connector): Mc
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system_mc';
        }

        if (!array_key_exists($connector, static::$mc)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$mc[$connector] = new Mc($connector);
        }

        return static::$mc[$connector];
    }


    /**
     * Access Redis database connectors
     *
     * @param string|null $connector
     * @return Redis
     * @throws Exception
     */
    public static function Redis(?string $connector): Redis
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system_redis';
        }

        if (!array_key_exists($connector, static::$redis)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$redis[$connector] = new Redis($connector);
        }

        return static::$redis[$connector];
    }


    /**
     * Access Mongo database connectors
     *
     * @param string|null $connector
     * @return Mongo
     * @throws Exception
     */
    public static function Mongo(?string $connector): Mongo
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system_mongodb';
        }

        if (!array_key_exists($connector, static::$mongo)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$mongo[$connector] = new Mongo($connector);
        }

        return static::$mongo[$connector];
    }


    /**
     * Access NullDb database connectors
     *
     * @param string|null $connector
     * @return NullDb
     * @throws Exception
     */
    public static function NullDb(?string $connector): NullDb
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system_nulldb';
        }

        if (!array_key_exists($connector, static::$null_db)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$null_db[$connector] = new NullDb($connector);
        }

        return static::$null_db[$connector];
    }
}
