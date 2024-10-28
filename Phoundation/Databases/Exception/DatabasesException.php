<?php

/**
 * Class DatabaseException
 *
 * This is the standard exception for all Phoundation Database classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Exception;

use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Databases\Exception\Interfaces\DatabasesExceptionInterface;
use Phoundation\Exception\PhoException;


class DatabasesException extends PhoException implements DatabasesExceptionInterface
{
    /**
     * Returns the connector interface or null if it is not found
     *
     * @return ConnectorInterface|null
     */
    public function getConnectorObject(): ?ConnectorInterface
    {
        return $this->getDataKey('connector');
    }


    /**
     * Adds the connector object information to a DatabasesException
     *
     * @param ConnectorInterface|null $connector
     *
     * @return static
     */
    public function setConnectorObject(?ConnectorInterface $connector): static
    {
        return $this->addData($connector, 'connector');
    }


    /**
     * Returns the database name
     *
     * @return string|int|null
     */
    public function getDatabase(): string|int|null
    {
        return $this->getDataKey('database');
    }


    /**
     * Sets the database name
     *
     * @param string|int|null $database
     *
     * @return static
     */
    public function setDatabase(string|int|null $database): static
    {
        return $this->addData($database, 'database');
    }
}
