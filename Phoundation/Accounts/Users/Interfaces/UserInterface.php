<?php

namespace Phoundation\Accounts\Users\Interfaces;

use DateTimeInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Date\DateTime;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\EntryInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\FormInterface;


/**
 * Interface UserInterface
 *
 * This is the default user class.
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface UserInterface extends DataEntryInterface
{
    /**
     * Returns id for this user entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string;

    /**
     * Returns true if the specified password matches the users password
     *
     * @param string $password
     * @return bool
     */
    public function passwordMatch(string $password): bool;

    /**
     * Returns true if this user object is the guest user
     *
     * @return bool
     */
    public function isGuest(): bool;

    /**
     * Returns the nickname for this user
     *
     * @return string|null
     */
    public function getNickname(): ?string;

    /**
     * Sets the nickname for this user
     *
     * @param string|null $nickname
     * @return static
     */
    public function setNickname(?string $nickname): static;

    /**
     * Returns the name for this user
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Returns the first_names for this user
     *
     * @return string|null
     */
    public function getFirstNames(): ?string;

    /**
     * Sets the first_names for this user
     *
     * @param string|null $first_names
     * @return static
     */
    public function setFirstNames(?string $first_names): static;

    /**
     * Returns the last_names for this user
     *
     * @return string|null
     */
    public function getLastNames(): ?string;

    /**
     * Sets the last_names for this user
     *
     * @param string|null $lastnames
     * @return static
     */
    public function setLastNames(?string $lastnames): static;

    /**
     * Returns the username for this user
     *
     * @return string|null
     */
    public function getUsername(): ?string;

    /**
     * Sets the username for this user
     *
     * @param string|null $username
     * @return static
     */
    public function setUsername(?string $username): static;

    /**
     * Returns the last_sign_in for this user
     *
     * @return string|null
     */
    public function getLastSignin(): ?string;

    /**
     * Sets the last_sign_in for this user
     *
     * @param string|null $last_sign_in
     * @return static
     */
    public function setLastSignin(?string $last_sign_in): static;

    /**
     * Returns the update_password for this user
     *
     * @return DateTime|null
     */
    public function getUpdatePassword(): ?DateTime;

    /**
     * Sets the update_password for this user
     *
     * @param DateTime|true|null $date_time
     * @return static
     */
    public function setUpdatePassword(DateTime|bool|null $date_time): static;

    /**
     * Returns the authentication_failures for this user
     *
     * @return int|null
     */
    public function getAuthenticationFailures(): ?int;

    /**
     * Sets the authentication_failures for this user
     *
     * @param int|null $authentication_failures
     * @return static
     */
    public function setAuthenticationFailures(?int $authentication_failures): static;

    /**
     * Returns the locked_until for this user
     *
     * @return string|null
     */
    public function getLockedUntil(): ?string;

    /**
     * Sets the locked_until for this user
     *
     * @param string|null $locked_until
     * @return static
     */
    public function setLockedUntil(?string $locked_until): static;

    /**
     * Returns the sign_in_count for this user
     *
     * @return int|null
     */
    public function getSigninCount(): ?int;

    /**
     * Sets the sign_in_count for this user
     *
     * @param int|null $sign_in_count
     * @return static
     */
    public function setSigninCount(?int $sign_in_count): static;

    /**
     * Returns the fingerprint datetime for this user
     *
     * @return DateTimeInterface|null
     */
    public function getFingerprint(): ?DateTimeInterface;

    /**
     * Sets the fingerprint datetime for this user
     *
     * @param DateTimeInterface|string|int|null $fingerprint
     * @return static
     */
    public function setFingerprint(DateTimeInterface|string|int|null $fingerprint): static;

    /**
     * Returns the title for this user
     *
     * @return string|null
     */
    public function getTitle(): ?string;

    /**
     * Sets the title for this user
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static;

    /**
     * Returns the keywords for this user
     *
     * @return string|null
     */
    public function getKeywords(): ?string;

    /**
     * Sets the keywords for this user
     *
     * @param array|string|null $keywords
     * @return static
     */
    public function setKeywords(array|string|null $keywords): static;

    /**
     * Returns the verification_code for this user
     *
     * @return string|null
     */
    public function getVerificationCode(): ?string;

    /**
     * Sets the verification_code for this user
     *
     * @param string|null $verification_code
     * @return static
     */
    public function setVerificationCode(?string $verification_code): static;

    /**
     * Returns the verified_on for this user
     *
     * @return string|null
     */
    public function getVerifiedOn(): ?string;

    /**
     * Sets the verified_on for this user
     *
     * @param string|null $verified_on
     * @return static
     */
    public function setVerifiedOn(?string $verified_on): static;

    /**
     * Returns the priority for this user
     *
     * @return int|null
     */
    public function getPriority(): ?int;

    /**
     * Sets the priority for this user
     *
     * @param int|null $priority
     * @return static
     */
    public function setPriority(?int $priority): static;

    /**
     * Returns the is_leader for this user
     *
     * @return bool
     */
    public function getIsLeader(): bool;

    /**
     * Sets the is_leader for this user
     *
     * @param int|bool|null $is_leader
     * @return static
     */
    public function setIsLeader(int|bool|null $is_leader): static;

    /**
     * Returns the leader for this user
     *
     * @return int|null
     */
    public function getLeadersId(): ?int;

    /**
     * Sets the leader for this user
     *
     * @param int|null $leaders_id
     * @return static
     */
    public function setLeadersId(?int $leaders_id): static;

    /**
     * Returns the leader for this user
     *
     * @return UserInterface|null
     */
    public function getLeader(): ?UserInterface;

    /**
     * Returns the leader for this user
     *
     * @return string|null
     */
    public function getLeadersName(): ?string;

    /**
     * Sets the leader for this user
     *
     * @param string|null $leaders_name
     * @return static
     */
    public function setLeadersName(?string $leaders_name): static;

    /**
     * Returns the latitude for this user
     *
     * @return float|null
     */
    public function getLatitude(): ?float;

    /**
     * Sets the latitude for this user
     *
     * @param float|null $latitude
     * @return static
     */
    public function setLatitude(?float $latitude): static;

    /**
     * Returns the longitude for this user
     *
     * @return float|null
     */
    public function getLongitude(): ?float;

    /**
     * Sets the longitude for this user
     *
     * @param float|null $longitude
     * @return static
     */
    public function setLongitude(?float $longitude): static;

    /**
     * Returns the accuracy for this user
     *
     * @return float|null
     */
    public function getAccuracy(): ?float;

    /**
     * Sets the accuracy for this user
     *
     * @param float|null $accuracy
     * @return static
     */
    public function setAccuracy(?float $accuracy): static;

    /**
     * Returns the offset_latitude for this user
     *
     * @return float|null
     */
    public function getOffsetLatitude(): ?float;

    /**
     * Sets the offset_latitude for this user
     *
     * @param float|null $offset_latitude
     * @return static
     */
    public function setOffsetLatitude(?float $offset_latitude): static;

    /**
     * Returns the offset_longitude for this user
     *
     * @return float|null
     */
    public function getOffsetLongitude(): ?float;

    /**
     * Sets the offset_longitude for this user
     *
     * @param float|null $offset_longitude
     * @return static
     */
    public function setOffsetLongitude(?float $offset_longitude): static;

    /**
     * Returns the redirect for this user
     *
     * @return string|null
     */
    public function getRedirect(): ?string;

    /**
     * Sets the redirect for this user
     *
     * @param string|null $redirect
     * @return static
     */
    public function setRedirect(?string $redirect = null): static;

    /**
     * Returns the gender for this user
     *
     * @return string|null
     */
    public function getGender(): ?string;

    /**
     * Sets the gender for this user
     *
     * @param string|null $gender
     * @return static
     */
    public function setGender(?string $gender): static;

    /**
     * Returns the birthdate for this user
     *
     * @return DateTimeInterface|null
     */
    public function getBirthdate(): ?DateTimeInterface;

    /**
     * Sets the birthdate for this user
     *
     * @param DateTimeInterface|string|null $birthdate
     * @return static
     */
    public function setBirthdate(DateTimeInterface|string|null $birthdate): static;

    /**
     * Sets the password for this user
     *
     * @param string $password
     * @param string $validation
     * @return static
     */
    public function setPassword(string $password, string $validation): static;

    /**
     * Validates the specified password
     *
     * @param string $password
     * @param string $validation
     * @return static
     */
    public function validatePassword(string $password, string $validation): static;

    /**
     * Returns the name for this user that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string;

    /**
     * Returns the name with an id for a user
     *
     * @return string
     */
    function getDisplayId(): string;

    /**
     * Returns the extra email addresses for this user
     *
     * @return EmailsInterface
     */
    public function getEmails(): EmailsInterface;

    /**
     * Returns the extra phones for this user
     *
     * @return PhonesInterface
     */
    public function getPhones(): PhonesInterface;

    /**
     * Returns the roles for this user
     *
     * @return RolesInterface
     */
    public function getRoles(): RolesInterface;

    /**
     * Returns the roles for this user
     *
     * @return RightsInterface
     */
    public function getRights(): RightsInterface;

    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @return bool
     */
    public function hasAllRights(array|string $rights): bool;

    /**
     * Returns an array of what rights this user misses
     *
     * @param array|string $rights
     * @return array
     */
    public function getMissingRights(array|string $rights): array;

    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @return bool
     */
    public function hasSomeRights(array|string $rights): bool;

    /**
     * Creates and returns an HTML DataEntry form
     *
     * @param string $name
     * @return DataEntryFormInterface
     */
    public function getRolesHtmlDataEntryForm(string $name = 'roles_id[]'): DataEntryFormInterface;

    /**
     * Save the user to database
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static;

    /**
     * Update this session so that it impersonates this person
     *
     * @return void
     */
    public function impersonate(): void;

    /**
     * Returns true if the current session user can impersonate this user
     *
     * A user can be impersonated if:
     * - The current session user has the right to impersonate users
     * - The target user does NOT have the "god" right
     * - The target user is not the same as the current user
     * - The current session user is not impersonated itself
     *
     * @return bool
     */
    public function canBeImpersonated(): bool;

    /**
     * Returns true if the current session user can change the status of this user
     *
     * @return bool
     */
    public function canBeStatusChanged(): bool;

    /**
     * Returns the notifications_hash for this user
     *
     * @return string|null
     */
    public function getNotificationsHash(): ?string;

    /**
     * Sets the notifications_hash for this user
     *
     * @param string|null $notifications_hash
     * @return static
     */
    public function setNotificationsHash(string|null $notifications_hash): static;

    /**
     * Send a notification to only this user.
     *
     * @return NotificationInterface
     */
    public function notify(): NotificationInterface;
}
