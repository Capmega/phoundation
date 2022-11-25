<?php

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Roles\UserRoles;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Api\Users;
use Phoundation\Business\Companies\Branches\Branch;
use Phoundation\Business\Companies\Company;
use Phoundation\Business\Companies\Departments\Department;
use Phoundation\Content\Images\Image;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry;
use Phoundation\Data\DataEntryNameDescription;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Date\DateTime;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Geo\City;
use Phoundation\Geo\Country;
use Phoundation\Geo\State;
use Phoundation\Geo\Timezone;



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
    use DataEntryNameDescription;



    /**
     * The roles for this user
     *
     * @var UserRoles
     */
    protected UserRoles $roles;

    /**
     * The company for this user
     *
     * @var Company|null $company
     */
    protected ?Company $company;

    /**
     * The department for this user
     *
     * @var Department|null $department
     */
    protected ?Department $department;

    /**
     * The branch for this user
     *
     * @var Branch|null $branch
     */
    protected ?Branch $branch;



    public function __construct(int|string|null $identifier = null)
    {
        self::$entry_name    = 'user';
        $this->table         = 'accounts_users';
        $this->unique_column = 'email';

        parent::__construct($identifier);
    }



    /**
     * Authenticates the specified user id / email with its password
     *
     * @param string|int $identifier
     * @param string $password
     * @return static
     */
    public static function authenticate(string|int $identifier, string $password): static
    {
        $user = User::get($identifier);

        if ($user->passwordMatch($password)) {
            return $user;
        }

        throw new AuthenticationException(tr('The specified password did not match for user ":user"', [
            ':user' => $identifier
        ]));
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
        return ($this->id === 0);
    }



    /**
     * Returns the picture for this user
     *
     * @return Image
     */
    public function getPicture(): Image
    {
        return Image::new($this->getDataValue('picture'))
            ->setDescription(tr('Profile image for :user', [':user' => $this->getDisplayName()]));
    }



    /**
     * Sets the picture for this user
     *
     * @param Image|string|null $picture
     * @return static
     */
    public function setPicture(Image|string|null $picture): static
    {
        if (!$picture) {
            $picture = Image::new('profiles/default.png');
        }

        return $this->setDataValue('picture', Strings::from(PATH_CDN, $picture->getFile()));
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
     * Returns the email for this user
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getDataValue('email');
    }



    /**
     * Sets the email for this user
     *
     * @param string $email
     * @return static
     */
    public function setEmail(string $email): static
    {
        return $this->setDataValue('email', $email);
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
     * Returns the auth_fails for this user
     *
     * @return string|null
     */
    public function getAuthenticationFailures(): ?string
    {
        return $this->getDataValue('auth_fails');
    }



    /**
     * Sets the auth_fails for this user
     *
     * @param int|null $auth_fails
     * @return static
     */
    public function setAuthenticationFailures(?int $auth_fails): static
    {
        return $this->setDataValue('auth_fails', $auth_fails);
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
     * @param \DateTime|int $fingerprint
     * @return static
     */
    public function setFingerprint(\DateTime|int $fingerprint): static
    {
        if (is_object($fingerprint)) {
            $fingerprint = $fingerprint->format('Y-m-d H:i:s');
        }

        return $this->setDataValue('fingerprint', $fingerprint);
    }



    /**
     * Returns the domain for this user
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->getDataValue('domain');
    }



    /**
     * Sets the domain for this user
     *
     * @param string|null $domain
     * @return static
     */
    public function setDomain(?string $domain): static
    {
        return $this->setDataValue('domain', $domain);
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
     * Returns the code for this user
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getDataValue('code');
    }



    /**
     * Sets the code for this user
     *
     * @param string|null $code
     * @return static
     */
    public function setCode(?string $code): static
    {
        return $this->setDataValue('code', $code);
    }



    /**
     * Returns the type for this user
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getDataValue('type');
    }



    /**
     * Sets the type for this user
     *
     * @param string|null $type
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->setDataValue('type', $type);
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
     * Returns the phones for this user
     *
     * @return string|null
     */
    public function getPhones(): ?string
    {
        return $this->getDataValue('phones');
    }



    /**
     * Sets the phones for this user
     *
     * @param string|null $phones
     * @return static
     */
    public function setPhones(?string $phones): static
    {
        return $this->setDataValue('phones', $phones);
    }



    /**
     * Returns the address for this user
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->getDataValue('address');
    }



    /**
     * Sets the address for this user
     *
     * @param string|null $address
     * @return static
     */
    public function setAddress(?string $address): static
    {
        return $this->setDataValue('address', $address);
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
     * Returns the leaders_id for this user
     *
     * @return static|null
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
     * Sets the leaders_id for this user
     *
     * @param User|int|null $leader
     * @return static
     */
    public function setLeader(User|int|null $leader): static
    {
        if (is_object($leader)) {
            $leader = $leader->getId();
        }

        return $this->setDataValue('leaders_id', $leader);
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
     * Returns the cities_id for this user
     *
     * @return City|null
     */
    public function getCity(): ?City
    {
        return $this->getDataValue('cities_id');
    }



    /**
     * Sets the cities_id for this user
     *
     * @param City|null $city
     * @return static
     */
    public function setCity(?City $city): static
    {
        if (is_object($city)) {
            $city = $city->getId();
        }

        return $this->setDataValue('cities_id', $city);
    }



    /**
     * Returns the states_id for this user
     *
     * @return State|null
     */
    public function getState(): ?State
    {
        return $this->getDataValue('states_id');
    }



    /**
     * Sets the states_id for this user
     *
     * @param State|null $state
     * @return static
     */
    public function setState(?State $state): static
    {
        if (is_object($state)) {
            $state = $state->getId();
        }

        return $this->setDataValue('states_id', $state);
    }



    /**
     * Returns the countries_id for this user
     *
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        return $this->getDataValue('countries_id');
    }



    /**
     * Sets the countries_id for this user
     *
     * @param Country|null $country
     * @return static
     */
    public function setCountry(?Country $country): static
    {
        if (is_object($country)) {
            $country = $country->getId();
        }

        return $this->setDataValue('countries_id', $country);
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
    public function setRedirect(?string $redirect): static
    {
        if (!filter_var($redirect, FILTER_VALIDATE_URL)) {
            throw new OutOfBoundsException(tr('Invalid redirect URL ":redirect" specified', [
                ':redirect' => $redirect
            ]));
        }

        return $this->setDataValue('redirect', $redirect);
    }



    /**
     * Returns the language for this user
     *
     * @return string|null
     */
    public function getLanguage(): ?string
    {
        return $this->getDataValue('language');
    }



    /**
     * Sets the language for this user
     *
     * @param string|null $language
     * @return static
     */
    public function setLanguage(?string $language): static
    {
        if ($language and (strlen($language) != 2)) {
            throw new OutOfBoundsException(tr('Invalid language ":language" specified', [
                ':language' => $language
            ]));
        }

        return $this->setDataValue('language', $language);
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

        if ($birthday === null) {
            return null;
        }

        return new DateTime($birthday);
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
     * Returns the comments for this user
     *
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->getDataValue('comments');
    }



    /**
     * Sets the comments for this user
     *
     * @param string|null $comments
     * @return static
     */
    public function setComments(?string $comments): static
    {
        return $this->setDataValue('comments', $comments);
    }



    /**
     * Returns the website for this user
     *
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->getDataValue('website');
    }



    /**
     * Sets the website for this user
     *
     * @param string|null $website
     * @return static
     */
    public function setWebsite(?string $website): static
    {
        return $this->setDataValue('website', $website);
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
        $this->validatePassword($password, $validation);

        $password = $this->passwordHash($password);

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

        $this->passwordTestSecure($password);

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
            return $name;
        }

        // We have no information available about this user
        return tr('Guest');
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
     * @return UserRoles
     */
    public function roles(): UserRoles
    {
        if (!isset($this->roles)) {
            $this->roles = UserRoles::new()->setUser($this->getDataValue('id'));
        }

        return $this->roles;
    }



    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @return bool
     */
    public function hasAllRights(array|string $rights): bool
    {
    }



    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     * @return bool
     */
    public function hasSomeRights(array|string $rights): bool
    {
    }



    /**
     * Returns true if the specified password matches the users password
     *
     * @param string $password
     * @return bool
     */
    public function passwordMatch(string $password): bool
    {
        $hash = $this->hashPassword($password);
        return $hash === $this->getDataValue('password');
    }



    /**
     * Save all user data to database
     *
     * @return static
     */
    public function save(): static
    {
        parent::save();
        $this->roles->save();
        return $this;
    }



    /**
     * Sets the available data keys for the User class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id',
            'created_by',
            'created_on',
            'meta_id',
            'status',
            'last_sign_in',
            'auth_fails',
            'locked_until',
            'sign_in_count',
            'username',
            'password',
            'fingerprint',
            'domain',
            'title',
            'name',
            'nickname',
            'avatar',
            'email',
            'code',
            'type',
            'keywords',
            'phones',
            'address',
            'verification_code',
            'verified_on',
            'priority',
            'is_leader',
            'leaders_id',
            'latitude',
            'longitude',
            'accuracy',
            'offset_latitude',
            'offset_longitude',
            'cities_id',
            'states_id',
            'countries_id',
            'redirect',
            'language',
            'gender',
            'birthday',
            'description',
            'comments',
            'website',
            'timezone',
            'companies_id'
        ];
    }



    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @return void
     */
    protected function passwordTestSecure(string $password): void
    {
        if ($this->passwordWeak($password)) {
            throw new ValidationFailedException(tr('This password is not secure enough'));
        }

        if ($this->passwordCompromised($password)) {
            throw new ValidationFailedException(tr('This password has been compromised'));
        }
    }



    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @return bool
     */
    protected function passwordWeak(string $password): bool
    {
        $strength = $this->getPasswordStrength($password);
show($strength);
        return ($strength < Config::get('security.password.strength', 50));
    }



    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @return int
     */
    protected function getPasswordStrength(string $password): int
    {
        // Get the length of the password
        $strength = 10;
        $length   = strlen($password);

        if($length < 8) {
            if(!$length) {
                Log::warning(tr('No password specified'));
                return -1;
            }

            Log::warning(tr('Specified password has length ":length" which is too short and cannot be accepted', [
                ':length' => $length
            ]));

            return -1;
        }

        // Check if password is not all lower case
        if(strtolower($password) === $password){
            $strength -= 15;
        }

        // Check if password is not all upper case
        if(strtoupper($password) === $password){
            $strength -= 15;
        }

        // Bonus for long passwords
        $strength += ($length * 2);

        // Get the amount of upper case letters in the password
        preg_match_all('/[A-Z]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Get the amount of lower case letters in the password
        preg_match_all('/[a-z]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Get the numbers in the password
        preg_match_all('/[0-9]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Check for special chars
        preg_match_all('/[|!@#$%&*\/=?,;.:\-_+~^\\\]/', $password, $matches);
        $strength += (count($matches[0]) * 2);

        // Get the number of unique chars
        $chars            = str_split($password);
        $num_unique_chars = count(array_unique($chars));

        $strength += $num_unique_chars * 4;

        // Test for same character repeats
        $repeats = Strings::countCharacters($password);
        $count   = (array_pop($repeats) + array_pop($repeats) + array_pop($repeats));

        if (($count / ($length + 3) * 10) >= 3) {
            $strength = $strength - ($strength * ($count / $length));
        } else {
            $strength = $strength + ($strength * ($count / $length));
        }

        // Test for character series
        $series     = Strings::countAlphaNumericSeries($password);
        $percentage = ($series / strlen($password)) * 100;
        $strength  += ((100 - $percentage) / 2);

        // Strength is a number 1 - 100;
        $strength = floor(($strength > 99) ? 99 : $strength);

        if(VERBOSE){
            Log::notice(tr('Password strength is ":strength"', [':strength' => $strength]));
        }

        return $strength;
    }



    /**
     * Returns true if the password is considered secure enough
     *
     * @param string $password
     * @return bool
     */
    protected function passwordCompromised(string $password): bool
    {
        return (bool) sql()->get('SELECT `id` FROM `accounts_compromised_passwords` WHERE `password` = :password', [
            ':password' => $password
        ]);
    }



    /**
     * Returns the hashed version of the possword
     *
     * @param string $password
     * @return string
     */
    protected function passwordHash(string $password): string
    {
        return '*DEFAULT*' . password_hash($this->id . $password);
    }
}