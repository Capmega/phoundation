<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Databases\Exception\RedisException;
use Phoundation\Databases\Sql\Sql;
use Phoundation\Utils\Json;

/**
 * Class Redis
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Redis extends \Redis
{
    /**
     * Configuration
     *
     * @var array|null $configuration
     */
    protected ?array $configuration = null;

    /**
     * Identifier of this instance
     *
     * @var string|null
     */
    protected ?string $instance = null;

    /**
     * The database used by this instance
     *
     * @var int|null
     */
    protected ?int $database = null;


    /**
     * Initialize the class object through the constructor.
     *
     * MC constructor.
     *
     * @param string|null $instance
     */
    public function __construct(?string $instance = null)
    {
        if ($instance === null) {
            $instance = 'system';
        }

        // Read configuration and connect
        $this->readConfiguration($instance);
        parent::__construct($this->configuration['host'], $this->configuration['options'], $this->configuration['driver_options']);
    }


    /**
     * Returns the configuration for this Redis instance
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }


    /**
     * Returns the value for the specified key
     *
     * @param string $key
     * @return mixed
     */
    public function get($key): mixed
    {
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
        return parent::set($key, Json::encode($value), $timeout);
    }


    /**
     * Get the document for the specified key from the specified collection
     *
     * @param $key
     * @return int The amount of documents deleted
     */
    public function delete($key): int
    {
        return parent::del($key);
    }


    /**
     * Read the redis configuration
     *
     * @param string $instance
     * @return void
     */
    protected function readConfiguration(string $instance): void
    {
        // Read in the entire redis configuration for the specified instance
        $this->instance = $instance;

        try {
            $configuration = Config::get('databases.redis.instances.' . $instance);
        } catch (ConfigurationDoesNotExistsException $e) {
            throw new RedisException(tr('The specified redis instance ":instance" is not configured', [
                ':instance' => $instance
            ]));
        }

        // Validate configuration
        if (!is_array($configuration)) {
            throw new ConfigException(tr('The configuration for the specified Redis database instance ":instance" is invalid, it should be an array', [
                ':instance' => $instance
            ]));
        }

// TODO Add support for instace configuration stored in database

        $template = [
            'host'             => 'localhost',
            'port'             => 6379,
            'options'          => [],
            'driver_options'   => [],
            'database'         => null,
        ];

        // Copy the configuration options over the template
        $this->configuration = Arrays::mergeFull($template, $configuration);
        $this->database      = $this->configuration['database'];
    }
}