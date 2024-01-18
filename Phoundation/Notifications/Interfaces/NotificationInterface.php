<?php

declare(strict_types=1);

namespace Phoundation\Notifications\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Throwable;


/**
 * Class Notification
 *
 *
 * @todo Change the Notification::roles to a Data\Iterator class instead of a plain array
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notification
 */
interface NotificationInterface extends DataEntryInterface
{
    /**
     * Sets the exception for this notification
     *
     * @param Throwable $e
     * @return static
     */
    public function setException(Throwable $e): static;

    /**
     * Returns the exception for this notification
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable;

    /**
     * Returns the roles for this notification
     *
     * @return array
     */
    public function getRoles(): array;

    /**
     * Clears the message for this notification
     *
     * @return static
     */
    public function clearRoles(): static;

    /**
     * Sets the message for this notification
     *
     * @note: This will reset the current already registered roles
     * @param IteratorInterface|array|string|int $roles
     * @return static
     */
    public function setRoles(IteratorInterface|array|string|int $roles): static;

    /**
     * Sets the message for this notification
     *
     * @param IteratorInterface|array|string|int $roles
     * @return static
     */
    public function addRoles(IteratorInterface|array|string|int $roles): static;

    /**
     * Sets the message for this notification
     *
     * @param string|null $role
     * @return static
     */
    public function add(?string $role): static;

    /**
     * Send the notification
     *
     * @param bool|null $log
     * @return static
     * @todo Implement!
     */
    public function send(?bool $log = null): static;

    /**
     * Log this notification to the system logs as well
     *
     * @return static
     */
    public function log(): static;


    /**
     * Returns the message for this object
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Sets the message for this object
     *
     * @param string|null $message
     * @return static
     */
    public function setMessage(?string $message): static;
}
