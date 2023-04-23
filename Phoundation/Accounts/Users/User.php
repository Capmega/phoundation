<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Passwords;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\PasswordNotChangedException;
use Phoundation\Accounts\Users\Exception\UsersException;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryAddress;
use Phoundation\Data\DataEntry\Traits\DataEntryCode;
use Phoundation\Data\DataEntry\Traits\DataEntryComments;
use Phoundation\Data\DataEntry\Traits\DataEntryDomain;
use Phoundation\Data\DataEntry\Traits\DataEntryEmail;
use Phoundation\Data\DataEntry\Traits\DataEntryGeo;
use Phoundation\Data\DataEntry\Traits\DataEntryLanguage;
use Phoundation\Data\DataEntry\Traits\DataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\DataEntryPhones;
use Phoundation\Data\DataEntry\Traits\DataEntryPicture;
use Phoundation\Data\DataEntry\Traits\DataEntryTimezone;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Date\DateTime;
use Phoundation\Exception\NotSupportedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Geo\Cities\Cities;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\States\States;
use Phoundation\Geo\Timezones\Timezone;
use Phoundation\Geo\Timezones\Timezones;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Html\Components\Form;
use Phoundation\Web\Http\UrlBuilder;


/**
 * Class User
 *
 * This is the default user class.
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class User extends DataEntry
{
    use DataEntryGeo;
    use DataEntryUrl;
    use DataEntryCode;
    use DataEntryType;
    use DataEntryEmail;
    use DataEntryPhones;
    use DataEntryDomain;
    use DataEntryPicture;
    use DataEntryAddress;
    use DataEntryLanguage;
    use DataEntryComments;
    use DataEntryTimezone;
    use DataEntryNameDescription;



    /**
     * The roles for this user
     *
     * @var Roles $roles
     */
    protected Roles $roles;

    /**
     * The rights for this user
     *
     * @var Rights $rights
     */
    protected Rights $rights;

    /**
     * Columns that will NOT be inserted
     *
     * @var array $fields_filter_on_insert
     */
    protected array $fields_filter_on_insert = ['id', 'password'];



    /**
     * User class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$entry_name = 'user';
        $this->unique_field = 'email';
        $this->table        = 'accounts_users';

        parent::__construct($identifier);
    }



    /**
     * Returns id for this user entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        $id = $this->getDataValue('id');

        if (!$id) {
            // This is a guest user
            return tr('Guest');
        }

        return $id . ' / ' . $this->getDataValue($this->unique_field);
    }



    /**
     * Authenticates the specified user id / email with its password
     *
     * @param string|int $identifier
     * @param string $password
     * @param string|null $domain
     * @return static
     */
    public static function authenticate(string|int $identifier, string $password, ?string $domain = null): static
    {
        return self::doAuthenticate($identifier, $password, $domain);
    }



    /**
     * Returns true if the specified password matches the users password
     *
     * @param string $password
     * @return bool
     */
    public function passwordMatch(string $password): bool
    {
        if (!array_key_exists('id', $this->data)) {
            throw new OutOfBoundsException(tr('Cannot match passwords, this user does not have a database id'));
        }

        return Passwords::match($this->data['id'], $password, $this->data['password']);
    }



    /**
     * Authenticates the specified user id / email with its password
     *
     * @param string $key
     * @return static
     */
    public static function authenticateKey(string $key): static
    {
        // Return the user that has this API key
        return Users::getUserFromApiKey($key);
    }



    /**
     * Returns true if this user object is the guest user
     *
     * @return bool
     */
    public function isGuest(): bool
    {
        return !isset_get($this->data['id']);
    }



    /**
     * Returns the nickname for this user
     *
     * @return string|null
     */
    public function getNickname(): ?string
    {
        return $this->getDataValue('nickname');
    }



    /**
     * Sets the nickname for this user
     *
     * @param string|null $nickname
     * @return static
     */
    public function setNickname(?string $nickname): static
    {
        return $this->setDataValue('nickname', $nickname);
    }



    /**
     * Returns the name for this user
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return trim($this->getDataValue('first_names') . ' ' . $this->getDataValue('last_names'));
    }



    /**
     * Sets the name for this user
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static
    {
        throw new NotSupportedException(tr('The Accounts\User class does not support the User::setName() method. Please use User::setFirstNames() and User::setLastNames() instead'));
    }



    /**
     * Returns the first_names for this user
     *
     * @return string|null
     */
    public function getFirstNames(): ?string
    {
        return $this->getDataValue('first_names');
    }



    /**
     * Sets the first_names for this user
     *
     * @param string|null $first_names
     * @return static
     */
    public function setFirstNames(?string $first_names): static
    {
        return $this->setDataValue('first_names', $first_names);
    }



    /**
     * Returns the last_names for this user
     *
     * @return string|null
     */
    public function getLastNames(): ?string
    {
        return $this->getDataValue('last_names');
    }



    /**
     * Sets the last_names for this user
     *
     * @param string|null $lastnames
     * @return static
     */
    public function setLastNames(?string $lastnames): static
    {
        return $this->setDataValue('last_names', $lastnames);
    }



    /**
     * Returns the username for this user
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getDataValue('username');
    }



    /**
     * Sets the username for this user
     *
     * @param string|null $username
     * @return static
     */
    public function setUsername(?string $username): static
    {
        return $this->setDataValue('username', $username);
    }



    /**
     * Returns the last_sign_in for this user
     *
     * @return string|null
     */
    public function getLastSignin(): ?string
    {
        return $this->getDataValue('last_sign_in');
    }



    /**
     * Sets the last_sign_in for this user
     *
     * @param string|null $last_sign_in
     * @return static
     */
    public function setLastSignin(?string $last_sign_in): static
    {
        return $this->setDataValue('last_sign_in', $last_sign_in);
    }



    /**
     * Returns the authentication_failures for this user
     *
     * @return string|null
     */
    public function getAuthenticationFailures(): ?string
    {
        return $this->getDataValue('authentication_failures');
    }



    /**
     * Sets the authentication_failures for this user
     *
     * @param int|null $authentication_failures
     * @return static
     */
    public function setAuthenticationFailures(?int $authentication_failures): static
    {
        return $this->setDataValue('authentication_failures', (int) $authentication_failures);
    }



    /**
     * Returns the locked_until for this user
     *
     * @return string|null
     */
    public function getLockedUntil(): ?string
    {
        return $this->getDataValue('locked_until');
    }



    /**
     * Sets the locked_until for this user
     *
     * @param string|null $locked_until
     * @return static
     */
    public function setLockedUntil(?string $locked_until): static
    {
        return $this->setDataValue('locked_until', $locked_until);
    }



    /**
     * Returns the sign_in_count for this user
     *
     * @return string|null
     */
    public function getSigninCount(): ?string
    {
        return $this->getDataValue('sign_in_count');
    }



    /**
     * Sets the sign_in_count for this user
     *
     * @param int|null $sign_in_count
     * @return static
     */
    public function setSigninCount(?int $sign_in_count): static
    {
        return $this->setDataValue('sign_in_count', $sign_in_count);
    }



    /**
     * Returns the fingerprint datetime for this user
     *
     * @return DateTime|null
     */
    public function getFingerprint(): ?DateTime
    {
        $fingerprint = $this->getDataValue('fingerprint');
        return new DateTime($fingerprint);
    }



    /**
     * Sets the fingerprint datetime for this user
     *
     * @param DateTime|string|int|null $fingerprint
     * @return static
     */
    public function setFingerprint(DateTime|string|int|null $fingerprint): static
    {
        if ($fingerprint) {
            if (!is_object($fingerprint)) {
                $fingerprint = new DateTime($fingerprint);
            }

            return $this->setDataValue('fingerprint', $fingerprint->format('Y-m-d H:i:s'));
        }

        return $this->setDataValue('fingerprint', null);
    }



    /**
     * Returns the title for this user
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getDataValue('title');
    }



    /**
     * Sets the title for this user
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        return $this->setDataValue('title', $title);
    }



    /**
     * Returns the keywords for this user
     *
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->getDataValue('keywords');
    }



    /**
     * Sets the keywords for this user
     *
     * @param array|string|null $keywords
     * @return static
     */
    public function setKeywords(array|string|null $keywords): static
    {
        return $this->setDataValue('keywords', Strings::force($keywords, ', '));
    }



    /**
     * Returns the verification_code for this user
     *
     * @return string|null
     */
    public function getVerificationCode(): ?string
    {
        return $this->getDataValue('verification_code');
    }



    /**
     * Sets the verification_code for this user
     *
     * @param string|null $verification_code
     * @return static
     */
    public function setVerificationCode(?string $verification_code): static
    {
        return $this->setDataValue('verification_code', $verification_code);
    }



    /**
     * Returns the verified_on for this user
     *
     * @return string|null
     */
    public function getVerifiedOn(): ?string
    {
        return $this->getDataValue('verified_on');
    }



    /**
     * Sets the verified_on for this user
     *
     * @param string|null $verified_on
     * @return static
     */
    public function setVerifiedOn(?string $verified_on): static
    {
        return $this->setDataValue('verified_on', $verified_on);
    }



    /**
     * Returns the priority for this user
     *
     * @return string|null
     */
    public function getPriority(): ?string
    {
        return $this->getDataValue('priority');
    }



    /**
     * Sets the priority for this user
     *
     * @param int|null $priority
     * @return static
     */
    public function setPriority(?int $priority): static
    {
        return $this->setDataValue('priority', $priority);
    }



    /**
     * Returns the is_leader for this user
     *
     * @return bool
     */
    public function getIsLeader(): bool
    {
        return $this->getDataValue('is_leader');
    }



    /**
     * Sets the is_leader for this user
     *
     * @param bool|null $is_leader
     * @return static
     */
    public function setIsLeader(?bool $is_leader): static
    {
        return $this->setDataValue('is_leader', (bool) $is_leader);
    }



    /**
     * Returns the leader for this user
     *
     * @return int|null
     */
    public function getLeadersId(): ?int
    {
        return $this->getDataValue('leaders_id');
    }



    /**
     * Sets the leader for this user
     *
     * @param int|null $leaders_id
     * @return static
     */
    public function setLeadersId(int|null $leaders_id): static
    {
        return $this->setDataValue('leaders_id', $leaders_id);
    }



    /**
     * Returns the leader for this user
     *
     * @return User|null
     */
    public function getLeader(): ?User
    {
        $leaders_id = $this->getDataValue('leaders_id');

        if ($leaders_id === null) {
            return null;
        }

        return new User($leaders_id);
    }



    /**
     * Sets the leader for this user
     *
     * @param User|string|int|null $leaders_id
     * @return static
     */
    public function setLeader(User|string|int|null $leaders_id): static
    {
        if (!is_numeric($leaders_id)) {
            $leaders_id = User::get($leaders_id);
        }

        if (is_object($leaders_id)) {
            $leaders_id = $leaders_id->getId();
        }

        return $this->setDataValue('leaders_id', $leaders_id);
    }



    /**
     * Returns the latitude for this user
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->getDataValue('latitude');
    }



    /**
     * Sets the latitude for this user
     *
     * @param float|null $latitude
     * @return static
     */
    public function setLatitude(?float $latitude): static
    {
        return $this->setDataValue('latitude', $latitude);
    }



    /**
     * Returns the longitude for this user
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->getDataValue('longitude');
    }



    /**
     * Sets the longitude for this user
     *
     * @param float|null $longitude
     * @return static
     */
    public function setLongitude(?float $longitude): static
    {
        return $this->setDataValue('longitude', $longitude);
    }



    /**
     * Returns the accuracy for this user
     *
     * @return int|null
     */
    public function getAccuracy(): ?int
    {
        return $this->getDataValue('accuracy');
    }



    /**
     * Sets the accuracy for this user
     *
     * @param int|null $accuracy
     * @return static
     */
    public function setAccuracy(?int $accuracy): static
    {
        return $this->setDataValue('accuracy', $accuracy);
    }



    /**
     * Returns the offset_latitude for this user
     *
     * @return float|null
     */
    public function getOffsetLatitude(): ?float
    {
        return $this->getDataValue('offset_latitude');
    }



    /**
     * Sets the offset_latitude for this user
     *
     * @param float|null $offset_latitude
     * @return static
     */
    public function setOffsetLatitude(?float $offset_latitude): static
    {
        return $this->setDataValue('offset_latitude', $offset_latitude);
    }



    /**
     * Returns the offset_longitude for this user
     *
     * @return float|null
     */
    public function getOffsetLongitude(): ?float
    {
        return $this->getDataValue('offset_longitude');
    }



    /**
     * Sets the offset_longitude for this user
     *
     * @param float|null $offset_longitude
     * @return static
     */
    public function setOffsetLongitude(?float $offset_longitude): static
    {
        return $this->setDataValue('offset_longitude', $offset_longitude);
    }



    /**
     * Returns the redirect for this user
     *
     * @return string|null
     */
    public function getRedirect(): ?string
    {
        return $this->getDataValue('redirect');
    }



    /**
     * Sets the redirect for this user
     *
     * @param string|null $redirect
     * @return static
     */
    public function setRedirect(?string $redirect = null): static
    {
        if ($redirect) {
            // Ensure we have a valid redirect URL
            $redirect = UrlBuilder::getWww($redirect);
        }

        return $this->setDataValue('redirect', get_null($redirect));
    }



    /**
     * Returns the gender for this user
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getDataValue('gender');
    }


    /**
     * Sets the gender for this user
     *
     * @param string|null $gender
     * @return static
     */
    public function setGender(?string $gender): static
    {
        return $this->setDataValue('gender', $gender);
    }


    /**
     * Returns the birthdate for this user
     *
     * @return DateTime|null
     */
    public function getBirthday(): ?DateTime
    {
        $birthdate = $this->getDataValue('birthdate');

        if ($birthdate ) {
            return new DateTime($birthdate);
        }

        return null;
    }


    /**
     * Sets the birthdate for this user
     *
     * @param string|null $birthdate
     * @return static
     */
    public function setBirthday(?string $birthdate): static
    {
        return $this->setDataValue('birthdate', $birthdate);
    }


    /**
     * Sets the password for this user
     *
     * @param string $password
     * @param string $validation
     * @return static
     */
    public function setPassword(string $password, string $validation): static
    {
        $password   = trim($password);
        $validation = trim($validation);

        $this->validatePassword($password, $validation);
        $this->setPasswordDirectly(Passwords::hash($password, $this->data['id']));

        return $this->savePassword();
    }



    /**
     * Sets the password for this user
     *
     * @param string $password
     * @return static
     */
    protected function setPasswordDirectly(string $password): static
    {
        return $this->setDataValue('password', $password);
    }



    /**
     * Validates the specified password
     *
     * @param string $password
     * @param string $validation
     * @return static
     */
    public function validatePassword(string $password, string $validation): static
    {
        $password   = trim($password);
        $validation = trim($validation);

        if (!$password) {
            throw new ValidationFailedException(tr('No password specified'));
        }

        if (!$validation) {
            throw new ValidationFailedException(tr('No validation password specified'));
        }

        if ($password !== $validation) {
            throw new ValidationFailedException(tr('The password must match the validation password'));
        }

        if (empty($this->data['id'])) {
            throw new OutOfBoundsException(tr('Cannot set password for this user, it has not been saved yet'));
        }

        if (empty($this->data['email'])) {
            throw new OutOfBoundsException(tr('Cannot set password for this user, it has no email address'));
        }

        // Is the password secure?
        Passwords::testSecurity($password, $this->data['email'], $this->data['id']);

        // Is the password not the same as the current password?
        try {
            static::doAuthenticate($this->data['email'], $password, isset_get($this->data['domain']), true);
            throw new PasswordNotChangedException(tr('The specified password is the same as the current password'));

        } catch (AuthenticationException) {
            // This password is new, yay! We can continue;
        }

        return $this;
    }



    /**
     * Returns the name for this user that can be displayed
     *
     * @return string
     */
    function getDisplayName(): string
    {
        if ($name = $this->getNickname()) {
            return $name;
        }

        if ($name = $this->getName()) {
            return $name;
        }

        if ($name = $this->getUsername()) {
            return $name;
        }

        if ($name = $this->getEmail()) {
            return $name;
        }

        if ($name = $this->getId()) {
            if ($this->getId() === -1) {
                // This is a guest user
                return tr('Guest');
            }

            return $name;
        }

        // This is a new user
        return tr('[NEW]');
    }



    /**
     * Returns the name with an id for a user
     *
     * @return string
     */
    function getDisplayId(): string
    {
        return $this->getDataValue('id') . ' / ' . $this->getDisplayName();
    }



    /**
     * Returns the roles for this user
     *
     * @return Roles
     */
    public function roles(): Roles
    {
        if (!isset($this->roles)) {
            if (!$this->getId()) {
                throw new OutOfBoundsException(tr('Cannot access user roles without saving user first'));
            }

            $this->roles = Roles::new($this);
        }

        return $this->roles;
    }



    /**
     * Returns the roles for this user
     *
     * @return Rights
     */
    public function rights(): Rights
    {
        if (!isset($this->rights)) {
            $this->rights = Rights::new($this, 'seo_name');
        }

        return $this->rights;
    }



    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @return bool
     */
    public function hasAllRights(array|string $rights): bool
    {
        if (!$rights) {
            return true;
        }

        return $this->rights()->containsKey($rights, true, 'god');
    }



    /**
     * Returns an array of what rights this user misses
     *
     * @param array|string $rights
     * @return array
     */
    public function getMissingRights(array|string $rights): array
    {
        if (!$rights) {
            return [];
        }

        return $this->rights()->missesKeys($rights, true, 'god');
    }



    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @return bool
     */
    public function hasSomeRights(array|string $rights): bool
    {
        if (!$rights) {
            return true;
        }

        return $this->rights()->containsKey($rights, false, 'god');
    }



    /**
     * Creates and returns an HTML for the fir
     *
     * @return Form
     */
    public function getRolesHtmlForm(): Form
    {
        $form   = Form::new();
        $roles  = $this->roles();
        $select = $roles->getHtmlSelect()->setCache(true);

        // Add extra entry with nothing selected
        $select->clearSelected();
        $form->addContent($select->render() . '<br>');

        // Add all current roles
        foreach ($roles as $role) {
            $select->setSelected($role->getSeoName());
            $form->addContent($select->render() . '<br>');
        }

        return $form;
    }



    /**
     * Save the user to database
     *
     * @param string|null $comments
     * @return static
     */
    public function save(?string $comments = null): static
    {
        Log::action(tr('Saving user ":user"', [':user' => $this->getDisplayName()]));

        // Can this information be changed? If this user has god right, the executing user MUST have god right as well!
        if ($this->hasAllRights('god')) {
            if (PLATFORM_HTTP and !Session::getUser()->hasAllRights('god')) {
                // Oops...
                Incident::new()
                    ->setType('Blocked user update')
                    ->setSeverity(Severity::severe)
                    ->setTitle(tr('The user ":user" attempted to modify god level user ":modify" without having the "god" right itself.', [
                        ':modify' => $this,
                        ':user'   => Session::getUser(),
                    ]))
                    ->setDetails([
                        ':modify' => $this,
                        ':user'   => Session::getUser(),
                    ])
                    ->save()
                    ->throw();
            }
        }

        parent::save();

        $meta_id = $this->getMeta()?->getId();

        if ($meta_id) {
            Incident::new()
                ->setType('User information changed')
                ->setSeverity(Severity::low)
                ->setTitle(tr('The user ":user" was modified, see audit ":meta_id" for more information', [
                    ':user'    => $this->getLogId(),
                    ':meta_id' => $meta_id
                ]))
                ->setDetails([
                    ':user' => $this->getLogId(),
                ])
                ->save();

        } else {
            Incident::new()
                ->setType('User information changed')
                ->setSeverity(Severity::low)
                ->setTitle(tr('The user ":user" was created', [
                    ':user' => $this->getLogId()
                ]))
                ->setDetails([
                    ':user' => $this->getLogId(),
                ])
                ->save();
        }

        return $this;
    }



    /**
     * Update this session so that it impersonates this person
     *
     * @return void
     */
    public function impersonate(): void
    {
        Session::impersonate($this);
    }



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
    public function canBeImpersonated(): bool
    {
        if (!Session::isImpersonated()) {
            if (Session::getUser()->hasAllRights('impersonate')) {
                // We must have the right and we cannot impersonate ourselves
                if ($this->getId() !== Session::getUser()->getId()) {
                    if (!$this->hasAllRights('god')) {
                        return true;
                    }
                }
            }
        }

        return false;
    }



    /**
     * Returns true if the current session user can change the status of this user
     *
     * @return bool
     */
    public function canBeStatusChanged(): bool
    {
        // We must have the right and we cannot impersonate ourselves
        if ($this->getId() !== Session::getUser()->getId()) {
            if (!$this->hasAllRights('god')) {
                return true;
            }
        }

        return false;
    }


    /**
     * Validates the DataEntry record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('username'), true)->isOptional()->hasMaxCharacters(64)->isName()
            ->select($this->getAlternateValidationField('domain'), true)->isOptional()->hasMaxCharacters(128)->isDomain()
            ->select($this->getAlternateValidationField('title'), true)->isOptional()->hasMaxCharacters(24)->isName()
            ->select($this->getAlternateValidationField('first_names'), true)->isOptional()->hasMaxCharacters(127)->isName()
            ->select($this->getAlternateValidationField('last_names'), true)->isOptional()->hasMaxCharacters(127)->isName()
            ->select($this->getAlternateValidationField('nickname'), true)->isOptional()->hasMaxCharacters(64)->isName()
            ->select($this->getAlternateValidationField('email'), true)->hasMaxCharacters(128)->isEmail()
            ->select($this->getAlternateValidationField('type'), true)->isOptional()->hasMaxCharacters(16)->isName()
            ->select($this->getAlternateValidationField('code'), true)->isOptional()->hasMaxCharacters(16)->isCode()
            ->select($this->getAlternateValidationField('keywords'), true)->isOptional()->hasMaxCharacters(255)->isPrintable()
            ->select($this->getAlternateValidationField('phones'), true)->isOptional()->hasMinCharacters(10)->hasMaxCharacters(64)
//                    ->select('keywords')->isOptional()->hasMaxCharacters(255)->sanitizeForceArray(' ')->each()->isWord()->sanitizeForceString()
//                    ->select('phones')->isOptional()->hasMinCharacters(10)->hasMaxCharacters(64)->sanitizeForceArray(',')->each()->isPhone()->sanitizeForceString()
            ->select($this->getAlternateValidationField('address'), true)->isOptional()->hasMaxCharacters(255)->isPrintable()
            ->select($this->getAlternateValidationField('zipcode'), true)->isOptional()->hasMinCharacters(4)->hasMaxCharacters(8)->isPrintable()
            ->select($this->getAlternateValidationField('priority'), true)->isOptional()->isNatural()->isBetween(1, 10)
            ->select($this->getAlternateValidationField('is_leader'))->isOptional()->isBoolean()
            ->select($this->getAlternateValidationField('latitude'), true)->isOptional()->isLatitude()
            ->select($this->getAlternateValidationField('longitude'), true)->isOptional()->isLongitude()
            ->select($this->getAlternateValidationField('accuracy'), true)->isOptional()->isFloat()->isBetween(0, 10)
            ->select($this->getAlternateValidationField('gender'), true)->isOptional()->inArray(['unknown', 'male', 'female', 'other'])
            ->select($this->getAlternateValidationField('birthdate'), true)->isOptional()->isDate()
            ->select($this->getAlternateValidationField('description'), true)->isOptional()->hasMaxCharacters(65_530)->isPrintable()
            ->select($this->getAlternateValidationField('comments'), true)->isOptional()->hasMaxCharacters(16_777_200)->isPrintable()
            ->select($this->getAlternateValidationField('url'), true)->isOptional()->hasMaxCharacters(2048)->isUrl()
            ->select($this->getAlternateValidationField('redirect'), true)->isOptional()->hasMaxCharacters(255)->isUrl()
            ->select($this->getAlternateValidationField('timezone'), true)->isOptional()->isTimezone()
            ->select($this->getAlternateValidationField('leader'), true)->or('leaders_id')->hasMaxCharacters(255)->isName()->isQueryColumn    ('SELECT `email` FROM `accounts_users` WHERE `email` = :email AND `status` IS NULL', [':email' => '$leader'])
            ->select($this->getAlternateValidationField('language'), true)->or('languages_id')->hasMaxCharacters(32)->isName()->isQueryColumn ('SELECT `code_639_1` FROM `core_languages` WHERE `code_639_1` = :code AND `status` IS NULL', [':code' => '$language'])
            ->select($this->getAlternateValidationField('country'), true)->or('countries_id')->hasMaxCharacters(200)->isName()->isQueryColumn ('SELECT `name` FROM `geo_countries` WHERE `name` = :name AND `status` IS NULL', [':name' => '$country'])
            ->select($this->getAlternateValidationField('state'), true)->or('states_id')->hasMaxCharacters(200)->isName()->isQueryColumn      ('SELECT `name` FROM `geo_states`    WHERE `name` = :name AND `countries_id` = :countries_id AND `status` IS NULL', [':name' => '$state'    , ':countries_id' => '$countries_id'])
            ->select($this->getAlternateValidationField('city'), true)->or('cities_id')->hasMaxCharacters(200)->isName()->isQueryColumn       ('SELECT `name` FROM `geo_cities`    WHERE `name` = :name AND `states_name`  = :states_id    AND `status` IS NULL', [':name' => '$city'     , ':states_id'    => '$states_id'])
            ->select($this->getAlternateValidationField('leaders_id'), true)->or('leader')->isId()->isQueryColumn      ('SELECT `id` FROM `core_languages`     WHERE `id` = :id AND `status` IS NULL', [':id'   => '$language'])
            ->select($this->getAlternateValidationField('languages_id'), true)->or('language')->isId()->isQueryColumn  ('SELECT `id` FROM `core_languages`     WHERE `id` = :id AND `status` IS NULL', [':id' => '$languages_id'])
            ->select($this->getAlternateValidationField('countries_id'), true)->or('country')->isId()->isQueryColumn   ('SELECT `id` FROM `geo_countries` WHERE `id` = :id AND `status` IS NULL', [':id' => '$countries_id'])
            ->select($this->getAlternateValidationField('states_id'), true)->or('state')->isId()->isQueryColumn        ('SELECT `id` FROM `geo_states`    WHERE `id` = :id AND `countries_id` = :countries_id AND `status` IS NULL', [':id'   => '$states_id', ':countries_id' => '$countries_id'])
            ->select($this->getAlternateValidationField('cities_id'), true)->or('city')->isId()->isQueryColumn         ('SELECT `id` FROM `geo_cities`    WHERE `id` = :id AND `states_name`  = :states_id    AND `status` IS NULL', [':id'   => '$cities_id', ':states_id'    => '$states_id'])
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        // Ensure the email doesn't exist yet as it is a unique identifier
        if ($data['email']) {
            static::notExists($data['email'], $this->getId(), true);
        }

        return $data;
    }


    /**
     * Save the password for this user
     *
     * @return static
     */
    protected function savePassword(): static
    {
        if (empty($this->data['id'])) {
            throw new UsersException(tr('Cannot save password, this user does not have an id'));
        }

        sql()->query('UPDATE `accounts_users` SET `password` = :password WHERE `id` = :id', [
            ':id'       => $this->data['id'],
            ':password' => $this->data['password']
        ]);

        return $this;
    }


    /**
     * Authenticates the specified user id / email with its password
     *
     * @param string|int $identifier
     * @param string $password
     * @param string|null $domain
     * @param bool $test
     * @return static
     */
    protected static function doAuthenticate(string|int $identifier, string $password, ?string $domain = null, bool $test = false): static
    {
        $user = User::get($identifier);

        if ($user->passwordMatch($password)) {
            if ($user->getDomain()) {
                // User is limited to a domain!

                if (!$domain) {
                    $domain = Domains::getCurrent();
                }

                if ($user->getDomain() !== $domain) {
                    if (!$test) {
                        Incident::new()
                            ->setType('Domain access disallowed')
                            ->setSeverity(Severity::medium)
                            ->setTitle(tr('The user ":user" is not allowed to have access to domain ":domain"', [
                                ':user'   => $user,
                                ':domain' => $domain
                            ]))
                            ->setDetails([':user' => $user, ':domain' => $domain])
                            ->save();
                    }

                    throw new AuthenticationException(tr('The specified user ":user" is not allowed to access the domain ":domain"', [
                        ':user'   => $identifier,
                        ':domain' => $domain
                    ]));
                }
            }

            return $user;
        }

        if (!$test) {
            Incident::new()
                ->setType('Incorrect password')->setSeverity(Severity::low)
                ->setTitle(tr('The specified password for user ":user" is incorrect', [':user' => $user]))
                ->setDetails([':user' => $user])
                ->save();
        }

        throw new AuthenticationException(tr('The specified password did not match for user ":user"', [
            ':user' => $identifier
        ]));
    }



    /**
     * Sets the available data keys for the User class
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        return [
            'country' => [
                'virtual'  => true,
                'complete' => [
                    'word'   => function($word) { return Countries::new()->filteredList($word); },
                    'noword' => function()      { return Countries::new()->list(); },
                ],
                'cli'      => '--country COUNTRY NAME',
            ],
            'state' => [
                'virtual'  => true,
                'complete' => [
                    'word'   => function($word) { return States::new()->filteredList($word); },
                    'noword' => function()      { return States::new()->list(); },
                ],
                'cli'      => '--state STATE-NAME',
            ],
            'city' => [
                'virtual'  => true,
                [
                    'word'   => function($word) { return Cities::new()->filteredList($word); },
                    'noword' => function()      { return Cities::new()->list(); },
                ],
                'cli'      => '--city CITY-NAME',
            ],
            'language' => [
                'virtual'  => true,
                'complete' => [
                    'word'   => function($word) { return Languages::new()->filteredList($word); },
                    'noword' => function()      { return Languages::new()->list(); },
                ],
                'cli'      => '-l,--language LANGUAGE-NAME',
            ],
            'leader' => [
                'virtual'  => true,
                'complete' => [
                    'word'   => function($word) { return Users::new()->filterby('is_leader', true)->filteredList($word); },
                    'noword' => function()      { return Users::new()->filterby('is_leader', true)->list(); },
                ],
                'cli'      => '--leader LEADER-EMAIL',
            ],
            'timezone' => [
                'virtual'  => true,
                'complete' => [
                    'word'   => function($word) { return Timezones::new()->filteredList($word); },
                    'noword' => function()      { return Timezones::new()->list(); },
                ],
                'cli'      => '--timezone TIMEZONE-NAME',
            ],
            'timezones_id' => [
                'element' => function (string $key, array $data, array $source) {
                    return Timezones::getHtmlSelect($key)
                        ->setSelected(isset_get($source['timezones_id']))
                        ->render();
                },
                'cli'        => '--timezones-id',
                'complete'   => true,
                'size'       => 3,
                'label'      => tr('Timezone'),
                'help_group' => tr('Location information'),
                'help'       => tr('The timezone where this user resides'),
            ],
            'picture' => [
                'visible'  => false,
                'complete' => true,
                'readonly' => true
            ],
            'verification_code' => [
                'visible'  => false,
                'readonly' => true
            ],
            'fingerprint' => [
                // TODO Implement
                'visible'  => false,
                'readonly' => true
            ],
            'password' => [
                'visible'    => false,
                'complete'   => true,
                'readonly'   => true,
                'type'       => 'password',
                'maxlength'  => 64,
                'db_null'    => false,
                'help_group' => tr(''),
                'help'       => tr('The password for this user'),
            ],
            'last_sign_in' => [
                'type'      => 'datetime-local',
                'readonly'  => true,
                'null_type' => 'text',
                'size'      => 3,
                'default'   => '-',
                'cli'       => '--type',
                'label'     => tr('Last sign in')
            ],
            'sign_in_count' => [
                'type'       => 'numeric',
                'readonly'   => true,
                'db_null'    => false,
                'db_default' => 0,
                'size'       => 3,
                'default'    => 0,
                'label'      => tr('Sign in count')
            ],
            'authentication_failures' => [
                'type'       => 'numeric',
                'readonly'   => true,
                'db_null'    => false,
                'db_default' => 0,
                'size'       => 3,
                'default'    => 0,
                'label'      => tr('Authentication failures')
            ],
            'locked_until' => [
                'type'      => 'datetime-local',
                'readonly'  => true,
                'null_type' => 'text',
                'size'      => 3,
                'default'   => tr('Not locked'),
                'label'     => tr('Locked until')
            ],
            'email' => [
                'required'   => true,
                'maxlength'  => 128,
                'type'       => 'email',
                'cli'        => '-e,--email',
                'complete'   => true,
                'label'      => tr('Email address'),
                'help_group' => tr('Personal information'),
                'help'       => tr('The email address for this user. This is also the unique identifier for the user'),
            ],
            'domain' => [
                'maxlength'  => 128,
                'cli'        => '--domain',
                'complete'   => true,
                'label'      => tr('Restrict to domain'),
                'help_group' => tr(''),
                'help'       => tr('The domain where this user will be able to sign in'),
            ],
            'username' => [
                'maxlength'  => 64,
                'cli'        => '-u,--username',
                'complete'   => true,
                'label'      => tr('Username'),
                'help_group' => tr('Personal information'),
                'help'       => tr('The unique username for this user.'),
            ],
            'nickname' => [
                'maxlength'  => 64,
                'cli'        => '--nickname',
                'complete'   => true,
                'label'      => tr('Nickname'),
                'help_group' => tr('Personal information'),
                'help'       => tr('The nickname for this user'),
            ],
            'first_names' => [
                'maxlength'  => 127,
                'cli'        => '-f,--first-names',
                'complete'   => true,
                'label'      => tr('First names'),
                'size'       => 3,
                'help_group' => tr('Personal information'),
                'help'       => tr('The firstnames for this user'),
            ],
            'last_names' => [
                'maxlength'  => 127,
                'cli'        => '-n,--last-names',
                'complete'   => true,
                'label'      => tr('Last names'),
                'size'       => 3,
                'help_group' => tr('Personal information'),
                'help'       => tr('The lastnames / surnames for this user'),
            ],
            'title' => [
                'maxlength'  => 24,
                'cli'        => '-t,--title',
                'complete'   => true,
                'label'      => tr('Title'),
                'size'       => 3,
                'help_group' => tr('Personal information'),
                'help'       => tr('The title added to this users name'),
            ],
            'gender' => [
                'element' => 'select',
                'source'  => [
                    ''       => tr('Select a gender'),
                    'male'   => tr('Male'),
                    'female' => tr('Female'),
                    'other'  => tr('Other')
                ],
                'size'       => 3,
                'cli'        => '-g,--gender',
                'complete'   => [tr('Male'), tr('Female'), tr('Other')],
                'label'      => tr('Gender'),
                'help_group' => tr('Personal information'),
                'help'       => tr('The gender for this user'),
            ],
            'phones'  => [
                'maxlength'  => 64,
                'size'       => 6,
                'cli'        => '-p,--phones',
                'complete'   => true,
                'label'      => tr('Phones'),
                'help_group' => tr('Personal information'),
                'help'       => tr('Phone numbers where this user can be reached'),
            ],
            'code' => [
                'visible'    => false,
                'cli'        => '--code',
                'complete'   => true,
                'label'      => tr('Code'),
                'maxlength'  => 16,
                'size'       => 6,
                'help_group' => tr('Personal information'),
                'help'       => tr(''),
            ],
            'type' => [
                'maxlength'  => 16,
                'cli'        => '--type',
                'complete'   => true,
                'label'      => tr('Type'),
                'size'       => 6,
                'help_group' => tr('Personal information'),
                'help'       => tr('The type of user'),
            ],
            'birthdate' => [
                'type'       => 'date',
                'cli'        => '-b,--birthdate',
                'complete'   => true,
                'label'      => tr('Birthday'),
                'size'       => 3,
                'help_group' => tr('Personal information'),
                'help'       => tr('The birthdate for this user'),
            ],
            'priority' => [
                'type'       => 'numeric',
                'cli'        => '--priority',
                'label'      => tr('Priority'),
                'complete'   => true,
                'size'       => 3,
                'min'        => 1,
                'max'        => 10,
                'step'       => 1,
                'help_group' => tr(''),
                'help'       => tr('The priority for this user, between 1 and 10'),
            ],
            'countries_id' => [
                'element' => function (string $key, array $data, array $source) {
                    return Countries::getHtmlCountriesSelect($key)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                },
                'cli'        => '--countries-id',
                'complete'   => true,
                'label'      => tr('Country'),
                'size'       => 3,
                'help_group' => tr('Location information'),
                'help'       => tr('The country where this user resides'),
            ],
            'states_id' => [
                'element' => function (string $key, array $data, array $source) {
                    return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                        ->setSelected(isset_get($source['states_id']))
                        ->render();
                },
                'execute'    => 'countries_id',
                'cli'        => '--states-id',
                'label'      => tr('State'),
                'size'       => 3,
                'help_group' => tr('Location information'),
                'help'       => tr('The state where this user resides'),
            ],
            'cities_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                        ->setSelected(isset_get($source['cities_id']))
                        ->render();
                },
                'execute'    => 'states_id',
                'cli'        => '--cities-id',
                'complete'   => true,
                'label'      => tr('City'),
                'size'       => 3,
                'help_group' => tr('Location information'),
                'help'       => tr('The city where this user resides'),
            ],
            'address' => [
                'maxlength'  => 255,
                'cli'        => '-a,--address',
                'complete'   => true,
                'label'      => tr('Address'),
                'size'       => 6,
                'help_group' => tr('Location information'),
                'help'       => tr('The address where this user resides'),
            ],
            'zipcode' => [
                'maxlength'  => 8,
                'cli'        => '-z,--zipcode',
                'complete'   => true,
                'label'      => tr('Zip code'),
                'size'       => 2,
                'help_group' => tr('Location information'),
                'help'       => tr('The zip code (postal code) where this user resides'),
            ],
            'languages_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Languages::getHtmlSelect($key)
                        ->setSelected(isset_get($source['languages_id']))
                        ->render();
                },
                'cli'        => '--languages-id',
                'complete'   => true,
                'label'      => tr('Language'),
                'size'       => 4,
                'help_group' => tr('Location information'),
                'help'       => tr('The language in which the site will be displayed to the user'),
            ],
            'latitude' => [
                'cli'        => '--latitude',
                'complete'   => true,
                'label'      => tr('Latitude'),
                'size'       => 2,
                'help_group' => tr('Location information'),
                'help'       => tr('The latitude location for this user'),
            ],
            'longitude' => [
                'cli'        => '--longitude',
                'complete'   => true,
                'label'      => tr('Longitude'),
                'size'       => 2,
                'help_group' => tr('Location information'),
                'help'       => tr('The longitude location for this user'),
            ],
            'accuracy' => [
                'readonly'   => true,
                'label'      => tr('Accuracy'),
                'size'       => 2,
                'help_group' => tr('Location information'),
                'help'       => tr('The accuracy of this users location'),
            ],
            'offset_latitude' => [
                'readonly'   => true,
                'label'      => tr('Offset latitude'),
                'size'       => 2,
                'help_group' => tr('Location information'),
                'help'       => tr('The latitude location for this user with a random offset within the configured range'),
            ],
            'offset_longitude' => [
                'readonly'   => true,
                'label'      => tr('Offset longitude'),
                'size'       => 2,
                'help_group' => tr('Location information'),
                'help'       => tr('The longitude location for this user with a random offset within the configured range'),
            ],
            'is_leader' => [
                'type'       => 'checkbox',
                'cli'        => '--is-leader',
                'label'      => tr('Is leader'),
                'size'       => 2,
                'help_group' => tr('Hiarchical information'),
                'help'       => tr('Sets if this user is a leader itself'),
            ],
            'leaders_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Users::getHtmlSelect($key)
                        ->setSelected(isset_get($source['created_by']))
                        ->render();
                },
                'cli'        => '--leaders-id',
                'complete'   => true,
                'label'      => tr('Leader'),
                'size'       => 2,
                'help_group' => tr('Hiarchical information'),
                'help'       => tr('The user that is the leader for this user'),
            ],
            'verified_on' => [
                'readonly'   => true,
                'type'       => 'datetime-local',
                'null_type'  => 'text',
                'default'    => tr('Not verified'),
                'label'      => tr('Account verified on'),
                'size'       => 2,
                'help_group' => tr('Account information'),
                'help'       => tr('The date when this user was email verified. Empty if not yet verified'),
            ],
            'redirect' => [
                'maxlength'  => 255,
                'type'       => 'url',
                'cli'        => '--redirect',
                'complete'   => true,
                'label'      => tr('Redirect'),
                'size'       => 6,
                'help_group' => tr('Account information'),
                'help'       => tr('The URL where this user will be redirected to upon sign in'),
            ],
            'url' => [
                'type'       => 'url',
                'maxlength'  => 2048,
                'cli'        => '--url',
                'complete'   => true,
                'label'      => tr('Website'),
                'size'       => 6,
                'help_group' => tr('Account information'),
                'help'       => tr('A URL specified by the user, usually containing more information about the user'),
            ],
            'keywords' => [
                'maxlength'  => 255,
                'cli'        => '-k,--keywords',
                'complete'   => true,
                'label'      => tr('Keywords'),
                'size'       => 12,
                'help_group' => tr('Account information'),
                'help'       => tr('The keywords for this user'),
            ],
            'description' => [
                'element'    => 'text',
                'cli'        => '-d,--description',
                'complete'   => true,
                'label'      => tr('Description'),
                'maxlength'  => 65_535,
                'size'       => 6,
                'help_group' => tr('Account information'),
                'help'       => tr('A description about this user'),
            ],
            'comments' => [
                'element'    => 'text',
                'cli'        => '-c,--comments',
                'complete'   => true,
                'label'      => tr('Comments'),
                'maxlength'  => 16_777_200,
                'size'       => 6,
                'help_group' => tr('Account information'),
                'help'       => tr('Comments about this user by leaders or administrators that are not visible to the user'),
            ],
        ];
    }
}
