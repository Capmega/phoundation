<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataUsersId
 *
 * This trait contains methods for Data objects that require a users_id
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataUsersId
{
    /**
     * @var int|null $users_id
     */
    protected ?int $users_id = null;


    /**
     * Returns the users_id for this object
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->users_id;
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     * @return static
     */
    public function setUsersId(int|null $users_id): static
    {
        $this->users_id = get_null($users_id);
        return $this;
    }
}