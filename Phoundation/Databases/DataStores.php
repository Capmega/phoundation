<?php

/**
 * DataStores class
 *
 * This class is the quick access to all database connectors, SQL or NoSQL alike
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases;

use Exception;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\UnderConstructionException;


class DataStores
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
     * Database connectors handler
     *
     * @var ConnectorsInterface $connectors
     */
    protected static ConnectorsInterface $connectors;


    /**
     * Returns an array with the available drivers
     *
     * @return array
     */
    public static function getDrivers(): array
    {
        return [
            'mysql',
            'redis',
            'mongo',
            'mongodb',
            'elastic',
            'elasticsearch',
        ];
    }


    /**
     * Returns the database connectors object
     *
     * @return ConnectorsInterface
     */
    public static function getConnectorsObject(): ConnectorsInterface
    {
        if (empty(static::$connectors)) {
            static::$connectors = Connectors::new()->load();
        }

        return static::$connectors;
    }


    /**
     * Returns a Database connector for the specified connector
     *
     * @param ConnectorInterface $connector
     * @param bool               $use_database
     *
     * @return DatabaseInterface
     * @throws Exception
     */
    public static function fromConnector(ConnectorInterface $connector, bool $use_database = true): DatabaseInterface
    {
        return match ($connector->getType()) {
            'sql'   => DataStores::sql($connector, $use_database),
            default => throw new UnderConstructionException(),
        };
    }


    /**
     * Access SQL database connectors
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $use_database
     * @param bool                           $connect
     *
     * @return SqlInterface
     */
    public static function sql(ConnectorInterface|string|null $connector = 'system', bool $use_database = true, bool $connect = true): SqlInterface
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system';
        }

        if ($connector instanceof ConnectorInterface) {
            $connector_name = $connector->getDisplayName();

        } else {
            // The connector specified was a connector name
            $connector_name = $connector;
        }

        if (!array_key_exists($connector_name, static::$sql)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it.
            // Connect and add it
            static::$sql[$connector_name] = new Sql($connector, $use_database, $connect);
        }

        return static::$sql[$connector_name];
    }


    /**
     * Access Memcached database connectors
     *
     * @param string|null $connector
     *
     * @return Mc
     * @throws Exception
     */
    public static function mc(?string $connector): Mc
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system-mc';
        }

        if ($connector instanceof ConnectorInterface) {
            $connector_name = $connector->getDisplayName();

        } else {
            // The connector specified was a connector name
            $connector_name = $connector;
        }

        if (!array_key_exists($connector_name, static::$mc)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it.
            // Connect and add it
            static::$mc[$connector] = new Mc($connector);
        }

        return static::$mc[$connector];
    }


    /**
     * Access Redis database connectors
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $connect
     *
     * @return Redis
     */
    public static function redis(ConnectorInterface|string|null $connector = 'system-redis', bool $connect = true): Redis
    {
        if ($connector instanceof ConnectorInterface) {
            $connector_name = $connector->getDisplayName();

        } else {
            // The connector specified was a connector name or null
            if ($connector === null) {
                $connector = 'system-redis';
            }
            $connector_name = $connector;
        }

        if (!array_key_exists($connector_name, static::$redis)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$redis[$connector] = new Redis($connector);
        }

        return static::$redis[$connector_name];
    }


    /**
     * Access Mongo database connectors
     *
     * @param string|null $connector
     *
     * @return Mongo
     * @throws Exception
     */
    public static function mongo(?string $connector): Mongo
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system-mongodb';
        }


        if ($connector instanceof ConnectorInterface) {
            $connector_name = $connector->getDisplayName();

        } else {
            // The connector specified was a connector name
            $connector_name = $connector;
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
     *
     * @return NullDb
     * @throws Exception
     */
    public static function nullDb(?string $connector): NullDb
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system-nulldb';
        }

        if ($connector instanceof ConnectorInterface) {
            $connector_name = $connector->getDisplayName();

        } else {
            // The connector specified was a connector name
            $connector_name = $connector;
        }

        if (!array_key_exists($connector, static::$null_db)) {
            // No panic now! This connector isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$null_db[$connector] = new NullDb($connector);
        }

        return static::$null_db[$connector];


    }
}
