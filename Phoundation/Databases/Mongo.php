<?php

declare(strict_types=1);

namespace Phoundation\Databases;

use MongoDB\Client;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigurationDoesNotExistsException;
use Phoundation\Databases\Exception\MongoException;
use Phoundation\Databases\Interfaces\DatabaseInterface;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;


/**
 * Class Mongo
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Mongo extends Client implements DatabaseInterface
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
     * @var string|null
     */
    protected ?string $database = null;


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
     * Returns the configuration for this Mongo instance
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }


    /**
     * Sets the database for this Mongo instance
     *
     * @param string $database
     * @return void
     */
    public function setDatabase(string $database): void
    {
        // TODO add tests on if this database exists?
        $this->database = $database;
    }


    /**
     * Returns the database for this Mongo instance
     *
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }


    /**
     * Get the document for the specified key from the specified collection
     *
     * @param string $key
     * @param string $collection
     * @return object|array|null
     */
    public function get(string $key, string $collection): object|array|null
    {
        return $this->selectCollection($this->database, $collection)->findOne(['_id' => $key]);
    }


    /**
     * Get the document for the specified key from the specified collection
     *
     * @param string|array $value
     * @param string $key
     * @param string $collection
     * @return int The _id of the inserted document
     */
    public function set(string|array $value, string $key, string $collection): int
    {
        $collection = $this->selectCollection($this->database, $collection);
        $document   = $collection->findOne(['_id' => $key]);

        if ($document === null) {
            $result = $collection->insertOne([
                '_id' => $key,
                'data' => $value
            ]);

            return $result->getInsertedId();
        }

        $collection->updateOne(['_id' => $key], [
            '$set' => [
                '_id' => $key,
                'data' => $value
            ]
        ]);

        return $key;
    }


    /**
     * Get the document for the specified key from the specified collection
     *
     * @param string $collection
     * @param string $key
     * @return bool True if a document was deleted
     */
    public function delete(string $collection, string $key): bool
    {
        $result = $this->selectCollection($this->database, $collection)->deleteOne(['_id' => $key]);
        return (bool) $result->getDeletedCount();
    }


    /**
     * Read the mongodb configuration
     *
     * @param string $instance
     * @return void
     */
    protected function readConfiguration(string $instance): void
    {
        // Read in the entire mongo configuration for the specified instance
        $this->instance = $instance;

        try {
            $configuration = Config::get('databases.mongo.connectors.' . $instance);
        } catch (ConfigurationDoesNotExistsException $e) {
            throw new MongoException(tr('The specified mongo instance ":instance" is not configured', [
                ':instance' => $instance
            ]));
        }

        // Validate configuration
        if (!is_array($configuration)) {
            throw new ConfigException(tr('The configuration for the specified Mongo database instance ":instance" is invalid, it should be an array', [
                ':instance' => $instance
            ]));
        }

// TODO Add support for instace configuration stored in database

        $template = [
            'host'             => 'mongodb://localhost/',
            'port'             => null,
            'options'          => [],
            'driver_options'   => [],
            'database'         => null,
        ];

        // Copy the configuration options over the template
        $this->configuration = Arrays::mergeFull($template, $configuration);
        $this->database      = $this->configuration['database'];
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