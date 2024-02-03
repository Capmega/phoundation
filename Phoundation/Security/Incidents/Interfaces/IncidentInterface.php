<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\Exception;
use Phoundation\Security\Incidents\Exception\Interfaces\SeverityInterface;
use Phoundation\Security\Incidents\Incident;
use Throwable;


/**
 * interface IncidentInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 * @todo Incidents should be able to throw exceptions depending on type. AuthenticationFailureExceptions, for example, should be thrown from here so that it is no longer required for the developer to both register the incident AND throw the exception
 */
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
     * @return static
     */
    public function setLog(bool $log): static;

    /**
     * Sets who will be notified about this incident directly without accessing the roles object
     *
     * @param IteratorInterface|array|string|null $roles
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
     * @param SeverityInterface|string $severity
     * @return static
     */
    public function setSeverity(SeverityInterface|string $severity): static;

    /**
     * Saves the incident to database
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static;

    /**
     * Throw an incidents exception
     *
     * @param string|null $exception
     * @return never
     */
    public function throw(?string $exception = null): never;
}
