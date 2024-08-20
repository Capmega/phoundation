<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\EnumSeverity;


interface IncidentInterface
{
    /**
     * Returns if this incident will be logged in the text log
     *
     * @return bool
     */
    public function getLog(): bool;


    /**
     * Sets if this incident will be logged in the text log
     *
     * @param bool $log
     *
     * @return static
     */
    public function setLog(bool $log): static;


    /**
     * Sets who will be notified about this incident directly without accessing the roles object
     *
     * @param IteratorInterface|array|string|null $roles
     *
     * @return Incident
     */
    public function notifyRoles(IteratorInterface|array|string|null $roles): static;


    /**
     * Returns the roles iterator containing who will be notified about this incident
     *
     * @return IteratorInterface
     */
    public function getNotifyRoles(): IteratorInterface;


    /**
     * Sets the roles iterator containing who will be notified about this incident
     *
     * @param IteratorInterface|array $notify_roles
     *
     * @return static
     */
    public function setNotifyRoles(IteratorInterface|array $notify_roles): static;


    /**
     * Returns the severity for this object
     *
     * @return string
     */
    public function getSeverity(): string;


    /**
     * Sets the severity for this object
     *
     * @param EnumSeverity|string $severity
     *
     * @return static
     */
    public function setSeverity(EnumSeverity|string $severity): static;


    /**
     * Saves the incident to the database
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static;


    /**
     * Throw an incidents exception
     *
     * @param string|null $exception
     *
     * @return never
     */
    public function throw(?string $exception = null): never;
}
