<?php

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Databases\Connectors\Connector;
use Phoundation\Exception\OutOfBoundsException;
use ReturnTypeWillChange;
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

    /**
     * Returns only the specified key from the source of this DataEntry
     *
     * @note This method filters out all keys defined in static::getProtectedKeys() to ensure that keys like "password"
     *       will not become available outside this object
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool                        $exception
     *
     * @return ConnectorInterface|null
     */
    #[ReturnTypeWillChange] public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): ?ConnectorInterface;

    /**
     * Returns a random connector
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function getRandom(): ?DataEntryInterface;

    /**
     * Returns the current entry
     *
     * @note overrides the IteratorCore::current() method which returns mixed
     *
     * @return DataEntryInterface|null
     */
    #[ReturnTypeWillChange] public function current(): ?DataEntryInterface;
}
