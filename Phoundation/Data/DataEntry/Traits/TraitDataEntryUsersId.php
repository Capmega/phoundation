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
use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsFile;


trait TraitDataEntryUsersId
{
    /**
     * Cached version of the user
     *
     * @var UserInterface|null $user
     */
    protected ?UserInterface $user = null;


    /**
     * Returns the users_id for this object
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->getTypeSafe('int', 'users_id');
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     *
     * @return static
     */
    public function setUsersId(int|null $users_id): static
    {
        return $this->set($users_id, 'users_id');
    }


    /**
     * Returns the users_id for this user
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        if (empty($this->user)) {
            $users_id = $this->getTypesafe('int', 'users_id');

            if ($users_id) {
                $this->user = User::load($users_id);
            }
        }

        return $this->user;
    }
}
