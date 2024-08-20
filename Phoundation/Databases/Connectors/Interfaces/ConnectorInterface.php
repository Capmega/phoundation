<?php

declare(strict_types=1);

namespace Phoundation\Databases\Connectors\Interfaces;

interface ConnectorInterface
{
    /**
     * Returns the name for this user that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string;


    /**
     * Returns the type for this connector
     *
     * @return string|null
     */
    public function getType(): ?string;


    /**
     * Sets the type for this connector
     *
     * @param string|null $type
     *
     * @return static
     */
    public function setType(?string $type): static;


    /**
     * Returns the driver for this connector
     *
     * @return string|null
     */
    public function getDriver(): ?string;


    /**
     * Sets the driver for this connector
     *
     * @param string|null $driver
     *
     * @return static
     */
    public function setDriver(?string $driver): static;


    /**
     * Returns the pdo_attributes for this connector
     *
     * @return array|null
     */
    public function getPdoAttributes(): ?array;


    /**
     * Sets the pdo_attributes for this connector
     *
     * @param array|string|null $pdo_attributes
     *
     * @return static
     */
    public function setPdoAttributes(array|string|null $pdo_attributes): static;


    /**
     * Returns the mode for this connector
     *
     * @return string|null
     */
    public function getMode(): ?string;


    /**
     * Sets the mode for this connector
     *
     * @param string|null $mode
     *
     * @return static
     */
    public function setMode(?string $mode): static;


    /**
     * Returns the limit_max for this connector
     *
     * @return int|null
     */
    public function getLimitMax(): ?int;


    /**
     * Sets the limit_max for this connector
     *
     * @param int|null $limit_max
     *
     * @return static
     */
    public function setLimitMax(?int $limit_max): static;


    /**
     * Returns the auto_increment for this connector
     *
     * @return int|null
     */
    public function getAutoIncrement(): ?int;


    /**
     * Sets the auto_increment for this connector
     *
     * @param int|null $auto_increment
     *
     * @return static
     */
    public function setAutoIncrement(?int $auto_increment): static;


    /**
     * Returns the ssh_tunnels_id for this connector
     *
     * @return int|null
     */
    public function getSshTunnelsId(): ?int;


    /**
     * Sets the ssh_tunnels_id for this connector
     *
     * @param int|null $ssh_tunnels_id
     *
     * @return static
     */
    public function setSshTunnelsId(int|null $ssh_tunnels_id): static;


    /**
     * Returns the log flag for this connector
     *
     * @return bool|null
     */
    public function getLog(): ?bool;


    /**
     * Sets the log flag for this connector
     *
     * @param int|bool|null $log
     *
     * @return static
     */
    public function setLog(int|bool|null $log): static;


    /**
     * Returns the persist flag for this connector
     *
     * @return bool|null
     */
    public function getPersist(): ?bool;


    /**
     * Sets the persist flag for this connector
     *
     * @param int|bool|null $persist
     *
     * @return static
     */
    public function setPersist(int|bool|null $persist): static;


    /**
     * Returns the init flag for this connector
     *
     * @return bool|null
     */
    public function getInit(): ?bool;


    /**
     * Sets the init flag for this connector
     *
     * @param int|bool|null $init
     *
     * @return static
     */
    public function setInit(int|bool|null $init): static;


    /**
     * Returns the buffered for this connector
     *
     * @return bool|null
     */
    public function getBuffered(): ?bool;


    /**
     * Sets the buffered for this connector
     *
     * @param int|bool|null $buffered
     *
     * @return static
     */
    public function setBuffered(int|bool|null $buffered): static;


    /**
     * Returns the statistics for this connector
     *
     * @return bool|null
     */
    public function getStatistics(): ?bool;


    /**
     * Sets the statistics for this connector
     *
     * @param int|bool|null $statistics
     *
     * @return static
     */
    public function setStatistics(int|bool|null $statistics): static;


    /**
     * Tests this connector by connecting to the database and executing a test query
     *
     * @return static
     */
    public function test(): static;
}
