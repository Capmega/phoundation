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


trait TraitDataEntryUsersEmail
{
    /**
     * Cached version of the user
     *
     * @var UserInterface|null $user
     */
    protected ?UserInterface $user = null;


    /**
     * Returns the users_email for this user
     *
     * @return string|null
     */
    public function getUsersEmail(): ?string
    {
        return $this->getTypesafe('string', 'users_email');
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


    /**
     * Returns the users_id for this user
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        if (empty($this->user)) {
            $email = $this->getTypesafe('string', 'users_email');

            if ($email) {
                $this->user = User::load($email);
            }
        }

        return $this->user;
    }
}
