<?php

/**
 * Trait TraitDataEntryUserEmail
 *
 * This trait contains methods for DataEntry objects that require a email
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


trait TraitDataEntryUserEmail
{
    /**
     * Cached version of the user
     *
     * @var UserInterface|null $user
     */
    protected ?UserInterface $user = null;


    /**
     * Returns the email for this object
     *
     * @return string|null
     */
    public function getUserEmail(): ?string
    {
        return $this->get('string', 'email');
    }


    /**
     * Sets the email for this object
     *
     * @param string|null $email
     *
     * @return static
     */
    public function setUserEmail(?string $email): static
    {
        return $this->set($email, 'email');
    }


    /**
     * Returns the users_id for this user
     *
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        if (empty($this->user)) {
            $email = $this->getTypesafe('string', 'email');

            if ($email) {
                $this->user = User::load($email);
            }
        }

        return $this->user;
    }
}
