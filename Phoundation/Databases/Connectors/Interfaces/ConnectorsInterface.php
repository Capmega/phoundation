<?php

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Stringable;

interface ConnectorsInterface extends DataIteratorInterface
{
    /**
     * @inheritDoc
     */
    public function copyConnector(Stringable|int|string|null $from_connector, Stringable|int|string|null $to_connector): static;

    /**
     * Returns the specified connector but with the specified database selected instead of its default one
     *
     * ConnectorInterface
     */
    public function getConnectorWithDatabase(string|int $connector, string $database): ConnectorInterface;
}
