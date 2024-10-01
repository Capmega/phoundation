<?php

/**
 * Trait TraitDataDatabaseConnector
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Connectors\SystemConnector;


trait TraitDataConnector
{
    /**
     * Tracks the database connector containing the connection information about the database where this DataEntry
     * object is stored
     *
     * @var ConnectorInterface|null $o_connector
     */
    protected ?ConnectorInterface $o_connector = null;

    /**
     * Tracks the database connector name
     *
     * @var string|null $connector
     */
    protected ?string $connector = null;


    /**
     * Returns the name of the database connector where this DataEntry is stored
     *
     * @return string
     */
    public function getConnector(): string
    {
        return $this->connector ?? static::getDefaultConnector();
    }


    /**
     * Sets the database connector by name
     *
     * @param string      $connector
     * @param string|null $database
     *
     * @return static
     */
    public function setConnector(string $connector, ?string $database = null): static
    {
        $this->connector = $connector;

        if (empty($this->o_connector)) {
            $connector = $this->getConnector();

            if (!$connector or ($connector === 'system')) {
                $this->setConnectorObject(new SystemConnector(), $database);

            } else {
                $this->setConnectorObject(new Connector($connector), $database);
            }
        }

        return $this;
    }


    /**
     * Returns the default database connector to use for this table
     *
     * @return string
     */
    public static function getDefaultConnector(): string
    {
        return 'system';
    }


    /**
     * Returns a database connector for this DataEntry object
     *
     * @return ConnectorInterface
     */
    public static function getDefaultConnectorObject(): ConnectorInterface
    {
        return new Connector(static::getDefaultConnector());
    }


    /**
     * Returns the database connector
     *
     * @return ConnectorInterface
     */
    public function getConnectorObject(): ConnectorInterface
    {
        if (empty($this->o_connector)) {
            $connector = $this->getConnector();

            if ($connector === 'system') {
                $this->setConnectorObject(new SystemConnector());

            } else {
                $this->setConnectorObject(new Connector($connector));
            }
        }

        return $this->o_connector;
    }


    /**
     * Sets the database connector
     *
     * @param ConnectorInterface $o_connector
     * @param string|null        $database
     *
     * @return static
     */
    public function setConnectorObject(ConnectorInterface $o_connector, ?string $database = null): static
    {
        $this->o_connector = $o_connector;
        $this->connector   = $o_connector->getName();

        if ($database) {
            $this->o_connector->setDatabase($database);
        }

        return $this;
    }
}
