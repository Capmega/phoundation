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

namespace Phoundation\Databases\Redis;

use Phoundation\Core\Core;
use Phoundation\Data\Traits\TraitDataConnector;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Exception\RedisConnectionFailedException;
use Phoundation\Databases\Exception\RedisException;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Databases\Redis\Interfaces\RedisInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpModuleNotAvailableException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\P;
use Throwable;
use function PHPUnit\Framework\throwException;

class Redis implements DatabaseInterface, RedisInterface
{
    use TraitDataConnector;


    /**
     * The Redis driver
     *
     * @var \Redis $client
     */
    protected \Redis $client;

    /**
     * Identifier of this instance
     *
     * @var string|null $connector
     */
    protected ?string $connector = null;

    /**
     * Entire Redis database configuration
     *
     * @var array $configuration
     */
    protected array $configuration = [];

    /**
     * Unique ID for this Redis connection
     *
     * @var string
     */
    protected string $uniqueid;

    /**
     * Sets if query logging enabled or disabled
     *
     * @var bool $debug
     */
    protected bool $debug = false;


    /**
     * Initialize the class object through the constructor.
     *
     * Redis constructor.
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $connect
     */
    public function __construct(ConnectorInterface|string|null $connector = null, bool $connect = true)
    {
        $this->uniqueid = Strings::getRandom();

        if (!class_exists('\Redis')) {
            throw new PhpModuleNotAvailableException(tr('The PHP module "redis" appears not to be installed. Please install the module first. On Ubuntu and alikes, use "sudo sudo apt-get -y install php-redis; sudo phpenmod redis" to install and enable the module., on Redhat and alikes use ""sudo yum -y install php5-memcached" to install the module. After this, a restart of your webserver or php-fpm server might be needed'));
        }

        if (!$connector || is_string($connector)) {
            $connector = new(Connector::new($connector));
        }

        $this->setConnectorObject($connector);

        if ($connect) {
            $this->connect();
        }
    }


    /**
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getDefaultConnector(): string
    {
        return 'system-redis';
    }


    /**
     * Connects to the Redis database
     *
     * @return static
     * @throws RedisConnectionFailedException
     */
    protected function connect(): static
    {
        if (empty($this->client)) {
            try {
                // Read configuration and connect
                $config = $this->o_connector->getRedisConfiguration();

                $this->client = new \Redis();
                $this->client->connect(
                    $config['host'],
                    $config['port'],
                    $config['timeout'],
                    $config['persistent_id'],
                    $config['retry_interval'],
                    $config['read_timeout'],
                    $config['context']
                );

            } catch (Throwable $e) {
                throw RedisConnectionFailedException::new(tr('Failed to connect to Redis connector ":connector"', [
                    ':connector' => $this->getConnectorObject()->getName(),]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }
        }

        return $this;
    }


    /**
     * Closes the Redis database connection
     *
     * @return $this
     */
    public function close(): static
    {
        try {
            $result = $this->client->close();
//            unset($this->client);

        } catch (Throwable $e) {
            throw RedisException::new(tr('Exception while closing Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }

        if ($result) {
            return $this;
        }

        throw RedisException::new(tr('Failed to close Redis connector ":connector"', [
            ':connector' => $this->getConnectorObject()->getName()]))
        ->setDatabase($this->getDatabase())
        ->setConnectorObject($this->getConnectorObject());
    }


    /**
     * Instantiate a new Redis object
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $connect
     *
     * @return static
     */
    public static function new(ConnectorInterface|string|null $connector = null, bool $connect = true): static
    {
        return new static($connector);
    }


    /**
     * Returns a value for the specified key
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key): mixed
    {
        try {
            $value = $this->connect()->client->get('value_' . $key);

            if ($value) {
                return Json::decode($value);
            }

            return null;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get key ":key" from Redis connector ":connector', [
                ':key' => $key,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Sets a key as a specific string value
     *
     * @param string|array $value
     * @param mixed        $key
     * @param int|null     $timeout
     *
     * @return static
     */
    public function set(mixed $value, mixed $key, ?int $timeout = null): static
    {
        try {
            $this->connect()->client
                ->set('value_' . $key, JSON::encode($value), $timeout);

            return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to set key ":key" with value ":value" from Redis connector ":connector', [
                ':key'       => $key,
                ':value'     => $value,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     *
     *
     * @return int
     */
    public function getDatabase(): int
    {
        try {
            $return = $this->connect()->client->getDbNum();

            if ($return === false) {
                throw new RedisException(tr('PHP driver Redis::getDbNum() returned false'));
            }

            return $return;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get Database from Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getLogId()]), $e)
            ->setDatabase(-1)
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Sets the database that this Redis object will interface with
     *
     * @param int $database
     *
     * @return $this
     * @throws OutOfBoundsException|RedisException
     */
    public function setDatabase(int $database): static
    {
        if ($database < 0) {
            throw new OutOfBoundsException(tr('Redis database ":database" is not a valid database id for Redis, the database must be an integer between 1 and 1024', [
                ':database' => $database
            ]));

        } elseif ($database === 0) {
            //TODO: PUT BACK OUT OF TESTING MODE
//            if (!Core::getUnitTestMode()) {
//                throw new OutOfBoundsException(tr('Redis database "0" is reserved for testing and may not be used', [
//                    ':database' => $database
//                ]));
//            }

        } elseif ($database > 1024) {
            throw new OutOfBoundsException(tr('Redis database ":database" is not a valid database id for Redis, the database must be an integer between 1 and 1024', [
                ':database' => $database
            ]));
        }

        try {
            $this->connect()->client
                ->select($database);

            return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to set database to ":database" with value ":value" from Redis connector ":connector', [
                ':database' => $database,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Drop a queue from the Redis database
     *
     * @param string $queue
     *
     * @return Redis
     */
    public function dropQueue(string $queue): static
    {
        try {
            $result = $this->connect()
                        ->client->del('queue_' . $queue);

            if ($result === false) {
                throw new RedisException(tr('PHP driver Redis::del() returned false'));
            }

            return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to drop queue ":$queue" from Redis connector ":connector', [
                ':$queue'      => $queue,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Drop a queue from the Redis database
     *
     * @param string $key
     *
     * @return Redis
     */
    public function delValue(string $key): static
    {
        try {
            $result = $this->connect()
                        ->client->del('value_' . $key);

            if ($result === false) {
                throw new RedisException(tr('PHP driver Redis::del() returned false'));
            }

            return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to delete key ":key" from Redis connector ":connector', [
                ':$key'      => $key,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Pushes the specified value to the beginning of the queue (on the left) to the queue
     *
     * @param mixed  $value
     * @param string $queue
     *
     * @return $this
     */
    public function push(mixed $value, string $queue): static
    {
        if ($value === null) {
            return $this;

        } try {
            $this->client->rPush('queue_' . $queue, Json::encode($value));
            return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to push value ":value" to Redis connector ":connector', [
                ':value'     => $value,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Pops the last value off the queue (from the right) from the queue and returns it
     *
     * @param string $queue
     * @param int    $timeout
     *
     * @return mixed
     */
    public function pop(string $queue, int $timeout = 0): mixed
    {

        if ($this->getQueueLength($queue) < 1) {
            return null;
        }

        try {
            $result = $this->client->blPop('queue_' . $queue, $timeout);
            return Json::decode($result[1]);
        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to pop from Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }

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
     * Check if a queue exists
     *
     * @param string $queue
     *
     * @return bool
     */
    public function queueExists(string $queue): bool
    {
        try {
        $result = $this->connect()
                    ->client->exists('queue_' . $queue);
        if ($result === 0) {
            return false;

        } elseif (is_int($result) && $result > 0) {
            return true;

        } else {
            return $result;
        }

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to search for queue ":queue" from Redis connector ":connector', [
                ':queue' => $queue,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Check if a key exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function keyExists(string $key): bool
    {
        try {
            return $this->connect()
                ->client->exists('value_' . $key);
        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to search for key ":key" from Redis connector ":connector', [
                ':key' => $key,
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Clears all queues and keys from database
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
            throw RedisException::new(tr('Failed to clear all from Redis connector ":connector', [
                ':connector' => $this->getConnectorObject()->getName()]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Return an array which lists all values in the Redis connector stored at the specified queue in the range
     * [start, end]. start and stop are interpreted as indices: 0 the first element, 1 the second ... -1 the last
     * element, -2 the penultimate ...
     *
     * @param string $queue
     * @param int    $start
     * @param int    $end
     *
     * @return array|null
     */
    public function getQueue(string $queue, int $start = 0, int $end = -1): ?array
    {
        try {
            $return = $this->connect()
                ->client
                    ->lRange('queue_' . $queue, $start, $end);

            if (!$return) {
                return [];
            }

            foreach ($return as &$value) {
                $value = JSON::decode($value);
            }

            unset($value);
            return $return;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get queue ":queue" from Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue' => $queue]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Peek at the first (or index-specified) element in a queue without removing it
     *
     * @param string $queue
     * @param int    $index 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
     *
     * @return bool|mixed
     */
    public function queuePeek(string $queue, int $index = 0): mixed
    {
        try {

            if ($index > ($this->getQueueLength($queue) - 1)) {
                return null;
            }

            $return = $this->connect()
                ->client->lIndex('queue_' . $queue, $index);

            if ($return) {
                return Json::decode($return);
            }

            return null;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to peek at first value in Redis queue ":queue" with connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue'     => $queue]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Takes the queue and clears all values from it
     *
     * @return $this
     */
    public function clearQueue(string $queue): static
    {
        try {
            if ($this->queueExists($queue)) {
                $this->connect()->client->set('queue_' . $queue, []);

                return $this;

            } else {
                throw RedisException::new(tr('Failed find queue ":queue"', [
                    ':queue' => $queue]))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }


        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to clear queue ":queue" with connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue'     => $queue]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Returns the length of the specified queue
     *
     * @param string $queue
     *
     * @return int
     */
    public function getQueueLength(string $queue): int
    {
        try {

            if ($this->getQueue($queue) == []) {
                return 0;
            }

            $result = $this->connect()->client->lLen('queue_' . $queue);

            if ($result === false) {
                throw RedisException::new(tr('Failed to get find Redis queue ":queue" with connector ":connector"', [
                    ':connector' => $this->getConnectorObject()->getName(),
                    ':queue' => $queue]))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());

            } else {
                return $result;
            }

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get length of Redis queue ":queue" from connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue' => $queue]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Pings connection
     *
     * @param string|null $message
     * @param bool        $exception
     *
     * @return bool|string
     */
    public function ping(?string $message = null, bool $exception = false): bool|string
    {
        try {
            $return = $this->client->ping($message);

            if ($return === false) {
                throw RedisException::new(tr('Ping returned false'))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }

            return $return;

        } catch (Throwable $e) {
            if ($exception) {
                throw RedisException::new(tr('Failed to ping Redis server ":server" with message ":message"', [
                    ':server' => $this->getConnectorObject()->getLogId(),]))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }

            return false;
        }
    }


    /**
     * Shows all keys/queues
     *
     *
     * @return array
     */
    public function showAll(): array
    {
        try {
            return $this->client->keys('*');

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get all keys for server ":server"', [
                ':server' => $this->getConnectorObject()->getLogId(),]))
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


// /**
//     * Check if a value exists in the list.
//     *
//     * @param string $queue
//     * @param mixed  $value
//     *
//     * @return bool
//     */
//    public function itemExists(string $queue, mixed $value): bool
//    {
//        try {
//            return $this->connect()
//                ->client->sIsMember($queue, $value);
//
//        } catch (Throwable $e) {
//            throw RedisException::new(tr('Failed to search list ":queue" for item ":value" with connector ":connector"', [
//                ':value' => $value,
//                ':connector' => $this->getConnectorObject()->getName(),
//                ':queue' => $queue
//            ]), $e);
//        }
//    }


//    /**
//     * Returns the values of multiple lists.
//     *
//     * @param array $lists The lists to find in the Redis database
//     *
//     * @return array|null An array containing all the values for the specified lists. If a list was not found in the Redis
//     *                    database, it's value will be NULL
//     */
//    public function getMultiple(array $lists): ?array
//    {
//        try {
//            foreach ($lists as &$key) {
//                $key = 'value_' . $key;
//            }
//
//            // Creates an array with empty values as 'false'
//            $return = $this->connect()
//                           ->client->mGet($key);
//
//            // Set false values to null, or return null if no values
//            if ($return) {
//                foreach ($return as &$value) {
//                    if (!$value) {
//                        $value = null;
//                    }
//                }
//
//                unset($value);
//            }
//
//            return null;
//
//        } catch (Throwable $e) {
//            throw RedisException::new(tr('Failed to return lists ":lists" from Redis connector ":connector"', [
//                ':lists' => $lists,
//                ':connector' => $this->getConnectorObject()->getName()
//            ]), $e);
//        }
//    }


//    /**
//     * Gets the item from the list at a specified index
//     *
//     * @param int $index
//     *
//     * @return mixed
//     */
//    public function getListElement(int $index): mixed
//    {
//        try {
//            return $this->connect()
//                ->client->lIndex($this->list_name, $index);
//
//        } catch (Throwable $e) {
//            throw RedisException::new(tr('Failed to get item at index ":index" of Redis list ":list" from connector ":connector"', [
//                ':connector' => $this->getConnectorObject()->getName(),
//                ':index' => $index,
//                ':list' => $this->list_name
//            ]), $e);
//        }
//    }
//
//
//    /**
//     * Sets an item's value in the list at a specified index
//     *
//     * @param mixed $value
//     * @param int $index
//     *
//     * @return mixed
//     */
//    public function setListElement(mixed $value, int $index): static
//    {
//        try {
//            return $this->connect()
//                ->client->lSet($this->list_name, $index, $value);
//
//        } catch (Throwable $e) {
//            throw RedisException::new(tr('Failed to set item at index ":index" of Redis list ":list" from connector ":connector"', [
//                ':connector' => $this->getConnectorObject()->getName(),
//                ':index' => $index,
//                ':list' => $this->list_name
//            ]), $e);
//        }
//    }


///**
//     * Removes the first 'n' occurrences of the value element from the list.
//     *  If count is zero, all the matching elements are removed. If count is negative,
//     *  elements are removed from tail to head. By default, remove all matching elements.
//     *
//     * @param string   $value
//     * @param int|null $count
//     *
//     * @return $this
//     */
//    public function deleteFrom(string $value, ?int $count = 0): static
//    {
//        try {
//            $this->connect()
//                ->client->lRem($this->list_name, $value, $count);
//
//            return $this;
//
//        } catch (Throwable $e) {
//            throw RedisException::new(tr('Failed to delete value ":value: from list ":list" with connector ":connector"', [
//                ':value' => $value,
//                ':connector' => $this->getConnectorObject()->getName(),
//                ':list' => $this->list_name
//            ]), $e);
//        }
//    }


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
