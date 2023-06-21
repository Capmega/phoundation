<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use DateTimeInterface;
use Phoundation\Accounts\Passwords;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\PasswordNotChangedException;
use Phoundation\Accounts\Users\Exception\UsersException;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionDefaults;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
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
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Date\DateTime;
use Phoundation\Exception\NotSupportedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Incidents\Severity;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Html\Components\Form;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\UrlBuilder;


/**
 * Class User
 *
 * This is the default user class.
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class User extends DataEntry implements UserInterface
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
     * @var RolesInterface $roles
     */
    protected RolesInterface $roles;

    /**
     * The rights for this user
     *
     * @var RightsInterface $rights
     */
    protected RightsInterface $rights;

    /**
     * Columns that will NOT be inserted
     *
     * @var array $fields_filter_on_insert
     */
    protected array $fields_filter_on_insert = ['id', 'password'];


    /**
     * User class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = false)
    {
        $this->table        = 'accounts_users';
        $this->entry_name   = 'user';
        $this->unique_field = 'email';

        parent::__construct($identifier, $init);
    }


    /**
     * Returns id for this user entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        $id = $this->getDataValue('string', 'id');

        if (!$id) {
            // This is a guest user
            return tr('Guest');
        }

        return $id . ' / ' . $this->getDataValue('string', $this->unique_field);
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

        return Passwords::match($this->data['id'], $password, (string) $this->data['password']);
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
        return $this->getDataValue('string', 'nickname');
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
        return trim($this->getDataValue('string', 'first_names') . ' ' . $this->getDataValue('string', 'last_names'));
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
        return $this->getDataValue('string', 'first_names');
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
        return $this->getDataValue('string', 'last_names');
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
        return $this->getDataValue('string', 'username');
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
        return $this->getDataValue('string', 'last_sign_in');
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
     * Returns the update_password for this user
     *
     * @return DateTime|null
     */
    public function getUpdatePassword(): ?DateTime
    {
        $update_password = $this->getDataValue('string', 'update_password');

        if ($update_password) {
            return new DateTime($update_password);
        }

        return null;
    }


    /**
     * Sets the update_password for this user
     *
     * @param DateTime|true|null $date_time
     * @return static
     */
    public function setUpdatePassword(DateTime|bool|null $date_time): static
    {
        if (is_bool($date_time)) {
            // Update password immediately
            $date_time = new DateTime('1970');
        } elseif ($date_time) {
            $date_time = $date_time->getTimestamp();
        }

        return $this->setDataValue('update_password', $date_time);
    }


    /**
     * Returns the authentication_failures for this user
     *
     * @return int|null
     */
    public function getAuthenticationFailures(): ?int
    {
        return $this->getDataValue('int', 'authentication_failures');
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
        return $this->getDataValue('string', 'locked_until');
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
     * @return int|null
     */
    public function getSigninCount(): ?int
    {
        return $this->getDataValue('int', 'sign_in_count');
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
     * @return DateTimeInterface|null
     */
    public function getFingerprint(): ?DateTimeInterface
    {
        $fingerprint = $this->getDataValue('string', 'fingerprint');
        return new DateTime($fingerprint);
    }


    /**
     * Sets the fingerprint datetime for this user
     *
     * @param DateTimeInterface|string|int|null $fingerprint
     * @return static
     */
    public function setFingerprint(DateTimeInterface|string|int|null $fingerprint): static
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
        return $this->getDataValue('string', 'title');
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
        return $this->getDataValue('string', 'keywords');
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
        return $this->getDataValue('string', 'verification_code');
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
        return $this->getDataValue('string', 'verified_on');
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
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->getDataValue('int', 'priority');
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
        return $this->getDataValue('bool', 'is_leader', false);
    }


    /**
     * Sets the is_leader for this user
     *
     * @param int|bool|null $is_leader
     * @return static
     */
    public function setIsLeader(int|bool|null $is_leader): static
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
        return $this->getDataValue('int', 'leaders_id');
    }


    /**
     * Sets the leader for this user
     *
     * @param string|int|null $leaders_id
     * @return static
     */
    public function setLeadersId(string|int|null $leaders_id): static
    {
        if ($leaders_id and !is_natural($leaders_id)) {
            throw new OutOfBoundsException(tr('Specified leaders_id ":id" is not numeric', [
                ':id' => $leaders_id
            ]));
        }

        return $this->setDataValue('leaders_id', get_null(isset_get_typed('integer', $leaders_id)));
    }


    /**
     * Returns the leader for this user
     *
     * @return User|null
     */
    public function getLeader(): ?User
    {
        $leaders_id = $this->getDataValue('int', 'leaders_id');

        if ($leaders_id === null) {
            return null;
        }

        return new User($leaders_id);
    }


    /**
     * Sets the leader for this user
     *
     * @param User|string|int|null $leader
     * @return static
     */
    public function setLeader(User|string|int|null $leader): static
    {
        if ($leader) {
            if (!is_numeric($leader)) {
                $leader = User::get($leader);
            }

            if (is_object($leader)) {
                $leader = $leader->getId();
            }
        }

        return $this->setLeadersId(get_null($leader));
    }


    /**
     * Returns the latitude for this user
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->getDataValue('float', 'latitude');
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
        return $this->getDataValue('float', 'longitude');
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
     * @return float|null
     */
    public function getAccuracy(): ?float
    {
        return $this->getDataValue('float', 'accuracy');
    }


    /**
     * Sets the accuracy for this user
     *
     * @param float|null $accuracy
     * @return static
     */
    public function setAccuracy(?float $accuracy): static
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
        return $this->getDataValue('float', 'offset_latitude');
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
        return $this->getDataValue('float', 'offset_longitude');
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
        return $this->getDataValue('string', 'redirect');
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
        return $this->getDataValue('string', 'gender');
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
     * @return DateTimeInterface|null
     */
    public function getBirthday(): ?DateTimeInterface
    {
        $birthdate = $this->getDataValue('string', 'birthdate');

        if ($birthdate ) {
            return new DateTime($birthdate);
        }

        return null;
    }


    /**
     * Sets the birthdate for this user
     *
     * @param DateTimeInterface|string|null $birthdate
     * @return static
     */
    public function setBirthday(DateTimeInterface|string|null $birthdate): static
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

            return (string) $name;
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
        return $this->getDataValue('string', 'id') . ' / ' . $this->getDisplayName();
    }


    /**
     * Returns the roles for this user
     *
     * @return RolesInterface
     */
    public function roles(): RolesInterface
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
     * @return RightsInterface
     */
    public function rights(): RightsInterface
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

        return $this->rights()->missesKeys($rights, 'god');
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
     * @return bool
     */
    public function save(?string $comments = null): bool
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
     * @param DefinitionsInterface $definitions
     * @return void
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(Definition::new('last_sign_in')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::datetime_local)
                ->setNullInputType(InputType::text)
                ->setSize(3)
                ->setDefault('-')
                ->setLabel('Last sign in'))
            ->add(Definition::new('sign_in_count')
                ->setOptional(true, 0)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setLabel(tr('Sign in count')))
            ->add(Definition::new('authentication_failures')
                ->setOptional(true, 0)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setNullDb(false, 0)
                ->setSize(3)
                ->setLabel(tr('Authentication failures')))
            ->add(Definition::new('locked_until')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::datetime_local)
                ->setNullInputType(InputType::text)
                ->setSize(3)
                ->setDefault(tr('Not locked'))
                ->setLabel(tr('Locked until')))
            ->add(DefinitionDefaults::getEmail()
                ->setSize(3)
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The email address for this user. This is also the unique identifier for the user')))
            ->add(Definition::new('domain')
                ->setOptional(true)
                ->setMaxlength(128)
                ->setSize(3)
                ->setCliField('--domain')
                ->setAutoComplete(true)
                ->setLabel(tr('Restrict to domain'))
                ->setHelpText(tr('The domain where this user will be able to sign in'))
                ->addValidationFunction(function ($validator) {
                    $validator->isDomain();
                }))
            ->add(Definition::new('username')
                ->setOptional(true)
                ->setMaxLength(64)
                ->setSize(3)
                ->setCliField('-u,--username')
                ->setAutoComplete(true)
                ->setLabel(tr('Username'))
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The unique username for this user.'))
                ->addValidationFunction(function ($validator) {
                    $validator->isName();
                }))
            ->add(DefinitionDefaults::getName('nickname')
                ->setLabel(tr('Nickname'))
                ->setCliField('--nickname NAME')
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The nickname for this user')))
            ->add(DefinitionDefaults::getName('first_names')
                ->setCliField('-f,--first-names NAMES')
                ->setLabel(tr('First names'))
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The firstnames for this user')))
            ->add(DefinitionDefaults::getName('last_names')
                ->setCliField('-n,--last-names')
                ->setLabel(tr('Last names'))
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The lastnames / surnames for this user')))
            ->add(DefinitionDefaults::getTitle()
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The title added to this users name')))
            ->add(Definition::new('gender')
                ->setOptional(true)
                ->setElement(InputElement::select)
                ->setSize(3)
                ->setCliField('-g,--gender')
                ->setSource([
                    ''       => tr('Select a gender'),
                    'male'   => tr('Male'),
                    'female' => tr('Female'),
                    'other'  => tr('Other')
                ])
                ->setAutoComplete([
                    'word'   => function (string $word) { return Arrays::filterValues([tr('Male'), tr('Female'), tr('Other')], $word); },
                    'noword' => function ()             { return [tr('Male'), tr('Female'), tr('Other')];},
                ])
                ->setLabel(tr('Gender'))
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The gender for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->hasMaxCharacters(6);
                }))
            ->add(DefinitionDefaults::getUser('leader')
                ->setCliField('--leader USER-EMAIL')
                ->addValidationFunction(function ($validator) {
                    $validator->or('leaders_id')->isEmail()->setColumnFromQuery('leaders_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email AND `status` IS NULL', [':email' => '$leader']);
                }))
            ->add(DefinitionDefaults::getUsersId('leaders_id')
                ->setCliField('--leaders-id USERS-DATABASE-ID')
                ->setLabel(tr('Leader'))
                ->setHelpGroup(tr('Hierarchical information'))
                ->setHelpText(tr('The user that is the leader for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->or('leader');
                }))
            ->add(Definition::new('is_leader')
                ->setOptional(true)
                ->setInputType(InputType::checkbox)
                ->setSize(3)
                ->setCliField('--is-leader')
                ->setAutoComplete(true)
                ->setLabel(tr('Is leader'))
                ->setHelpGroup(tr('Hierarchical information'))
                ->setHelpText(tr('Sets if this user is a leader itself'))
                ->addValidationFunction(function ($validator) {
                    $validator->isBoolean();
                }))
            ->add(DefinitionDefaults::getCode()
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The code associated with this user')))
            ->add(Definition::new('priority')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setCliField('--priority')
                ->setAutoComplete(true)
                ->setLabel(tr('Priority'))
                ->setMin(1)
                ->setMax(9)
                ->setHelpText(tr('The priority for this user, between 1 and 9'))
                ->addValidationFunction(function ($validator) {
                    $validator->isInteger();
                }))
            ->add(DefinitionDefaults::getDate('birthdate')
                ->setLabel(tr('Birthdate'))
                ->setCliField('-b,--birthdate')
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('The birthdate for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->isDate()->isPast();
                }))
            ->add(DefinitionDefaults::getPhones()
                ->setHelpGroup(tr('Personal information'))
                ->setHelpText(tr('Multiple phone numbers where this user may be contacted')))
            ->add(Definition::new('address')
                ->setOptional(true)
                ->setMaxlength(255)
                ->setSize(6)
                ->setCliField('-a,--address')
                ->setAutoComplete(true)
                ->setLabel(tr('Address'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The address where this user resides'))
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                }))
            ->add(Definition::new('zipcode')
                ->setOptional(true)
                ->setMinlength(4)
                ->setMaxlength(8)
                ->setSize(3)
                ->setCliField('-z,--zipcode')
                ->setAutoComplete(true)
                ->setLabel(tr('Zip code'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The zip code (postal code) where this user resides'))
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                }))
            ->add(DefinitionDefaults::getCountry()
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The country where this user resides')))
            ->add(DefinitionDefaults::getCountriesId())
            ->add(DefinitionDefaults::getState()
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The state where this user resides')))
            ->add(DefinitionDefaults::getStatesId())
            ->add(DefinitionDefaults::getCity()
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The city where this user resides')))
            ->add(DefinitionDefaults::getCitiesId())
            ->add(Definition::new('latitude')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setCliField('--latitude')
                ->setAutoComplete(true)
                ->setLabel(tr('Latitude'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The latitude location for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->isLatitude();
                }))
            ->add(Definition::new('longitude')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setCliField('--longitude')
                ->setAutoComplete(true)
                ->setLabel(tr('Longitude'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The longitude location for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->isLongitude();
                }))
            ->add(Definition::new('offset_latitude')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setAutoComplete(true)
                ->setLabel(tr('Offset latitude'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The latitude location for this user with a random offset within the configured range')))
            ->add(Definition::new('offset_longitude')
                ->setOptional(true)
                ->setReadonly(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setAutoComplete(true)
                ->setLabel(tr('Offset longitude'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The longitude location for this user with a random offset within the configured range')))
            ->add(Definition::new('accuracy')
                ->setOptional(true)
                ->setInputType(InputType::number)
                ->setSize(3)
                ->setMin(0)
                ->setMax(10)
                ->setCliField('--accuracy')
                ->setAutoComplete(true)
                ->setLabel(tr('Accuracy'))
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The accuracy of this users location'))
                ->addValidationFunction(function ($validator) {
                    $validator->isFloat();
                }))
            ->add(Definition::new('type')
                ->setOptional(true)
                ->setMaxLength(16)
                ->setSize(3)
                ->setCliField('--type')
                ->setAutoComplete(true)
                ->setLabel(tr('Type'))
                ->setHelpGroup(tr(''))
                ->setHelpText(tr('The type classification for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->isName();
                }))
            ->add(DefinitionDefaults::getTimezone()
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The timezone where this user resides')))
            ->add(DefinitionDefaults::getTimezonesId())
            ->add(DefinitionDefaults::getLanguage()
                ->setHelpGroup(tr('Location information'))
                ->setHelpText(tr('The display language for this user')))
            ->add(DefinitionDefaults::getLanguagesId())
            ->add(Definition::new('keywords')
                ->setOptional(true)
                ->setMaxlength(255)
                ->setSize(3)
                ->setCliField('-k,--keywords')
                ->setAutoComplete(true)
                ->setLabel(tr('Keywords'))
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('The keywords for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                    //$validator->sanitizeForceArray(' ')->each()->isWord()->sanitizeForceString()
                }))
            ->add(DefinitionDefaults::getDateTime('verified_on')
                ->setReadonly(true)
                ->setNullInputType(InputType::text)
                ->setDefault(tr('Not verified'))
                ->setLabel(tr('Account verified on'))
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('The date when this user was email verified. Empty if not yet verified')))
            ->add(DefinitionDefaults::getUrl('redirect')
                ->setSize(6)
                ->setLabel(tr('Redirect URL'))
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('The URL where this user will be redirected to upon sign in')))
            ->add(Definition::new('url')
                ->setCliField('--url')
                ->setLabel(tr('Website URL'))
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('A URL specified by the user, usually containing more information about the user')))
            ->add(DefinitionDefaults::getDescription()
                ->setSize(6)
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('A public description about this user')))
            ->add(DefinitionDefaults::getComments()
                ->setSize(6)
                ->setHelpGroup(tr('Account information'))
                ->setHelpText(tr('Comments about this user by leaders or administrators that are not visible to the user')))
            ->add(Definition::new('verification_code')
                ->setOptional(true)
                ->setVisible(false)
                ->setReadonly(true))
            ->add(Definition::new('fingerprint')
                // TODO Implement
                ->setOptional(true)
                ->setVisible(false))
            ->add(Definition::new('password')
                ->setVisible(false)
                ->setReadonly(true)
                ->setOptional(true)
                ->setAutoComplete(true)
                ->setInputType(InputType::password)
                ->setMaxlength(64)
                ->setNullDb(false)
                ->setHelpText(tr('The password for this user'))
                ->addValidationFunction(function ($validator) {
                    $validator->isStrongPassword();
                }))
            ->add(Definition::new('picture')
                // TODO Implement
                ->setOptional(true)
                ->setVisible(false));
    }
}
