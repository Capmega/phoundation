<?php

/**
 * Trait TraitDataEntryUsersId
 *
 * This trait contains methods for DataEntry objects that require a users_id
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;

trait TraitDataEntryUser
{
    /**
     * Returns the users_id for this object
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->getValueTypesafe('int', 'users_id');
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     *
     * @return static
     */
    public function setUsersId(?int $users_id): static
    {
        return $this->set($users_id, 'users_id');
    }


    /**
     * Returns the users_id for this user
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        $users_id = $this->getValueTypesafe('int', 'users_id');
        if ($users_id) {
            return User::load($users_id, 'id');
        }

        return null;
    }


    /**
     * Returns the users_email for this user
     *
     * @return string|null
     */
    public function getUsersEmail(): ?string
    {
        return $this->getValueTypesafe('string', 'users_email');
    }


    /**
     * Sets the users_email for this user
     *
     * @param string|null $users_email
     *
     * @return static
     */
    public function setUsersEmail(?string $users_email): static
    {
        return $this->set($users_email, 'users_email');
    }
}
