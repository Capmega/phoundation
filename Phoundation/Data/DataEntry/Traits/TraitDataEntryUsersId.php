<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

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
trait TraitDataEntryUsersId
{
    /**
     * Returns the users_id for this object
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->get('int', 'users_id');
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
        return $this->setSourceValue('users_id', $users_id);
    }
}
