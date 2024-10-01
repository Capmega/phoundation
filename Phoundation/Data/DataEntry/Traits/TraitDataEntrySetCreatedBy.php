<?php

/**
 * Trait TraitDataEntrySetCreatedBy
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Accounts\Users\Interfaces\UserInterface;


trait TraitDataEntrySetCreatedBy
{
    /**
     * Returns the user object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     *
     * @param UserInterface|null $user
     *
     * @return static
     */
    public function setCreatedByUserObject(?UserInterface $user): static
    {
        return $this->set($user->getId(), 'created_by');
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
}
