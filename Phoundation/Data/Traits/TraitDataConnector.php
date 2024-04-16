<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Databases\Connectors\Connector;
use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Sql\Exception\Interfaces\SqlExceptionInterface;
use Phoundation\Seo\Seo;
use Phoundation\Utils\Config;

/**
 * Trait TraitDataConnector
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataConnector
{
    /**
     * The connector to use
     *
     * @var ConnectorInterface|null $connector
     */
    protected ?ConnectorInterface $connector = null;


    /**
     * Returns the source
     *
     * @return ConnectorInterface|null
     */
    public function getConnector(): ?ConnectorInterface
    {
        return $this->connector;
    }


    /**
     * Sets the source
     *
     * @param ConnectorInterface|string|null $connector
     * @param bool                           $ignore_sql_exceptions
     *
     * @return static
     * @throws SqlExceptionInterface
     */
    public function setConnector(ConnectorInterface|string|null $connector, bool $ignore_sql_exceptions = false): static
    {
        if (!$connector) {
            $connector = 'system';
        }
        try {
            $this->connector = Connector::load($connector);

        } catch (SqlExceptionInterface $e) {
            if (!$ignore_sql_exceptions) {
                throw $e;
            }
            // Sql failed, which might be due to the system database or databases_connectors table not existing?
            // Try getting the connector from configuration
            $entry = Config::getArray('databases.connectors.' . $connector, []);
            if (count($entry)) {
                $entry['name']     = $connector;
                $entry['seo_name'] = Seo::string($connector);
                $this->connector = Connector::newFromSource($entry, true)
                                            ->setReadonly(true);
            }
        }

        return $this;
    }
}
