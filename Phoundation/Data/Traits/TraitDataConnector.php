<?php

/**
 * Trait TraitDataConnector
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Databases;
use Phoundation\Exception\OutOfBoundsException;


trait TraitDataConnector
{
    /**
     * Tracks the database connector containing the connection information about the database where this DataEntry
     * object is stored
     *
     * @var ConnectorInterface|null $_connector
     */
    protected ?ConnectorInterface $_connector = null;

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
     * @param string|null $connector
     * @param string|null $database
     *
     * @return static
     */
    public function setConnector(?string $connector, ?string $database = null): static
    {
        $this->connector = $connector;
        $this->setConnectorObject(Databases::getConnectorObject($this->getConnector()), $database);

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
     * @todo Add caching here. Since we can have different default connectors per class, ensure this cache is an array with connector names being the key
     * @return ConnectorInterface
     */
    public static function getDefaultConnectorObject(): ConnectorInterface
    {
        return Databases::getConnectorObject(static::getDefaultConnector());
    }


    /**
     * Returns the database connector
     *
     * @return ConnectorInterface
     */
    public function getConnectorObject(): ConnectorInterface
    {
        if (empty($this->_connector)) {
            $this->setConnector($this->connector);
        }

        return $this->_connector;
    }


    /**
     * Sets the database connector
     *
     * @note  If the specified $_connector is NULL, it will be ignored
     * @param ConnectorInterface|null $_connector
     * @param string|int|null         $database
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $_connector, string|int|null $database = null): static
    {
        if ($_connector) {
            $this->_connector = $_connector;
            $this->connector   = $_connector->getName();

            if ($database) {
                $this->_connector->setDatabase($database);
            }

        } else {
            $this->connector   = null;
            $this->_connector = null;

            if ($database) {
                throw new OutOfBoundsException(tr('Cannot specify a database name without a connector'));
            }
        }

        return $this;
    }
}
