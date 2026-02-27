<?php

 namespace Phoundation\Accounts\Users\Sessions\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\UserSession;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Stringable;

interface UserSessionInterface extends DataEntryInterface
{
    /**
     * Returns the session identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Returns the domain for this session
     *
     * @return string
     */
    public function getDomain(): string;

    /**
     * Returns the session IP
     *
     * @return string
     */
    public function getIp(): string;

    /**
     * Returns the session users id
     *
     * @return int
     */
    public function getUsersId(): int;

    /**
     * Returns the session user object
     *
     * @return UserInterface
     */
    public function getUserObject(): UserInterface;

    /**
     * Returns the session start datetime string
     *
     * @return string
     */
    public function getStart(): string;

    /**
     * Returns the session start datetime object
     *
     * @return PhoDateTimeInterface
     */
    public function getStartObject(): PhoDateTimeInterface;

    /**
     * Returns the session stop datetime object
     *
     * @return PhoDateTimeInterface
     */
    public function getStopObject(): PhoDateTimeInterface;


    /**
     *  Returns the value for the specified session user data key
     *
     * @param Stringable|string|float|int $key
     * @param mixed                       $default
     * @param bool|null                   $exception
     *
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, mixed $default = null, ?bool $exception = null): mixed;

    /**
     * Sets the specified session user data key to the specified value
     *
     * @param mixed                       $value
     *
     * @param Stringable|string|float|int $key
     * @param bool                        $skip_null_values
     *
     * @return UserSession
     */
    public function set(mixed $value, Stringable|string|float|int $key, bool $skip_null_values = true): static;

    /**
     * Saves the session data
     *
     * @return static
     */
    public function save(): static;

    /**
     * Copies the data for this session to a session with the specified identifier
     *
     * @param string $identifier
     *
     * @return $this
     */
    public function copyTo(string $identifier): static;

    /**
     * Returns if the specified identifier is an active session.
     *
     * @return bool
     */
     public function isActive(): bool;

    /**
     * Adds data to the specified sessions list
     *
     * @param array $session
     *
     * @return static
     */
    public function addData(array $session): static;
}
