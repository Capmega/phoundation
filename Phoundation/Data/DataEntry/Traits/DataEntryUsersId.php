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
     * @param string|int|null $users_id
     * @return static
     */
    public function setUsersId(string|int|null $users_id): static
    {
        if ($users_id and !is_natural($users_id)) {
            throw new OutOfBoundsException(tr('Specified users_id ":id" is not a natural number', [
                ':id' => $users_id
            ]));
        }

        return $this->setDataValue('users_id', get_null(isset_get_typed('integer', $users_id)));
    }
}