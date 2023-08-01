<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryUsersId
 *
 * This trait contains methods for DataEntry objects that require a users_id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryUsersId
{
    /**
     * Returns the users_id for this object
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->getDataValue('int', 'users_id');
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     * @return static
     */
    public function setUsersId(int|null $users_id): static
    {
        return $this->setDataValue('users_id', $users_id);
    }


    /**
     * Returns the users_id for this user
     *
     * @return User|null
     */
    public function getUser(): ?User
    {
        $users_id = $this->getDataValue('int', 'users_id');

        if ($users_id) {
            return new User($users_id);
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
        return $this->getDataValue('string', 'users_email');
    }


    /**
     * Sets the users_email for this user
     *
     * @param string|null $users_email
     * @return static
     */
    public function setUsersEmail(string|null $users_email): static
    {
        return $this->setDataValue('users_email', $users_email);
    }
}