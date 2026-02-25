<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use DateTimeInterface;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Users\Configuration\Interfaces\ConfigurationsInterface;
use Phoundation\Accounts\Users\Locale\Language\Interfaces\PhoLocaleInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImageInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImagesInterface;
use Phoundation\Accounts\Users\Sessions\Interfaces\SessionInterface;
use Phoundation\Accounts\Users\Sessions\Interfaces\SessionStateInterface;
use Phoundation\Accounts\Users\Sessions\SessionState;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;

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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
     * @return static
     */
    public function setLastSignin(?string $last_sign_in): static;


    /**
     * Returns the update_password for this user
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatePassword(): ?DateTimeInterface;


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
     *
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
     * @param DateTimeInterface|string|null $locked_until
     *
     * @return static
     */
    public function setLockedUntil(DateTimeInterface|string|null $locked_until): static;


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
     *
     * @return static
     */
    public function setSigninCount(?int $sign_in_count): static;


    /**
     * Returns the fingerprint datetime for this user
     *
     * @return DateTimeInterface|null
     */
    public function getFingerprintObject(): ?DateTimeInterface;


    /**
     * Returns the fingerprint datetime for this user
     *
     * @return string|null
     */
    public function getFingerprint(): ?string;


    /**
     * Sets the fingerprint datetime for this user
     *
     * @param DateTimeInterface|string|int|null $fingerprint
     *
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
     * Sets the HTML title element attribute
     *
     * @param string|false|null $title            The title for this flash message
     * @param bool              $make_safe [true] If true, will make the title safe for use with HTML
     *
     * @return static
     */
    public function setTitle(string|false|null $title, bool $make_safe = true): static;

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
     *
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
     *
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
     *
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
     *
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
     *
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
     *
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
     * Returns the latitude for this user
     *
     * @return float|null
     */
    public function getLatitude(): ?float;


    /**
     * Sets the latitude for this user
     *
     * @param float|null $latitude
     *
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
     *
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
     *
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
     *
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
     *
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
     * @param UrlInterface|string|null $redirect
     *
     * @return static
     */
    public function setRedirect(UrlInterface|string|null $redirect ): static;


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
     *
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
     *
     * @return static
     */
    public function setBirthdate(DateTimeInterface|string|null $birthdate): static;


    /**
     * Sets the password for this user
     *
     * @param string $password
     * @param string $validation
     * @param bool $permit_same_password
     *
     * @return static
     */
    public function changePassword(string $password, string $validation, bool $permit_same_password = false): static;


    /**
     * Validates the specified password
     *
     * @param string $password
     * @param string $validation
     * @param bool $permit_same_password
     *
     * @return static
     */
    public function validatePassword(string $password, string $validation, bool $permit_same_password = false): static;


    /**
     * Returns the name for this user that can be displayed
     *
     * @param bool $official
     * @param bool $clean
     * @param bool $reverse
     *
     * @return string|null
     */
    function getDisplayName(bool $official = false, bool $clean = false, bool $reverse = false): ?string;


    /**
     * Returns the name with an id for a user
     *
     * @return string|null
     */
    function getDisplayId(): ?string;


    /**
     * Returns the extra email addresses for this user
     *
     * @return EmailsInterface
     */
    public function getEmailsObject(): EmailsInterface;


    /**
     * Returns the extra phones for this user
     *
     * @return PhonesInterface
     */
    public function getPhonesObject(): PhonesInterface;


    /**
     * Returns the roles for this user
     *
     * @return RolesInterface
     */
    public function getRolesObject(): RolesInterface;


    /**
     * Returns the roles for this user
     *
     * @return RightsInterface
     */
    public function getRightsObject(): RightsInterface;


    /**
     * Creates and returns an HTML DataEntry form
     *
     * @param string $name
     *
     * @return DataEntryFormInterface
     */
    public function getRolesHtmlDataEntryFormObject(string $name = 'roles_id[]'): DataEntryFormInterface;


    /**
     * Save the user to database
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;


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
     *
     * @return static
     */
    public function setNotificationsHash(?string $notifications_hash): static;


    /**
     * Returns a NotificationInterface object that can be used to send a notification to only this user.
     *
     * Will return NULL if notifications_enabled is false
     *
     * @return NotificationInterface|null
     */
    public function notify(): ?NotificationInterface;


    /**
     * Returns the password string for this user
     *
     * @return string|null
     */
    public function getPassword(): ?string;


    /**
     * Returns the session for this user
     *
     * @return SessionInterface
     */
    public function getSessionObject(): SessionInterface;


    /**
     * Returns the remote_id for this user
     *
     * @return int|null
     */
    public function getRemoteId(): ?int;


    /**
     * Sets the remote_id for this user
     *
     * @param int|null $remote_id
     *
     * @return static
     */
    public function setRemoteId(?int $remote_id): static;


    /**
     * Returns the remote user for this user
     *
     * @param string      $class
     * @param string|null $column
     *
     * @return UserInterface|null
     */
    public function getRemoteUserObject(string $class, ?string $column = null): ?UserInterface;


    /**
     * Sets the remote user for this user
     *
     * @param UserInterface|null $remote_user
     *
     * @return static
     */
    public function setRemoteUserObject(?UserInterface $remote_user): static;


    /**
     * Lock this user account
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function lock(?string $comments = null): static;


    /**
     * Unlock this user account
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function unlock(?string $comments = null): static;


    /**
     * Returns true if this user account is locked
     *
     * @return bool
     */
    public function isLocked(): bool;

    /**
     * Returns the profile image for this user
     *
     * @return ProfileImageInterface
     */
    public function getProfileImageObject(): ProfileImageInterface;

    /**
     * Sets the profile image for this user
     *
     * @param ProfileImageInterface|string|null $profile_image
     *
     * @return static
     */
    public function setProfileImageObject(ProfileImageInterface|string|null $profile_image): static;

    /**
     * Returns the list of profile images for this user
     *
     * @return ProfileImagesInterface
     */
    public function getProfileImagesObject(): ProfileImagesInterface;

    /**
     * Returns true if this user has the specified password
     *
     * @param string $password
     *
     * @return bool
     */
    public function hasPassword(string $password): bool;

    /**
     * Returns if this user can receive notifications or if notifications for this user will be dropped
     *
     * @return bool
     */
    public function getNotificationsEnabled(): bool;

    /**
     * Sets if this user can receive notifications or if notifications for this user will be dropped
     *
     * @param bool $enabled
     *
     * @return static
     */
    public function setNotificationsEnabled(bool $enabled): static;

    /**
     * Returns the email for this object
     *
     * @return string|null
     */
    public function getEmail(): ?string;

    /**
     * Sets the email for this object
     *
     * @param string|null $email
     *
     * @return static
     */
    public function setEmail(?string $email): static;

    /**
     * Update the MFA code (and optionally the timeslice) for this user
     *
     * @param string   $code
     * @param int|null $timeslice
     *
     * @return static
     */
    public function updateMfaCode(string $code, ?int $timeslice): static;

    /**
     * Update only the MFA timeslice for this user
     *
     * @param int|null $timeslice
     *
     * @return static
     */
    public function updateMfaTimeslice(?int $timeslice): static;

    /**
     * Returns the "configurations" object for this user
     *
     * @return ConfigurationsInterface
     */
    public function getConfigurationsObject(): ConfigurationsInterface;

    /**
     * Returns a Locale object for this user
     *
     * @return PhoLocaleInterface
     */
    public function getLocaleObject(): PhoLocaleInterface;

    /**
     * Returns the SessionState object for this user
     *
     * @return SessionStateInterface
     */
    public function getSessionStateObject(): SessionStateInterface;

    /**
     * Returns the session_state for this user
     *
     * @return string|null
     */
    public function getSessionState(): ?string;

    /**
     * Sets the session_state for this user
     *
     * @param string|null $session_state
     *
     * @return static
     */
    public function setSessionState(?string $session_state): static;

    /**
     * Sends a welcome email to the user
     *
     * @return static
     */
    public function sendWelcomeEmail(): static;

    /**
     * Returns true if this user has any redirect URL other than NULL
     *
     * @return bool
     */
    public function hasRedirect(): bool;

    /**
     * Returns true if this user has the specified redirect URL
     *
     * @param UrlInterface|null $_redirect [null] The URL that should match the redirect URL for this user
     *
     * @return bool
     */
    public function hasSpecifiedRedirect(?UrlInterface $_redirect): bool;

    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasSomeRights(array|string $rights, ?string $always_match = 'god'): bool;

    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasAllRights(array|string $rights, ?string $always_match = 'god'): bool;
}
