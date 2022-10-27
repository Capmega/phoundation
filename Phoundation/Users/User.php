<?php

namespace Phoundation\Users;

use Phoundation\Data\DataEntry;
use Phoundation\Date\DateTime;
use Phoundation\Geo\City;


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
     * Sets the nickname for this user
     *
     * @return string|null
     */
    public function getNickname(): ?string
    {
        return $this->getDataValue('nickname');
    }

    

    /**
     * Returns the nickname for this user
     *
     * @param string $nickname
     * @return User
     */
    public function setNickname(string $nickname): User
    {
        return $this->setDataValue('nickname', $nickname);
    }



    /**
     * Sets the name for this user
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getDataValue('name');
    }



    /**
     * Returns the name for this user
     *
     * @param string $name
     * @return User
     */
    public function setName(string $name): User
    {
        return $this->setDataValue('name', $name);
    }



    /**
     * Sets the email for this user
     *
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->getDataValue('email');
    }



    /**
     * Returns the email for this user
     *
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): User
    {
        return $this->setDataValue('email', $email);
    }



    /**
     * Sets the username for this user
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getDataValue('username');
    }



    /**
     * Returns the username for this user
     *
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        return $this->setDataValue('username', $username);
    }



    /**
     * Sets the last_signin for this user
     *
     * @return string|null
     */
    public function getLastSignin(): ?string
    {
        return $this->getDataValue('last_signin');
    }



    /**
     * Returns the last_signin for this user
     *
     * @param string $last_signin
     * @return User
     */
    public function setLastSignin(string $last_signin): User
    {
        return $this->setDataValue('last_signin', $last_signin);
    }



    /**
     * Sets the auth_fails for this user
     *
     * @return string|null
     */
    public function getAuthenticationFailures(): ?string
    {
        return $this->getDataValue('auth_fails');
    }



    /**
     * Returns the auth_fails for this user
     *
     * @param string $auth_fails
     * @return User
     */
    public function setAuthenticationFailures(string $auth_fails): User
    {
        return $this->setDataValue('auth_fails', $auth_fails);
    }



    /**
     * Sets the locked_until for this user
     *
     * @return string|null
     */
    public function getLockedUntil(): ?string
    {
        return $this->getDataValue('locked_until');
    }



    /**
     * Returns the locked_until for this user
     *
     * @param string $locked_until
     * @return User
     */
    public function setLockedUntil(string $locked_until): User
    {
        return $this->setDataValue('locked_until', $locked_until);
    }



    /**
     * Sets the signin_count for this user
     *
     * @return string|null
     */
    public function getSigninCount(): ?string
    {
        return $this->getDataValue('signin_count');
    }



    /**
     * Returns the signin_count for this user
     *
     * @param string $signin_count
     * @return User
     */
    public function setSigninCount(string $signin_count): User
    {
        return $this->setDataValue('signin_count', $signin_count);
    }



    /**
     * Sets the fingerprint datetime for this user
     *
     * @return DateTime|null
     */
    public function getFingerprint(): ?DateTime
    {
        $fingerprint = $this->getDataValue('fingerprint');
        return new DateTime($fingerprint);
    }



    /**
     * Returns the fingerprint datetime for this user
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
     * Sets the domain for this user
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->getDataValue('domain');
    }



    /**
     * Returns the domain for this user
     *
     * @param string $domain
     * @return User
     */
    public function setDomain(string $domain): User
    {
        return $this->setDataValue('domain', $domain);
    }



    /**
     * Sets the title for this user
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getDataValue('title');
    }



    /**
     * Returns the title for this user
     *
     * @param string $title
     * @return User
     */
    public function setTitle(string $title): User
    {
        return $this->setDataValue('title', $title);
    }



    /**
     * Sets the avatar for this user
     *
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return $this->getDataValue('avatar');
    }



    /**
     * Returns the avatar for this user
     *
     * @param string $avatar
     * @return User
     */
    public function setAvatar(string $avatar): User
    {
        return $this->setDataValue('avatar', $avatar);
    }



    /**
     * Sets the code for this user
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getDataValue('code');
    }



    /**
     * Returns the code for this user
     *
     * @param string $code
     * @return User
     */
    public function setCode(string $code): User
    {
        return $this->setDataValue('code', $code);
    }



    /**
     * Sets the type for this user
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getDataValue('type');
    }



    /**
     * Returns the type for this user
     *
     * @param string $type
     * @return User
     */
    public function setType(string $type): User
    {
        return $this->setDataValue('type', $type);
    }



    /**
     * Sets the keywords for this user
     *
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->getDataValue('keywords');
    }



    /**
     * Returns the keywords for this user
     *
     * @param string $keywords
     * @return User
     */
    public function setKeywords(string $keywords): User
    {
        return $this->setDataValue('keywords', $keywords);
    }



    /**
     * Sets the phones for this user
     *
     * @return string|null
     */
    public function getPhones(): ?string
    {
        return $this->getDataValue('phones');
    }



    /**
     * Returns the phones for this user
     *
     * @param string $phones
     * @return User
     */
    public function setPhones(string $phones): User
    {
        return $this->setDataValue('phones', $phones);
    }



    /**
     * Sets the address for this user
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->getDataValue('address');
    }



    /**
     * Returns the address for this user
     *
     * @param string $address
     * @return User
     */
    public function setAddress(string $address): User
    {
        return $this->setDataValue('address', $address);
    }



    /**
     * Sets the verification_code for this user
     *
     * @return string|null
     */
    public function getVerificationCode(): ?string
    {
        return $this->getDataValue('verification_code');
    }



    /**
     * Returns the verification_code for this user
     *
     * @param string $verification_code
     * @return User
     */
    public function setVerificationCode(string $verification_code): User
    {
        return $this->setDataValue('verification_code', $verification_code);
    }



    /**
     * Sets the verified_on for this user
     *
     * @return string|null
     */
    public function getverifiedOn(): ?string
    {
        return $this->getDataValue('verified_on');
    }



    /**
     * Returns the verified_on for this user
     *
     * @param string $verified_on
     * @return User
     */
    public function setverifiedOn(string $verified_on): User
    {
        return $this->setDataValue('verified_on', $verified_on);
    }



    /**
     * Sets the priority for this user
     *
     * @return string|null
     */
    public function getPriority(): ?string
    {
        return $this->getDataValue('priority');
    }



    /**
     * Returns the priority for this user
     *
     * @param string $priority
     * @return User
     */
    public function setPriority(string $priority): User
    {
        return $this->setDataValue('priority', $priority);
    }



    /**
     * Sets the is_leader for this user
     *
     * @return string|null
     */
    public function getIsLeader(): ?string
    {
        return $this->getDataValue('is_leader');
    }



    /**
     * Returns the is_leader for this user
     *
     * @param string $is_leader
     * @return User
     */
    public function setIsLeader(string $is_leader): User
    {
        return $this->setDataValue('is_leader', $is_leader);
    }



    /**
     * Sets the leaders_id for this user
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
     * Returns the leaders_id for this user
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
     * Sets the latitude for this user
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->getDataValue('latitude');
    }



    /**
     * Returns the latitude for this user
     *
     * @param float|null $latitude
     * @return User
     */
    public function setLatitude(?float $latitude): User
    {
        return $this->setDataValue('latitude', $latitude);
    }



    /**
     * Sets the longitude for this user
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->getDataValue('longitude');
    }



    /**
     * Returns the longitude for this user
     *
     * @param float|null $longitude
     * @return User
     */
    public function setLongitude(?float $longitude): User
    {
        return $this->setDataValue('longitude', $longitude);
    }



    /**
     * Sets the accuracy for this user
     *
     * @return int|null
     */
    public function getAccuracy(): ?int
    {
        return $this->getDataValue('accuracy');
    }



    /**
     * Returns the accuracy for this user
     *
     * @param int|null $accuracy
     * @return User
     */
    public function setAccuracy(?int $accuracy): User
    {
        return $this->setDataValue('accuracy', $accuracy);
    }



    /**
     * Sets the offset_latitude for this user
     *
     * @return float|null
     */
    public function getOffsetLatitude(): ?float
    {
        return $this->getDataValue('offset_latitude');
    }



    /**
     * Returns the offset_latitude for this user
     *
     * @param float|null $offset_latitude
     * @return User
     */
    public function setOffsetLatitude(?float $offset_latitude): User
    {
        return $this->setDataValue('offset_latitude', $offset_latitude);
    }



    /**
     * Sets the offset_longitude for this user
     *
     * @return float|null
     */
    public function getOffsetLongitude(): ?float
    {
        return $this->getDataValue('offset_longitude');
    }



    /**
     * Returns the offset_longitude for this user
     *
     * @param float|null $offset_longitude
     * @return User
     */
    public function setOffsetLongitude(?float $offset_longitude): User
    {
        return $this->setDataValue('offset_longitude', $offset_longitude);
    }



    /**
     * Sets the cities_id for this user
     *
     * @return City|null
     */
    public function getCity(): ?City
    {
        return $this->getDataValue('cities_id');
    }



    /**
     * Returns the cities_id for this user
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
     * Load all user data from database
     *
     * @param int $id
     * @return void
     */
    protected function load(int $id): void
    {
        $this->data = sql()->get('SELECT * FROM `users` WHERE `id` = :id', [':id' => $id]);
    }
}