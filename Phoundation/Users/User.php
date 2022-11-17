<?php

namespace Phoundation\Users;

use Phoundation\Content\Images\Image;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry;
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
 * @package Phoundation\Users
 */
class User
{
    use DataEntry;



    /**
     * User class constructor
     *
     * @param string|int|null $identifier
     */
    public function __construct(string|int|null $identifier = null) {
        if ($identifier) {
            $this->load($identifier);
        }
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
     * @return User
     */
    public function setPicture(Image|string|null $picture): User
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
     * @param string $nickname
     * @return User
     */
    public function setNickname(string $nickname): User
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
        return $this->getDataValue('name');
    }



    /**
     * Sets the name for this user
     *
     * @param string $name
     * @return User
     */
    public function setName(string $name): User
    {
        return $this->setDataValue('name', $name);
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
     * @return User
     */
    public function setEmail(string $email): User
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
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        return $this->setDataValue('username', $username);
    }



    /**
     * Returns the last_signin for this user
     *
     * @return string|null
     */
    public function getLastSignin(): ?string
    {
        return $this->getDataValue('last_signin');
    }



    /**
     * Sets the last_signin for this user
     *
     * @param string $last_signin
     * @return User
     */
    public function setLastSignin(string $last_signin): User
    {
        return $this->setDataValue('last_signin', $last_signin);
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
     * @param string $auth_fails
     * @return User
     */
    public function setAuthenticationFailures(string $auth_fails): User
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
     * @param string $locked_until
     * @return User
     */
    public function setLockedUntil(string $locked_until): User
    {
        return $this->setDataValue('locked_until', $locked_until);
    }



    /**
     * Returns the signin_count for this user
     *
     * @return string|null
     */
    public function getSigninCount(): ?string
    {
        return $this->getDataValue('signin_count');
    }



    /**
     * Sets the signin_count for this user
     *
     * @param string $signin_count
     * @return User
     */
    public function setSigninCount(string $signin_count): User
    {
        return $this->setDataValue('signin_count', $signin_count);
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
     * @return User
     */
    public function setFingerprint(\DateTime|int $fingerprint): User
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
     * @param string $domain
     * @return User
     */
    public function setDomain(string $domain): User
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
     * @param string $title
     * @return User
     */
    public function setTitle(string $title): User
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
     * @param string $avatar
     * @return User
     */
    public function setAvatar(string $avatar): User
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
     * @param string $code
     * @return User
     */
    public function setCode(string $code): User
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
     * @param string $type
     * @return User
     */
    public function setType(string $type): User
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
     * @param string $keywords
     * @return User
     */
    public function setKeywords(string $keywords): User
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
     * @param string $phones
     * @return User
     */
    public function setPhones(string $phones): User
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
     * @param string $address
     * @return User
     */
    public function setAddress(string $address): User
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
     * @param string $verification_code
     * @return User
     */
    public function setVerificationCode(string $verification_code): User
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
     * @param string $verified_on
     * @return User
     */
    public function setverifiedOn(string $verified_on): User
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
     * @param string $priority
     * @return User
     */
    public function setPriority(string $priority): User
    {
        return $this->setDataValue('priority', $priority);
    }



    /**
     * Returns the is_leader for this user
     *
     * @return string|null
     */
    public function getIsLeader(): ?string
    {
        return $this->getDataValue('is_leader');
    }



    /**
     * Sets the is_leader for this user
     *
     * @param string $is_leader
     * @return User
     */
    public function setIsLeader(string $is_leader): User
    {
        return $this->setDataValue('is_leader', $is_leader);
    }



    /**
     * Returns the leaders_id for this user
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
     * Sets the leaders_id for this user
     *
     * @param int|User $leader
     * @return User
     */
    public function setLeader(int|User $leader): User
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
     * @return User
     */
    public function setLatitude(?float $latitude): User
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
     * @return User
     */
    public function setLongitude(?float $longitude): User
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
     * @return User
     */
    public function setAccuracy(?int $accuracy): User
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
     * @return User
     */
    public function setOffsetLatitude(?float $offset_latitude): User
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
     * @return User
     */
    public function setOffsetLongitude(?float $offset_longitude): User
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
     * @return User
     */
    public function setCity(?City $city): User
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
     * @return User
     */
    public function setState(?State $state): User
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
     * @return User
     */
    public function setCountry(?Country $country): User
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
     * @param string $redirect
     * @return User
     */
    public function setRedirect(string $redirect): User
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
     * @param string $language
     * @return User
     */
    public function setLanguage(string $language): User
    {
        if (strlen($language) != 2) {
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
     * @param string $gender
     * @return User
     */
    public function setGender(string $gender): User
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
     * @param string $gender
     * @return User
     */
    public function setBirthday(string $gender): User
    {
        return $this->setDataValue('birthday', $gender);
    }



    /**
     * Returns the description for this user
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getDataValue('description');
    }



    /**
     * Sets the description for this user
     *
     * @param string $gender
     * @return User
     */
    public function setDescription(string $gender): User
    {
        return $this->setDataValue('description', $gender);
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
     * @param string $gender
     * @return User
     */
    public function setComments(string $gender): User
    {
        return $this->setDataValue('comments', $gender);
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
     * @param string $gender
     * @return User
     */
    public function setWebsite(string $gender): User
    {
        return $this->setDataValue('website', $gender);
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
     * @param string $gender
     * @return User
     */
    public function setTimezone(string $gender): User
    {
        return $this->setDataValue('timezone', $gender);
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
     * Load all user data from database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {
        if (is_integer($identifier)) {
            $data = sql()->get('SELECT * FROM `users` WHERE `id`    = :id'   , [':id'    => $identifier]);
        } else {
            $data = sql()->get('SELECT * FROM `users` WHERE `email` = :email', [':email' => $identifier]);
        }

        // Store all data
        $this->setData($data);
        $this->setMetaData($data);
    }



    /**
     * Save all user data to database
     *
     * @return void
     */
    protected function save(): void
    {
        $this->id = sql()->write('users', $this->getInsertColumns(), $this->getUpdateColumns());
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
            'modified_by',
            'modified_on',
            'meta_id',
            'status',
            'last_signin',
            'auth_fails',
            'locked_until',
            'signin_count',
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
            'timezone'
        ];
    }
}