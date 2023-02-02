<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Passwords;
use Phoundation\Accounts\Rights\Rights;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\PasswordNotChangedException;
use Phoundation\Accounts\Users\Exception\UsersException;
use Phoundation\Business\Companies\Branches\Branch;
use Phoundation\Business\Companies\Company;
use Phoundation\Business\Companies\Departments\Department;
use Phoundation\Core\Locale\Language\Languages;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\DataEntryAddress;
use Phoundation\Data\DataEntry\DataEntryCode;
use Phoundation\Data\DataEntry\DataEntryComments;
use Phoundation\Data\DataEntry\DataEntryDomain;
use Phoundation\Data\DataEntry\DataEntryEmail;
use Phoundation\Data\DataEntry\DataEntryGeo;
use Phoundation\Data\DataEntry\DataEntryLanguage;
use Phoundation\Data\DataEntry\DataEntryNameDescription;
use Phoundation\Data\DataEntry\DataEntryPhones;
use Phoundation\Data\DataEntry\DataEntryPicture;
use Phoundation\Data\DataEntry\DataEntryType;
use Phoundation\Data\DataEntry\DataEntryUrl;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Date\DateTime;
use Phoundation\Exception\NotSupportedException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Geo\Countries\Countries;
use Phoundation\Geo\Countries\Country;
use Phoundation\Geo\States\State;
use Phoundation\Geo\Timezones\Timezone;
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
     * @var array $remove_columns_on_insert
     */
    protected array $remove_columns_on_insert = ['id', 'password'];

    /**
     * Columns that will NOT be updated
     *
     * @var array $remove_columns_on_update
     */
    protected array $remove_columns_on_update = ['meta_id', 'created_by', 'created_on', 'password'];



    /**
     * User class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        static::$entry_name    = 'user';
        $this->table         = 'accounts_users';
        $this->unique_column = 'email';

        parent::__construct($identifier);
    }



    /**
     * Returns this user as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getLogId();
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

        return $id . ' / ' . $this->getDataValue($this->unique_column);
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
        return $this->setDataValue('authentication_failures', $authentication_failures);
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
     * Returns the avatar for this user
     *
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->getDataValue('avatar');
    }



    /**
     * Sets the avatar for this user
     *
     * @param string|null $avatar
     * @return static
     */
    public function setAvatar(?string $avatar): static
    {
        return $this->setDataValue('avatar', $avatar);
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
     * @param string|null $keywords
     * @return static
     */
    public function setKeywords(?string $keywords): static
    {
        return $this->setDataValue('keywords', $keywords);
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
    public function getverifiedOn(): ?string
    {
        return $this->getDataValue('verified_on');
    }



    /**
     * Sets the verified_on for this user
     *
     * @param string|null $verified_on
     * @return static
     */
    public function setverifiedOn(?string $verified_on): static
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
     * Returns the birthday for this user
     *
     * @return DateTime|null
     */
    public function getBirthday(): ?DateTime
    {
        $birthday = $this->getDataValue('birthday');

        if ($birthday ) {
            return new DateTime($birthday);
        }

        return null;
    }



    /**
     * Sets the birthday for this user
     *
     * @param string|null $birthday
     * @return static
     */
    public function setBirthday(?string $birthday): static
    {
        return $this->setDataValue('birthday', $birthday);
    }



    /**
     * Returns the timezone for this user
     *
     * @return Timezone|null
     */
    public function getTimezone(): ?Timezone
    {
        $timezone = $this->getDataValue('timezone');

        if ($timezone === null) {
            return null;
        }

        return new Timezone($timezone);
    }



    /**
     * Sets the timezone for this user
     *
     * @param string|null $gender
     * @return static
     */
    public function setTimezone(?string $gender): static
    {
        return $this->setDataValue('timezone', $gender);
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
    protected function validatePassword(string $password, string $validation): static
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
            throw new OutOfBoundsException(tr('Cannot set password for this user, it has no password'));
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
     * Returns the company for this user
     *
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }



    /**
     * Sets the company for this user
     *
     * @param  Company|string|int|null $company
     * @return static
     */
    public function setCompany(Company|string|int|null $company): static
    {
        if ($company) {
            if (!is_object($company)) {
                $company = Company::get($company);
            }
        }

        $this->company = $company;
        return $this;
    }



    /**
     * Returns the department for this user
     *
     * @return Department|null
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }



    /**
     * Sets the department for this user
     *
     * @param Department|string|int|null $department
     * @return static
     */
    public function setDepartment(Department|string|int|null $department): static
    {
        if ($department) {
            if (!is_object($department)) {
                $department = Department::get($department);
            }

            // This branch must be part of the specified company!
            if (!$this->company) {
                throw new ValidationFailedException(tr('Cannot specify a department, this user is not linked to a company yet'));
            }

            // This branch must be part of the specified company!
            if (!$this->company->departments()->exists($department)) {
                throw new ValidationFailedException(tr('The department ":department" is not part of company ":company"', [
                    ':branch' => $department->getName(),
                    ':company' => $this->company->getName()
                ]));
            }
        }

        $this->department = $department;
        return $this;
    }



    /**
     * Returns the branch for this user
     *
     * @return Branch|null
     */
    public function getBranch(): ?Branch
    {
        return $this->branch;
    }



    /**
     * Sets the branch for this user
     *
     * @param  Branch|string|int|null $branch
     * @return static
     */
    public function setBranch(Branch|string|int|null $branch): static
    {
        if ($branch) {
            if (!is_object($branch)) {
                $branch = Branch::get($branch);
            }

            // This branch must be part of the specified company!
            if (!$this->company) {
                throw new ValidationFailedException(tr('Cannot specify a branch, this user is not linked to a company yet'));
            }

            // This branch must be part of the specified company!
            if (!$this->company->branches()->exists($branch)) {
                throw new ValidationFailedException(tr('The branch ":branch" is not part of company ":company"', [
                    ':branch' => $branch->getName(),
                    ':company' => $this->company->getName()
                ]));
            }
        }

        $this->branch = $branch;
        return $this;
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
     * @return static
     */
    public function save(): static
    {
        Log::action(tr('Saving user ":user"', [':user' => $this->getDisplayName()]));
        return parent::save();
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
                ->setType('Incorrect password')
                ->setSeverity(Severity::low)
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
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id' => [
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_on' => [
                'disabled'  => true,
                'type'      => 'text',
                'label'     => tr('Created on')
            ],
            'created_by' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Users::getHtmlSelect($key)
                        ->setSelected(isset_get($source['created_by']))
                        ->setDisabled(true)
                        ->render();
                },
                'label'    => tr('Created by')
            ],
            'meta_id' => [
                'disabled' => true,
                'element'  => null, //Meta::new()->getHtmlTable(), // TODO implement
                'label'    => tr('Meta information')
            ],
            'status' => [
                'disabled' => true,
                'default'  => tr('Ok'),
                'label'    => tr('Status')
            ],
            'last_sign_in' => [
                'disabled'  => true,
                'type'      => 'date',
                'null_type' => 'text',
                'default'   => '-',
                'label'     => tr('Last sign in')
            ],
            'authentication_failures' => [
                'disabled' => true,
                'db_null'  => false,
                'type'     => 'numeric',
                'label'    => tr('Authentication failures')
            ],
            'locked_until' => [
                'disabled'  => true,
                'type'      => 'date',
                'null_type' => 'text',
                'default'   => '-',
                'label'     => tr('Locked until')
            ],
            'sign_in_count' => [
                'disabled' => true,
                'db_null'  => false,
                'type'     => 'numeric',
                'label'    => tr('Sign in count')
            ],
            'username' => [
                'label'    => tr('Username')
            ],
            'password' => [
                'type'    => 'password',
                'db_null' => false,
                'label'   => tr('Password')
            ],
            'fingerprint' => [
                'element' => null  // TODO Implement
            ],
            'domain' => [
                'label'   => tr('Restrict to domain')
            ],
            'title' => [
                'label'   => tr('Title')
            ],
            'first_names' => [
                'label'   => tr('First names')
            ],
            'last_names' => [
                'label'   => tr('Last names')
            ],
            'nickname' => [
                'label'   => tr('Nickname')
            ],
            'avatar' => [
                'display' => false
            ],
            'type' => [
                'label'    => tr('Type')
            ],
            'email' => [
                'type'     => 'email',
                'label'    => tr('Email address')
            ],
            'code' => [
                'label'    => tr('Code')
            ],
            'keywords' => [
                'label'    => tr('Keywords')
            ],
            'phones'  => [
                'label'    => tr('Phones')
            ],
            'address' => [
                'label'    => tr('Address')
            ],
            'verification_code' => [
                'display'  => false
            ],
            'verified_on' => [
                'disabled'  => true,
                'type'      => 'date',
                'null_type' => 'text',
                'default'   => tr('Not verified'),
                'label'     => tr('Account verified on'),
            ],
            'priority' => [
                'type'     => 'numeric',
                'label'    => tr('Priority'),
            ],
            'is_leader' => [
                'type'     => 'checkbox',
                'label'    => tr('Is leader'),
            ],
            'leaders_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Users::getHtmlSelect($key)
                        ->setSelected(isset_get($source['created_by']))
                        ->render();
                },
                'label'    => tr('Leader'),
            ],
            'latitude' => [
                'label'    => tr('Latitude'),
            ],
            'longitude' => [
                'label'    => tr('Longitude'),
            ],
            'accuracy' => [
                'label'    => tr('Accuracy'),
            ],
            'offset_latitude' => [
                'readonly' => true,
                'label'    => tr('Offset latitude'),
            ],
            'offset_longitude' => [
                'readonly' => true,
                'label'    => tr('Offset longitude'),
            ],
            'countries_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Countries::getHtmlCountriesSelect($key)
                        ->setSelected(isset_get($source['countries_id']))
                        ->render();
                },
                'label'    => tr('Country')
            ],
            'states_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Country::get($source['countries_id'])->getHtmlStatesSelect($key)
                        ->setSelected(isset_get($source['states_id']))
                        ->render();
                },
                'execute'  => 'countries_id',
                'label'    => tr('State'),
            ],
            'cities_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return State::get($source['states_id'])->getHtmlCitiesSelect($key)
                        ->setSelected(isset_get($source['cities_id']))
                        ->render();
                },
                'execute'  => 'states_id',
                'label'    => tr('City'),
            ],
            'redirect' => [
                'type'     => 'url',
                'label'    => tr('Redirect'),
            ],
            'languages_id' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Languages::getHtmlSelect($key)
                      ->setSelected(isset_get($source['languages_id']))
                      ->render();
                },
                'source'   => [],
                'label'    => tr('Language'),
            ],
            'gender' => [
                'element'  => 'select',
                'source'   => [
                    ''       => tr('Select a gender'),
                    'male'   => tr('Male'),
                    'female' => tr('Female'),
                    'other'  => tr('Other')
                ],
                'label'    => tr('Gender'),
            ],
            'birthday' => [
                'type'     => 'date',
                'label'    => tr('Birthday'),
            ],
            'url' => [
                'type'     => 'url',
                'label'    => tr('Website'),
            ],
            'timezone' => [
                'source'   => 'SELECT `seo_name`, `name` FROM `geo_timezones`',
                'label'    => tr('Timezone'),
            ],
            'description' => [
                'element'  => 'text',
                'label'    => tr('Description'),
            ],
            'comments' => [
                'element'  => 'text',
                'label'    => tr('Comments'),
            ],
        ];

        $this->keys_display = [
            'id'                      => 12,
            'created_by'              => 3,
            'created_on'              => 3,
            'meta_id'                 => 3,
            'status'                  => 3,
            'last_sign_in'            => 3,
            'sign_in_count'           => 3,
            'authentication_failures' => 3,
            'locked_until'            => 3,
            'email'                   => 3,
            'domain'                  => 3,
            'username'                => 3,
            'nickname'                => 3,
            'first_names'             => 3,
            'last_names'              => 3,
            'gender'                  => 3,
            'birthday'                => 3,
            'title'                   => 3,
            'phones'                  => 3,
            'address'                 => 6,
            'type'                    => 3,
            'code'                    => 3,
            'keywords'                => 6,
            'url'                     => 6,
            'leaders_id'              => 3,
            'is_leader'               => 3,
            'latitude'                => 3,
            'longitude'               => 3,
            'offset_latitude'         => 3,
            'offset_longitude'        => 3,
            'accuracy'                => 3,
            'priority'                => 3,
            'verified_on'             => 3,
            'languages_id'            => 3,
            'timezone'                => 3,
            'countries_id'            => 3,
            'states_id'               => 3,
            'cities_id'               => 3,
            'redirect'                => 12,
            'description'             => 6,
            'comments'                => 6
        ] ;
    }
}
