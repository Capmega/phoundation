<?php

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryUsersId
 *
 * This trait contains methods for DataEntry objects that require a users_id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return $this->getDataValue('users_id');
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     * @return static
     */
    public function setUsersId(?int $users_id): static
    {
        if (is_numeric($users_id) and ($users_id < 1)) {
            throw new OutOfBoundsException(tr('Specified users_id ":users_id" is invalid, it should be a number 1 or higher', [
                ':users_id' => $users_id
            ]));
        }

        return $this->setDataValue('users_id', $users_id);
    }
}