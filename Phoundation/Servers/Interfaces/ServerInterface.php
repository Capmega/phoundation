<?php

namespace Phoundation\Servers\Interfaces;


/**
 * interface ServerInterface
 *
 * This class manages a single server
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Servers
 */
interface ServerInterface
{
    /**
     * Returns the cost for this object
     *
     * @return float|null
     */
    public function getCost(): ?float;

    /**
     * Sets the cost for this object
     *
     * @param float|null $cost
     * @return static
     */
    public function setCost(?float $cost): static;

    /**
     * Returns the bill_due_date for this object
     *
     * @return string|null
     */
    public function getBillDueDate(): ?string;

    /**
     * Sets the bill_due_date for this object
     *
     * @param string|null $bill_due_date
     * @return static
     */
    public function setBillDueDate(?string $bill_due_date): static;

    /**
     * Returns the interval for this object
     *
     * @return string|null
     */
    public function getInterval(): ?string;

    /**
     * Sets the interval for this object
     *
     * @param string|null $interval
     * @return static
     */
    public function setInterval(?string $interval): static;

    /**
     * Returns the os_name for this object
     *
     * @return string|null
     */
    public function getOsName(): ?string;

    /**
     * Sets the os_name for this object
     *
     * @param string|null $os_name
     * @return static
     */
    public function setOsName(?string $os_name): static;

    /**
     * Returns the os_version for this object
     *
     * @return string|null
     */
    public function getOsVersion(): ?string;

    /**
     * Sets the os_version for this object
     *
     * @param string|null $os_version
     * @return static
     */
    public function setOsVersion(?string $os_version): static;

    /**
     * Returns the web_services for this object
     *
     * @return bool
     */
    public function getWebServices(): bool;

    /**
     * Sets the web_services for this object
     *
     * @param bool|null $web_services
     * @return static
     */
    public function setWebServices(?bool $web_services): static;

    /**
     * Returns the mail_services for this object
     *
     * @return bool
     */
    public function getMailServices(): bool;

    /**
     * Sets the mail_services for this object
     *
     * @param bool|null $mail_services
     * @return static
     */
    public function setMailServices(?bool $mail_services): static;

    /**
     * Returns the database_services for this object
     *
     * @return bool
     */
    public function getDatabaseServices(): bool;

    /**
     * Sets the database_services for this object
     *
     * @param bool|null $database_services
     * @return static
     */
    public function setDatabaseServices(?bool $database_services): static;

    /**
     * Returns the allow_sshd_modifications for this object
     *
     * @return bool
     */
    public function getAllowSshdModifications(): bool;

    /**
     * Sets the allow_sshd_modifications for this object
     *
     * @param bool|null $allow_sshd_modifications
     * @return static
     */
    public function setAllowSshdModifications(?bool $allow_sshd_modifications): static;

    /**
     * Returns the username for the SSH account for this server
     *
     * @return string
     */
    public function getUsername(): string;

    /**
     * Returns the command line as it should be executed for this server
     *
     * @param string $command_line
     * @return string
     */
    public function getSshCommandLine(string $command_line): string;
}
