<?php

/**
 * Class Redis
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Exception\RedisException;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\ConfigException;
use Phoundation\Utils\Exception\ConfigPathDoesNotExistsException;
use Phoundation\Utils\Json;


class Redis implements DatabaseInterface
{
    use TraitDataConnector;


    /**
     * The Redis driver
     *
     * @var \Redis $client
     */
    protected \Redis $client;


    /**
     * Initialize the class object through the constructor.
     *
     * MC constructor.
     *
     * @param ConnectorInterface|string|null $connector
     */
    public function __construct(ConnectorInterface|string|null $connector = null)
    {
        if ($connector === null) {
            $connector = Config::getString('databases.redis.connectors.default', 'system-redis');

        }

        if (is_string($connector)) {
            $connector = new Connector($connector);
        }

        $this->setConnectorObject($connector);
    }


    /**
     * Connects to the Redis database
     *
     * @return static
     */
    public function connect(): static
    {
        if (empty($this->client)) {
            // Read configuration and connect
            $this->client = new \Redis($this->o_connector->getRedisConfiguration());
        }

        return $this;
    }


    /**
     * Returns the value for the specified key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key): mixed
    {
        $this->connect();

        $value = parent::get($key);

        if ($value) {
            return Json::decode($value);
        }

        return null;
    }


    /**
     * Get the document for the specified key from the specified collection
     *
     * @param string|array $value
     * @param string $key
     * @param int|null $timeout
     * @return int The _id of the inserted document
     */
    public function set(mixed $value, string $key, ?int $timeout = null): int
    {
        $this->connect();

        return parent::set($key, Json::encode($value), $timeout);
    }


    /**
     * Get the document for the specified key from the specified collection
     *
     * @param $key
     * @return int The number of documents deleted
     */
    public function delete($key): int
    {
        $this->connect();

        return parent::del($key);
    }


    /**
     * Pushes the specified value at the beginning of the queue
     *
     * @param mixed $value
     *
     * @return $this
     * @throws \RedisException
     */
    public function push(mixed $value): static
    {
        $this->client->lPush($value);
        return $this;
    }


    /**
     * Pops the last value off the queue and returns it
     *
     * @return mixed
     * @throws \RedisException
     */
    public function pop(): mixed
    {
        return $this->client->brPop();
    }


    /**
     * Connects to this database and executes a test query
     *
     * @return static
     */
    public function test(): static
    {
        throw new UnderConstructionException();
        return $this;
    }
}
