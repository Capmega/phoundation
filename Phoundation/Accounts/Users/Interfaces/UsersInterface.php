<?php

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Stringable;


/**
 * Interface UsersInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface UsersInterface extends DataListInterface
{
    /**
     * Set the new users for the current parents to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function setUsers(?array $list): static;

    /**
     * Add the specified user to the data list
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true): static;

    /**
     * Remove the specified data entry from the data list
     *
     * @param User|Stringable|array|string|float|int $user
     * @return static
     */
    public function delete(User|Stringable|array|string|float|int $user): static;

    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static;

    /**
     * Load the data for this rights list into the object
     *
     * @return static
     */
    public function load(): static;

    /**
     * Save the data for this rights list in the database
     *
     * @return static
     */
    public function save(): static;
}
