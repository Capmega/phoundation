<?php

namespace Phoundation\Accounts\Users\Sessions\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\UserSession;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Date\PhoDateTime;
use Stringable;

interface UserSessionInterface extends DataEntryInterface
{
    /**
     * Returns the session "close" datetime string or NULL if the session is still open
     *
     * @return string|null
     */
    public function getClosed(): ?string;


    /**
     * Sets the session close datetime string or NULL if the session is still open
     *
     * @param PhoDateTimeInterface|string|null $close The session close date time
     *
     * @return static
     */
    public function setClosed(PhoDateTimeInterface|string|null $close = null): static;


    /**
     * Returns the session stop datetime object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getClosedObject(): ?PhoDateTimeInterface;


    /**
     * Closes this session
     *
     * This method will set the status of the UserSession entry to "closed" and set the "closed" value to "now" datetime
     *
     * @return static
     */
    public function close(): static;


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
     * Deletes this session
     *
     * @return static
     */
    public function delete(?string $comments = null, bool $auto_save = true): static;


    /**
     * @inheritDoc
     */
    public function load(IdentifierInterface|int|array|string|null $identifier = null, ?EnumLoadParameters $on_null_identifier = null, ?EnumLoadParameters $on_not_exists = null): ?static;


    /**
     * Will save the data from this data entry to the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;


    /**
     * Returns if the specified identifier is an active session.
     *
     * @return bool
     */
    public function isActive(): bool;


    /**
     * Adds data to the specified sessions list
     *
     * @param array|string|null $data
     *
     * @return static
     */
    public function addExtraData(array|string|null $data): static;


    /**
     * Copies the data for this session to a session with the specified identifier
     *
     * @param string $code
     *
     * @return $this
     */
    public function copyTo(string $code): static;

    /**
     * Returns the code for this object
     *
     * @return string|int|null
     */
    public function getCode(): string|int|null;

    /**
     * Returns the code for this object
     *
     * @return string|null
     */
    public function getDisplayCode(): string|null;

    /**
     * Sets the code for this object
     *
     * @param string|int|null $code
     *
     * @return static
     */
    public function setCode(string|int|null $code): static;

    /**
     * Returns the domain for this object
     *
     * @return string|null
     */
    public function getDomain(): ?string;

    /**
     * Sets the domain for this object
     *
     * @param string|null $domain
     *
     * @return static
     */
    public function setDomain(?string $domain): static;

    /**
     * Returns the remote_ip for this object
     *
     * @return string|null
     */
    public function getRemoteIp(): string|null;

    /**
     * Sets the remote_ip for this object
     *
     * @param string|null $remote_ip
     *
     * @return static
     */
    public function setRemoteIp(string|null $remote_ip): static;

    /**
     * Returns the remote_ip for this object
     *
     * @return string|null
     */
    public function getRemoteIpReal(): string|null;

    /**
     * Sets the remote_ip for this object
     *
     * @param string|null $remote_ip
     *
     * @return static
     */
    public function setRemoteIpReal(string|null $remote_ip): static;


    /**
     * Returns the session "close" datetime string or NULL if the session is still open
     *
     * @return string|null
     */
    public function getOpened(): ?string;

    /**
     * Returns the session close datetime object
     *
     * @return PhoDateTimeInterface|null
     */
    public function getOpenedObject(): ?PhoDateTimeInterface;

    /**
     * Sets the session close datetime string or NULL if the session is still open
     *
     * @param PhoDateTimeInterface|string|null $close The session close date time
     *
     * @return static
     */
    public function setOpened(PhoDateTimeInterface|string|null $close = null): static;

    /**
     * Returns the users_id column
     *
     * @return int|null
     */
    public function getUsersId(): ?int;

    /**
     * Sets the users_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setUsersId(?int $id): static;

    /**
     * Returns the users_email column
     *
     * @return string|null
     */
    public function getUsersEmail(): ?string;

    /**
     * Sets the users_email column
     *
     * @param string|null $email
     * @return static
     */
    public function setUsersEmail(?string $email): static;

    /**
     * Returns the User Object
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface;

    /**
     * Returns the users_id for this user
     *
     * @param UserInterface|null $_object
     *
     * @return static
     */
    public function setUserObject(?UserInterface $_object): static;

    /**
     * Returns the string containing the last activity
     *
     * @return PhoDateTimeInterface|null
     */
    public function getLastActivity(): ?string;

    /**
     * Returns the PhoDateTime object containing the last activity datetime
     *
     * @return PhoDateTimeInterface|null
     */
    public function getLastActivityObject(): ?PhoDateTimeInterface;

    /**
     * Sets the string containing the last activity
     *
     * @param PhoDateTimeInterface|string|null $last_activity The last_activity value
     *
     * @return static
     */
    public function setLastActivity(PhoDateTimeInterface|string|null $last_activity): static;

    /**
     * Returns if the specified code is an active session.
     *
     * @param string $code The session identifier string to test
     *
     * @return bool
     */
     public static function isActiveSession(string $code): bool;

    /**
     * Fetches extra data for this UserSession object from session save handler
     *
     * @return array|string|null
     */
    public function getExtraData(): array|string|null;
}
