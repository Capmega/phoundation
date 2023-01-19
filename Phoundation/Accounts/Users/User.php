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
use Phoundation\Content\Images\Image;
use Phoundation\Core\Locale\Language\Languages;
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
use Phoundation\Web\Http\Html\Components\Form;
use Phoundation\Web\Http\Url;
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
        self::$entry_name    = 'user';
        $this->table         = 'accounts_users';
        $this->unique_column = 'email';

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
            return $id . ' / ' . tr('Guest');
        }

        return $id . ' / ' . $this->getDataValue($this->unique_column);
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
            $picture = Image::new('img/profiles/default.png');
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
     * @param string|null $email
     * @return static
     */
    public function setEmail(?string $email): static
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
     * @param array|string|null $phones
     * @return static
     */
    public function setPhones(array|string|null $phones): static
    {
        return $this->setDataValue('phones', Strings::force($phones, ', '));
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
     * @return int|null
     */
    public function getCitiesId(): ?int
    {
        return $this->getDataValue('cities_id');
    }



    /**
     * Sets the cities_id for this user
     *
     * @param int|null $cities_id
     * @return static
     */
    public function setCitiesId(?int $cities_id): static
    {
        return $this->setDataValue('cities_id', $cities_id);
    }



    /**
     * Returns the cities_id for this user
     *
     * @return int|null
     */
    public function getCity(): ?int
    {
        $cities_id = $this->getDataValue('cities_id');

        if ($cities_id) {
            return new City($cities_id);
        }

        return null;
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
     * @return int|null
     */
    public function getStatesId(): ?int
    {
        return $this->getDataValue('states_id');
    }



    /**
     * Sets the states_id for this user
     *
     * @param int|null $states_id
     * @return static
     */
    public function setStatesId(?int $states_id): static
    {
        return $this->setDataValue('states_id', $states_id);
    }



    /**
     * Returns the state for this user
     *
     * @return State|null
     */
    public function getState(): ?State
    {
        $states_id = $this->getDataValue('states_id');

        if ($states_id) {
            return new State($states_id);
        }

        return null;
    }



    /**
     * Sets the state for this user
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
     * @return int|null
     */
    public function getCountriesId(): ?int
    {
        return $this->getDataValue('countries_id');
    }



    /**
     * Sets the countries_id for this user
     *
     * @param int|null $country
     * @return static
     */
    public function setCountriesId(?int $country): static
    {
        return $this->setDataValue('countries_id', $country);
    }



    /**
     * Returns the countries_id for this user
     *
     * @return Country|null
     */
    public function getCountry(): ?Country
    {
        $countries_id = $this->getDataValue('countries_id');

        if ($countries_id) {
            return new Country($countries_id);
        }

        return null;
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
    public function setRedirect(?string $redirect = null): static
    {
        if ($redirect) {
            // Ensure we have a valid redirect URL
            $redirect = UrlBuilder::www($redirect);
        }

        return $this->setDataValue('redirect', get_null($redirect));
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
        if ($language) {
            if (strlen($language) != 2) {
                throw new OutOfBoundsException(tr('Invalid language ":language" specified', [
                    ':language' => $language
                ]));
            }
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
            throw new OutOfBoundsException(tr('Cannot set password for this user, it has no password'));
        }

        // Is the password secure?
        Passwords::testSecurity($password, $this->data['email'], $this->data['id']);

        // Is the password not the same as the current password?
        try {
            self::authenticate($this->data['email'], $password);
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
            return $name;
        }

        // We have no information available about this user
        return tr('Guest');
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

        foreach ($roles as $role) {
            $select->setSelected($role->getSeoName());
            $form->addContent($select->render() . '<br>');
        }

        // Add extra entry with nothing selected
        $select->clearSelected();
        $form->addContent($select->render());
        return $form;
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
                'display'  => true,
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Database ID')
            ],
            'created_by' => [
                'element'  => 'input',
                'display'  => true,
                'disabled' => true,
                'source'   => 'SELECT IFNULL(`username`, `email`) AS `username` FROM `accounts_users` WHERE `id` = :id',
                'execute'  => 'id',
                'label'    => tr('Created by')
            ],
            'created_on' => [
                'display'  => true,
                'disabled' => true,
                'type'     => 'date',
                'label'    => tr('Created on')
            ],
            'meta_id' => [
                'display'  => true,
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
                'type'     => 'numeric',
                'label'    => tr('Authentication failures')
            ],
            'locked_until' => [
                'disabled'      => true,
                'type'          => 'date',
                'null_type'     => 'text',
                'default'       => '-',
                'label'         => tr('Locked until')
            ],
            'sign_in_count' => [
                'disabled' => true,
                'type'     => 'numeric',
                'label'    => tr('Sign in count')
            ],
            'username' => [
                'label'    => tr('Username')
            ],
            'password' => [
                'type'     => 'password',
                'label'    => tr('Password')
            ],
            'fingerprint' => [
                'element'  => null  // TODO Implement
            ],
            'domain' => [
                'label'    => tr('Domain')
            ],
            'title' => [
                'label'    => tr('Title')
            ],
            'firstname' => [
                'label'    => tr('First names')
            ],
            'lastname' => [
                'label'    => tr('Last names')
            ],
            'nickname' => [
                'label'    => tr('Nickname')
            ],
            'avatar' => [
                'label'    => tr('Avatar')
            ],
            'email' => [
                'display'  => true,
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
                'label'     => tr('Verified on'),
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
                'source'   => 'SELECT `username` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL',
                'execute'  => 'id',
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
                'element'  => 'select',
                'source'   => 'SELECT `id`, `name` FROM `geo_countries` WHERE `status` IS NULL',
                'label'    => tr('Country')
            ],
            'states_id' => [
                'element'  => 'select',
                'source'   => 'SELECT `id`, `name` FROM `geo_states` WHERE `countries_id` = :countries_id AND `status` IS NULL',
                'execute'  => 'countries_id',
                'label'    => tr('State'),
            ],
            'cities_id' => [
                'element'  => 'select',
                'source'   => 'SELECT `id`, `name` FROM `geo_cities` WHERE `states_id` = :states_id AND `status` IS NULL',
                'execute'  => 'states_id',
                'label'    => tr('City'),
            ],
            'redirect' => [
                'label'    => tr('Redirect'),
            ],
            'language' => [
                'element'  => function (string $key, array $data, array $source) {
                    return Languages::getHtmlSelect($key)
                      ->setSelected(isset_get($source['language']))
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
            'description' => [
                'element'  => 'text',
                'label'    => tr('Description'),
            ],
            'comments' => [
                'element'  => 'text',
                'label'    => tr('Comments'),
            ],
            'website' => [
                'type'     => 'url',
                'label'    => tr('Website'),
            ],
            'timezone' => [
                'source'   => 'SELECT `seo_name`, `name` FROM `geo_timezones`',
                'label'    => tr('Timezone'),
            ],
            'companies_id' => [
                'source'   => 'SELECT `id`, `name` FROM `business_companies`',
                'label'    => tr('Company'),
            ]
        ];

        $this->form_keys = [
            'id'                      => 12,
            'created_by'              => 6,
            'created_on'              => 6,
            'meta_id'                 => 6,
            'status'                  => 6,
            'last_sign_in'            => 6,
            'sign_in_count'           => 6,
            'authentication_failures' => 6,
            'locked_until'            => 6,
            'email'                   => 6,
            'domain'                  => 6,
            'username'                => 6,
            'nickname'                => 6,
            'firstname'               => 6,
            'lastname'                => 6,
            'gender'                  => 6,
            'birthday'                => 6,
            'title'                   => 6,
            'phones'                  => 6,
            'address'                 => 12,
            'avatar'                  => 12,
            'keywords'                => 12,
            'code'                    => 12,
            'website'                 => 12,
            'leaders_id'              => 6,
            'is_leader'               => 6,
            'latitude'                => 6,
            'longitude'               => 6,
            'offset_latitude'         => 6,
            'offset_longitude'        => 6,
            'accuracy'                => 6,
            'priority'                => 6,
            'language'                => 6,
            'countries_id'            => 6,
            'states_id'               => 6,
            'cities_id'               => 6,
            'redirect'                => 6,
            'timezone'                => 6,
            'verified_on'             => 6,
            'companies_id'            => 6,
            'description'             => 12,
            'comments'                => 12
        ] ;
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
}