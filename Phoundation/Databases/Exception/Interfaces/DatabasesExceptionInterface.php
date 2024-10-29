<?php

declare(strict_types=1);

namespace Phoundation\Databases\Exception\Interfaces;

use Phoundation\Databases\Connectors\Interfaces\ConnectorInterface;
use Phoundation\Exception\Interfaces\PhoExceptionInterface;

interface DatabasesExceptionInterface extends PhoExceptionInterface
{
    /**
     * Returns the connector interface or null if it is not found
     *
     * @return ConnectorInterface|null
     */
    public function getConnectorObject(): ?ConnectorInterface;


    /**
     * Adds the connector object information to a DatabasesException
     *
     * @param ConnectorInterface|null $connector
     *
     * @return $this
     */
    public function setConnectorObject(?ConnectorInterface $connector): static;


    /**
     * Returns the database name
     *
     * @return string|int|null
     */
    public function getDatabase(): string|int|null;


    /**
     * Sets the database name
     *
     * @param string|int|null $database
     *
     * @return static
     */
    public function setDatabase(string|int|null $database): static;
}
