<?php

 namespace Phoundation\Accounts\Users\Sessions\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\UserSession;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Stringable;

interface UserSessionInterface
{
    /**
     * Returns the databse id for this session record
     *
     * @return int|null
     */
    public function getId(): ?int;

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
     * @param bool                        $exception
     *
     * @return mixed
     */
    public function get(Stringable|string|float|int $key, bool $exception = true): mixed;

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
}
