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
use Phoundation\Exception\PhpModuleNotAvailableException;
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

    }


    /**
     * Check if a list exists
     *
     * @param string $list
     *
     * @return bool
     */
    public function listExists(string $list): bool
    {
        try {
        return $this->connect()
                    ->client->exists($list);
        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to search for list ":list" from Redis connector ":connector', [
                ':list' => $list,
                ':connector' => $this->o_connector->getName()
            ]), $e);
        }
    }


    /**
     * Clears all lists from database
     *
     * @return static
     */
    public function clearAll(): static
    {
        try {
            $this->connect()
                 ->client->flushAll();

            return $this;

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to clear all from Redis connector ":connector', [
                ':connector' => $this->o_connector->getName()
            ]), $e);
        }
    }


    /**
     * Returns the values of multiple lists.
     *
     * @param array $lists The lists to find in the Redis database
     *
     * @return array|null An array containing all the values for the specified lists. If a list was not found in the Redis
     *                    database, it's value will be NULL
     */
    public function getMultiple(array $lists): ?array
    {
        try {
            // Creates an array with empty values as 'false'
            $return = $this->connect()
                           ->client->mGet($lists);

            // Set false values to null, or return null if no values
            if ($return) {
                foreach ($return as &$value) {
                    if (!$value) {
                        $value = null;
                    }
                }

                unset($value);
            }

            return null;

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to return lists ":lists" from Redis connector ":connector"', [
                ':lists' => $lists,
                ':connector' => $this->o_connector->getName()
            ]), $e);
        }
    }


    /**
     * Return an array which lists all values in the Redis connector stored at the specified list in the range
     * [start, end]. start and stop are interpreted as indices: 0 the first element, 1 the second ... -1 the last
     * element, -2 the penultimate ...
     *
     * @param int|null  $start
     * @param int|null  $end
     * @param bool|null $json
     *
     * @return array|null
     */
    public function select(?int $start = 0, ?int $end = -1, ?bool $json = false): ?array
    {
        try {
            $return = $this->connect()
                           ->client->lRange($this->list_name, $start, $end);

            if (!$return) {
                return null;
            }

            if (!$json) {
                foreach ($return as &$value) {
                    $value = JSON::decode($value);
                }

                unset($value);

            }
            return $return;

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to show list from Redis connector ":connector" for list ":list"', [
                ':connector' => $this->o_connector->getName(),
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Peek at the first (or index-specified) element in a list without removing it
     *
     * @param int|null $index 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     * @param bool|null $json Boolean to specify whether the output is in JSON format. False by default
     *
     * @return bool|mixed
     */
    public function peek(?int $index = 0, ?bool $json = false): mixed
    {
        try {
            $return = $this->connect()
                           ->client->lIndex($this->list_name, $index);

            if ($return) {
                if ($json) {
                    return $return;
                }
                return Json::decode($return);
            }
            return null;

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to peek at first value in Redis connector ":connector" in list ":list"', [
                ':connector' => $this->o_connector->getName(),
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * @return int
     */
    public function getListLength(): int
    {
        try {
            return $this->connect()
                         ->client->lLen($this->list_name);

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to get length of Redis queue from connector ":connector" for list ":list"', [
                ':connector' => $this->o_connector->getName(),
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Takes the list and clears all values from it
     *
     * @return $this
     */
    public function clearList(): static
    {
        try {
            $this->connect()
                 ->client->lTrim($this->list_name, 1, 0);

            return $this;

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to clear list ":list" with connector ":connector"', [
                ':connector' => $this->o_connector->getName(),
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Check if a value exists in the list.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function itemExists(mixed $value): boolean
    {
        try {
            return $this->connect()
                        ->client->lsIsMember($this->list_name, $value);

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to search list ":list" for item ":value" with connector ":connector"', [
                ':value' => $value,
                ':connector' => $this->o_connector->getName(),
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Removes the first 'n' occurrences of the value element from the list.
     *  If count is zero, all the matching elements are removed. If count is negative,
     *  elements are removed from tail to head. By default, remove all matching elements.
     *
     * @param string   $value
     * @param int|null $count
     *
     * @return $this
     */
    public function deleteFrom(string $value, ?int $count = 0): static
    {
        try {
            $this->connect()
                 ->client->lRem($this->list_name, $value, $count);

            return $this;

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to delete value ":value: from list ":list" with connector ":connector"', [
                ':value' => $value,
                ':connector' => $this->o_connector->getName(),
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Gets the item from the list at a specified index
     *
     * @param int $index
     *
     * @return mixed
     */
    public function getListElement(int $index): mixed
    {
        try {
            return $this->connect()
                        ->client->lIndex($this->list_name, $index);

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to get item at index ":index" of Redis list ":list" from connector ":connector"', [
                ':connector' => $this->o_connector->getName(),
                ':index' => $index,
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Sets an item's value in the list at a specified index
     *
     * @param mixed $value
     * @param int $index
     *
     * @return mixed
     */
    public function setListElement(mixed $value, int $index): static
    {
        try {
            return $this->connect()
                        ->client->lSet($this->list_name, $index, $value);

        } catch (Throwable $e) {
            throw new RedisException(tr('Failed to set item at index ":index" of Redis list ":list" from connector ":connector"', [
                ':connector' => $this->o_connector->getName(),
                ':index' => $index,
                ':list' => $this->list_name
            ]), $e);
        }
    }


    /**
     * Import the data dump from the specified file into the current corrected Redis database
     *
     * @param FsFileInterface $file
     *
     * @return $this
     */
    public function import(FsFileInterface $file): static
    {
        // TODO: Implement import() method.
        throw new UnderConstructionException();
    }


    /**
     * Export the current Redis database into a dump file
     *
     * @param FsFileInterface $file
     *
     * @return $this
     */
    public function export(FsFileInterface $file): static
    {
        // TODO: Implement export() method.
        throw new UnderConstructionException();
    }

}
