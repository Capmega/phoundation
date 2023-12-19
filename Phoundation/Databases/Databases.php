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
 * This class is the quick access to all database instances, SQL or NoSQL alike
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * Returns a Database instance for the specified connector
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
     * Access SQL database instances
     *
     * @param ConnectorInterface|string $connector
     * @param bool $use_database
     * @return SqlInterface
     */
    public static function Sql(ConnectorInterface|string $connector = 'system', bool $use_database = true): SqlInterface
    {
        if (!$connector) {
            // Default to system instance
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
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it.
            // Connect and add it
            static::$sql[$connector_name] = new Sql($connector, $use_database);
        }

        return static::$sql[$connector_name];
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

        if (!array_key_exists($instance, static::$mc)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$mc[$instance] = new Mc($instance);
        }

        return static::$mc[$instance];
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

        if (!array_key_exists($instance, static::$redis)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$redis[$instance] = new Redis($instance);
        }

        return static::$redis[$instance];
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

        if (!array_key_exists($instance, static::$mongo)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$mongo[$instance] = new Mongo($instance);
        }

        return static::$mongo[$instance];
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

        if (!array_key_exists($instance, static::$null_db)) {
            // No panic now! This instance isn't registered yet, so it might very well be the first time we're using it
            // Try connecting
            static::$null_db[$instance] = new NullDb($instance);
        }

        return static::$null_db[$instance];
    }
}
