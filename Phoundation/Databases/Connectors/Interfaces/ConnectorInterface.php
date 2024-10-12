<?php

namespace Phoundation\Databases\Connectors\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Geo\Timezones\Interfaces\TimezoneInterface;

interface ConnectorInterface extends DataEntryInterface
{
    /**
     * Returns the password for this object
     *
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     * Sets the password for this object
     *
     * @param string|null $password
     *
     * @return static
     */
    public function setPassword(?string $password): static;

    /**
     * Returns the username for this object
     *
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * Sets the username for this object
     *
     * @param string|null $username
     *
     * @return static
     */
    public function setUsername(?string $username): static;

    /**
     * Returns the SEO hostname for this object
     *
     * @return string|null
     */
    public function getSeoHostname(): ?string;

    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string;

    /**
     * Sets the hostname for this object
     *
     * @param string|null $hostname
     *
     * @return static
     */
    public function setHostname(?string $hostname): static;

    /**
     * Returns the port for this object
     *
     * @return int|null
     */
    public function getPort(): ?int;

    /**
     * Sets the port for this object
     *
     * @param int|null $port
     *
     * @return static
     */
    public function setPort(?int $port): static;

    /**
     * Returns the description for this object
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Sets the description for this object
     *
     * @param string|null $description
     *
     * @return static
     */
    public function setDescription(?string $description): static;

    /**
     * Returns the SEO name for this object
     *
     * @return string|null
     */
    public function getSeoName(): ?string;

    /**
     * Returns the name for this object
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Sets the name for this object
     *
     * @param string|null $name
     * @param bool        $set_seo_name
     *
     * @return static
     */
    public function setName(?string $name, bool $set_seo_name = true): static;

    /**
     * Returns the timezones_id for this user
     *
     * @return int|null
     */
    public function getTimezonesId(): ?int;

    /**
     * Sets the timezones_id for this user
     *
     * @param int|null $timezones_id
     *
     * @return static
     */
    public function setTimezonesId(?int $timezones_id): static;

    /**
     * Returns the timezone for this user
     *
     * @return TimezoneInterface|null
     */
    public function getTimezone(): ?TimezoneInterface;

    /**
     * Returns the timezones_name for this user
     *
     * @return string|null
     */
    public function getTimezonesName(): ?string;

    /**
     * Sets the timezones_name for this user
     *
     * @param string|null $timezones_name
     *
     * @return static
     */
    public function setTimezonesName(?string $timezones_name): static;

    /**
     * Returns the database for this object
     *
     * @return string|null
     */
    public function getDatabase(): ?string;

    /**
     * Sets the database for this object
     *
     * @param string|null $database
     *
     * @return static
     */
    public function setDatabase(?string $database): static;

    /**
     * Returns the character_set for this object
     *
     * @return string|null
     */
    public function getCharacterSet(): ?string;

    /**
     * Sets the character_set for this object
     *
     * @param string|null $character_set
     *
     * @return static
     */
    public function setCharacterSet(?string $character_set): static;

    /**
     * Returns the collate for this object
     *
     * @return string|null
     */
    public function getCollate(): ?string;

    /**
     * Sets the collate for this object
     *
     * @param string|null $collate
     *
     * @return static
     */
    public function setCollate(?string $collate): static;

    /**
     * Returns the sync setting for this object
     *
     * @return bool|null
     */
    public function getSync(): ?bool;

    /**
     * Sets the sync setting for this object
     *
     * @param int|bool|null $sync
     *
     * @return static
     */
    public function setSync(int|bool|null $sync): static;

    /**
     * Returns if the database for this connector should be backed up
     *
     * @return bool
     */
    public function getBackup(): bool;

    /**
     * Sets if the database for this connector should be backed up
     *
     * @param bool $backup
     *
     * @return static
     */
    public function setBackup(bool $backup): static;

    /**
     * Returns the name for this user that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string;

    /**
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string;

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

    /**
     * Returns the connector configuration in an array that can be understood by the Redis driver
     *
     * @return array
     */
    public function getRedisConfiguration(): array;
}
