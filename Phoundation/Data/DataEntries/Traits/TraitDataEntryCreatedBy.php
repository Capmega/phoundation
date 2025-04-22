<?php

/**
 * Trait TraitDataEntrySetCreatedBy
 *
 *
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


trait TraitDataEntryCreatedBy
{
    /**
     * Returns the users_id that created this data entry
     *
     * @return int|null
     */
    public function getCreatedBy(): ?int
    {
        return $this->getTypesafe('int', 'created_by');
    }


    /**
     * Sets the users_id that created this data entry
     *
     * @param int|null $users_id
     *
     * @return static
     */
    public function setCreatedBy(?int $users_id): static
    {
        return $this->set($users_id, 'created_by');
    }


    /**
     * Returns the UserInterface that created this object
     *
     * @return UserInterface|null
     */
    public function getCreatedByObject(): ?UserInterface
    {
        return User::new()->loadNull($this->getCreatedBy());
    }

    /**
     * Sets the UserInterface that created this object
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     *
     * @param UserInterface|null $user
     *
     * @return static
     */
    public function setCreatedByObject(?UserInterface $user): static
    {
        return $this->set($user->getId(), 'created_by');
    }
}
