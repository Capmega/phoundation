<?php

namespace Phoundation\Databases\Connectors;

use Phoundation\Data\DataEntry\DataList;
use Phoundation\Databases\Connectors\Interfaces\ConnectorsInterface;
use Phoundation\Databases\Sql\Exception\DatabasesConnectorException;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;
use Phoundation\Databases\Sql\Exception\SqlConnectorException;
use Phoundation\Seo\Seo;
use Phoundation\Utils\Config;
use Throwable;


/**
 * SqlConnectors class
 *
 * This class represents a list of SqlConnector objects
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Databases
 */
class Connectors extends DataList implements ConnectorsInterface
{
    /**
     * DataList class constructor
     */
    public function __construct(?array $ids = null)
    {
        $this->query = 'SELECT * FROM `databases_connectors`';
    }


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'databases_connectors';
    }

    /**
     * @inheritDoc
     */
    public static function getEntryClass(): string
    {
        return Connector::class;
    }

    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'name';
    }


    /**
     * Load the id list from the database
     *
     * @param bool $clear
     * @return $this
     */
    public function load(bool $clear = true, bool $ignore_sql_exceptions = false): static
    {
        try {
            parent::load($clear);

        } catch (SqlExceptionInterface $e) {
            if (!$ignore_sql_exceptions) {
                // In some cases we need access to configured connectors while database connectors are not available
                // because the database may not exist, or a database version may be so old that the databases_connectors
                // table doesn't exist. In those cases where we know that this might happen, we will ignore SQL
                // exceptions and continue loading connectors from configuration
                throw $e;
            }
        }

        // Get connectors from the configuration
        $connectors = Config::getArray(Connector::new()->getConfigPath());
        $count      = 0;

        // Load all connectors by type
        foreach ($connectors as $name => &$connector) {
            if (!is_array($connector)) {
                throw new DatabasesConnectorException(tr('Invalid configuration encountered for connector ":connector", it should contain an array with at least "type"', [
                    ':connector' => $name
                ]));
            }

            if (empty($connector['type'])) {
                throw new DatabasesConnectorException(tr('Invalid configuration encountered for connector ":connector", it has no type specified', [
                    ':connector' => $name
                ]));
            }

            $connector['id']       = --$count;
            $connector['name']     = $name;
            $connector['seo_name'] = Seo::string($name);

            $this->source[$count]  = Connector::fromSource($connector, true);
        }

        return $this;
    }
}
