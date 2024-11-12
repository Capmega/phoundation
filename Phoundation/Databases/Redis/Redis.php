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
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Throwable;

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
                    ':connector' => $this->getConnectorObject()->getName()
                ]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }
        }

        return $this;
    }


    /**
     * Closes the Redis database connection
     *
     * @return static
     */
    public function close(): static
    {
        try {
            $result = $this->client->close();
            unset($this->client);

            if ($result) {
                return $this;
            }

            throw RedisException::new(tr('Failed to close Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName()]));

        } catch (Throwable $e) {
            throw RedisException::new(tr('Exception while closing Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
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
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
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
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
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
                ':connector' => $this->getConnectorObject()->getLogId()
            ]), $e)
            ->setDatabase(-1)
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Sets the database that this Redis object will interface with
     *
     * @param int $database
     *
     * @return static
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
            if (!Core::getUnitTestMode()) {
                throw new OutOfBoundsException(tr('Redis database "0" is reserved for testing and may not be used', [
                    ':database' => $database
                ]));
            }

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
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
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
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Drop/deletes a queue from the Redis database
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
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Pushes the specified value to the end of the queue (on the right)
     * e.g. if array[0] = a, array[1] = b, array[2] = c and we run push(d, array), now array[3] = d
     *
     * @param mixed  $value
     * @param string $queue
     *
     * @return static
     */
    public function push(mixed $value, string $queue): static
    {
        if ($value === null) {
            return $this;

        } try {
            $result = $this->client->rPush('queue_' . $queue, Json::encode($value));

        if ($result === false) {
            throw new RedisException(tr('PHP driver Redis rPush() returned false'));
        }

        return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to push value ":value" to Redis connector ":connector', [
                ':value'     => $value,
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Pops the first value off the queue (from the left) from the queue and returns it
     * e.g. if array[0] = a, array[1] = b, array[2] = c and we run pop(array),
     *      now array[0] = b, array[1] = c, array[2] = null.
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

            if ($result === false) {
                throw new RedisException(tr('PHP driver Redis::del() returned false'));
            }

            return Json::decode($result[1]);

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to pop from Redis connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
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
     * Check if a queue exists by name and returns a boolean value
     *
     * @param string $queue
     *
     * @return bool
     */
    public function queueExists(string $queue): bool
    {
        try {
            $result = $this->connect()->client->exists('queue_' . $queue);

            if ($result === 0) {
                return false;
            }

            if (is_int($result) and ($result > 0)) {
                return true;
            }

            return $result;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to search for queue ":queue" from Redis connector ":connector', [
                ':queue'     => $queue,
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Check if a key exists and returns a boolean value
     *
     * @param string $key
     *
     * @return bool
     */
    public function keyExists(string $key): bool
    {
        try {
            return (bool) $this->connect()->client->exists('value_' . $key);

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to search for key ":key" from Redis connector ":connector', [
                ':key'       => $key,
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
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
            $this->connect()->client->flushAll();
            return $this;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to clear all from Redis connector ":connector', [
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Return an array which lists all values in the Redis connector. If $start and $end are specified, return an array
     * which lists all values stored in the specified queue in the range [start, end]. start and stop are interpreted
     * as indices: 0 the first element, 1 the second ... -1 the last element, -2 the penultimate ...
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
            $return = $this->connect()->client->lRange('queue_' . $queue, $start, $end);

            if (!$return) {
                return [];
            }

            foreach ($return as &$value) {
                $value = JSON::decode($value);
            }

            unset($value);
            return $return;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get Queue from Redis connector ":connector', [
                ':connector' => $this->getConnectorObject()->getName()
            ]), $e)
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

            $return = $this->connect()->client->lIndex('queue_' . $queue, $index);

            if ($return) {
                return Json::decode($return);
            }

            return null;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to peek at first value in Redis queue ":queue" with connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue'     => $queue
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Takes a queue and clears all values from it, leaving it as an empty array
     *
     * @return static
     */
    public function clearQueue(string $queue): static
    {
        try {
            if ($this->queueExists($queue)) {
                $this->connect();

                if (empty($this->getQueue($queue))) {
                    return $this;
                }

                $result = $this->client->set('queue_' . $queue, []);

                if ($result) {
                    return $this;
                }

                throw RedisException::new(tr('PHP Redis driver failed to run "set" to clear queue'));
            }

            throw RedisException::new(tr('Could not find queue'));

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to clear queue ":queue" with connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue'     => $queue
            ]), $e)
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
                throw RedisException::new(tr('PHP driver Redis::lLen() returned false', [
                    ':connector' => $this->getConnectorObject()->getName(),
                    ':queue'     => $queue
                ]))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());

            } else {
                return $result;
            }

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get length of Redis queue ":queue" from connector ":connector"', [
                ':connector' => $this->getConnectorObject()->getName(),
                ':queue'     => $queue
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Pings a connection to a Redis database
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
                throw RedisException::new(tr('PHP driver Redis::ping() returned false', [
                    ':connector' => $this->getConnectorObject()->getName()
                ]))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }

            return $return;

        } catch (Throwable $e) {
            if ($exception) {
                throw RedisException::new(tr('Failed to ping Redis server ":server" with message ":message"', [
                    ':server' => $this->getConnectorObject()->getLogId(),
                    ':message' => $e->getMessage()
                ]), $e)
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }
            return false;
        }
    }


    /**
     * Shows all keys and queues in the database
     *
     * @todo rename this method. "show" typically displays information on screen, and "all" is vague. This returns all "what"? Apparently this returns available keys, so likely a better method name would be Redis::getAllKeyValues()
     * @return array
     */
    public function showAll(): array
    {
        try {
            $return = $this->client->keys('*');

            if ($return === false) {
                throw RedisException::new(tr('PHP driver Redis::keys() returned false', [
                    ':connector' => $this->getConnectorObject()->getName()
                ]))
                ->setDatabase($this->getDatabase())
                ->setConnectorObject($this->getConnectorObject());
            }

            return $return;

        } catch (Throwable $e) {
            throw RedisException::new(tr('Failed to get all keys for server ":server"', [
                ':server' => $this->getConnectorObject()->getLogId(),
            ]), $e)
            ->setDatabase($this->getDatabase())
            ->setConnectorObject($this->getConnectorObject());
        }
    }


    /**
     * Import the data dump from the specified file into the current corrected Redis database
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function import(PhoFileInterface $file): static
    {
        // TODO: Implement import() method.
        throw new UnderConstructionException();
    }


    /**
     * Export the current Redis database into a dump file
     *
     * @param PhoFileInterface $file
     *
     * @return static
     */
    public function export(PhoFileInterface $file): static
    {
        // TODO: Implement export() method.
        throw new UnderConstructionException();
    }
}
