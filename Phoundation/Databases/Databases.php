<?php

/**
 * DataStores class
 *
 * This class is the quick access to all database connectors, SQL or NoSQL alike
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Databases\FileDb\FileDb;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Databases\Memcached\Interfaces\MemcachedInterface;
use Phoundation\Databases\Memcached\Memcached;
use Phoundation\Databases\MongoDb\MongoDb;
use Phoundation\Databases\NullDb\NullDb;
use Phoundation\Databases\Redis\Interfaces\RedisInterface;
use Phoundation\Databases\Redis\Redis;
use Phoundation\Databases\Sql\Interfaces\SqlInterface;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Exception\OutOfBoundsException;


class Databases
{
    use TraitStaticMethodNew;


    /**
     * The register with all database connections
     *
     * @var array $databases
     */
    protected static array $databases = [];

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
            'null',
            'file',
            'mysql',
            'redis',
            'mongo',
            'elasticsearch',
        ];
    }


    /**
     * Returns a (cached) connector object for the specified connector name
     *
     * If the object already exists in the connectors list, it will return the cached connector instead.
     *
     * @param ConnectorInterface|string $connector
     *
     * @return ConnectorInterface
     */
    public static function getConnectorObject(ConnectorInterface|string $connector): ConnectorInterface
    {
        if ($connector instanceof ConnectorInterface) {
            if (!static::getConnectorsObject()->keyExists($connector->getDisplayName())) {
                static::getConnectorsObject()->add($connector, $connector->getDisplayName());
            }

            // The specified connector is already an object, return it
            return $connector;
        }

        if (!$connector) {
            throw new OutOfBoundsException(tr('Cannot return Connector object because no connector name is specified'));
        }

        // Connectors::get() will automatically load the required connector object if it isn't loaded yet
        return static::getConnectorsObject()->get($connector, false);
    }


    /**
     * Returns the database connectors object
     *
     * @return ConnectorsInterface
     */
    public static function getConnectorsObject(): ConnectorsInterface
    {
        if (empty(static::$connectors)) {
            static::$connectors = Connectors::new();
        }

        return static::$connectors;
    }


    /**
     * Returns a Database connector for the specified connector
     *
     * @param ConnectorInterface $connector
     * @param bool               $connect
     * @param bool               $use_database
     *
     * @return DatabaseInterface
     */
    public static function fromConnector(ConnectorInterface $connector, bool $connect = true, bool $use_database = true): DatabaseInterface
    {
        return match ($connector->getType()) {
            'sql'       => Databases::getSql($connector, $connect, $use_database),
            'null'      => Databases::getNullDb($connector, $connect, $use_database),
            'file'      => Databases::getFileDb($connector, $connect, $use_database),
            'mongo'     => Databases::getMongo($connector, $connect, $use_database),
            'redis'     => Databases::getRedis($connector, $connect, $use_database),
            'memcached' => Databases::getMemcached($connector, $connect, $use_database),
            default     => throw new OutOfBoundsException(tr('Unknown connector type ":type" specified', [
                ':type' => $connector->getType()
            ])),
        };
    }


    /**
     * Returns a database object for the specified database connector
     *
     * @param ConnectorInterface|string|null $connector
     * @param string                         $class
     * @param bool                           $connect
     * @param bool                           $use_database
     *
     * @return SqlInterface|RedisInterface|MemcachedInterface|MongoDb|FileDb|NullDb
     */
    protected static function getDatabase(ConnectorInterface|string|null $connector, string $class, bool $connect = true, bool $use_database = true): SqlInterface|RedisInterface|MemcachedInterface|MongoDb|FileDb|NullDb
    {
        $o_connector    = Databases::getConnectorObject($connector);
        $connector_name = $o_connector->getDisplayName();

        if (!array_key_exists($connector_name, static::$databases)) {
            // This connector isn't registered yet, so connect and add it to the "connectors" list
            static::$databases[$connector_name] = new $class($o_connector, $connect, $use_database);
        }

        return static::$databases[$connector_name];
    }


    /**
     * Access SQL database connectors
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $connect
     * @param bool                           $use_database
     *
     * @return SqlInterface
     */
    public static function getSql(ConnectorInterface|string|null $connector = 'system', bool $connect = true, bool $use_database = true): SqlInterface
    {
        if (!$connector) {
            // Default to system connector
            $connector = 'system';
        }

        return static::getDatabase($connector, Sql::class, $connect, $use_database);
    }


    /**
     * Access Memcached database connectors
     *
     * @param ConnectorInterface|string $connector
     * @param bool                      $connect
     * @param bool                      $use_database
     *
     * @return MemcachedInterface
     */
    public static function getMemcached(ConnectorInterface|string $connector, bool $connect = true, bool $use_database = true): MemcachedInterface
    {
        return static::getDatabase($connector, Memcached::class, $connect, $use_database);
    }


    /**
     * Access Redis database connectors
     *
     * @param ConnectorInterface|string $connector
     * @param bool                      $connect
     * @param bool                      $use_database
     *
     * @return RedisInterface
     */
    public static function getRedis(ConnectorInterface|string $connector, bool $connect = true, bool $use_database = true): RedisInterface
    {
        return static::getDatabase($connector, Redis::class, $connect, $use_database);
    }


    /**
     * Access Mongo database connectors
     *
     * @param ConnectorInterface|string $connector
     * @param bool                      $connect
     * @param bool                      $use_database
     *
     * @return MongoDb
     */
    public static function getMongo(ConnectorInterface|string $connector, bool $connect = true, bool $use_database = true): MongoDb
    {
        return static::getDatabase($connector, MongoDb::class, $connect, $use_database);
    }


    /**
     * Access NullDb database connectors
     *
     * @param ConnectorInterface|string $connector
     * @param bool                      $connect
     * @param bool                      $use_database
     *
     * @return NullDb
     */
    public static function getNullDb(ConnectorInterface|string $connector, bool $connect = true, bool $use_database = true): NullDb
    {
        return static::getDatabase($connector, NullDb::class, $connect, $use_database);
    }


    /**
     * Access FileDb database connectors
     *
     * @param ConnectorInterface|string $connector
     * @param bool                      $connect
     * @param bool                      $use_database
     *
     * @return FileDb
     */
    public static function getFileDb(ConnectorInterface|string $connector, bool $connect = true, bool $use_database = true): FileDb
    {
        return static::getDatabase($connector, FileDb::class, $connect, $use_database);
    }
}
