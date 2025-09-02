<?php

/**
 * Trait TraitDataEntryUser
 *
 * This trait contains methods for DataEntry objects that require a user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;


trait TraitDataEntryUser
{
    /**
     * Setup virtual configuration for Users
     *
     * @return static
     */
    protected function addVirtualConfigurationUsers(): static
    {
        return $this->addVirtualConfiguration('users', User::class, [
            'id',
            'email',
        ]);
    }


    /**
     * Returns the users_id column
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->getVirtualData('users', 'int', 'id');
    }


    /**
     * Sets the users_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setUsersId(?int $id): static
    {
        return $this->setVirtualData('users', $id, 'id');
    }


    /**
     * Returns the users_email column
     *
     * @return string|null
     */
    public function getUsersEmail(): ?string
    {
        return $this->getVirtualData('users', 'string', 'email');
    }


    /**
     * Sets the users_email column
     *
     * @param string|null $email
     * @return static
     */
    public function setUsersEmail(?string $email): static
    {
        return $this->setVirtualData('users', $email, 'email');
    }


    /**
     * Returns the User Object
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        return $this->getVirtualObject('users');
    }


    /**
     * Returns the users_id for this user
     *
     * @param UserInterface|null $o_object
     *
     * @return static
     */
    public function setUserObject(?UserInterface $o_object): static
    {
        return $this->setVirtualObject('users', $o_object);
    }
}
