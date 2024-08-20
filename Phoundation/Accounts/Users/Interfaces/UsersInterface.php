<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
use Stringable;

interface UsersInterface extends DataIteratorInterface
{
    /**
     * Set the new users for the current parents to the specified list
     *
     * @param array|null $list
     *
     * @return static
     */
    public function setUsers(?array $list): static;

    /**
     * Add the specified user to the data list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static;

    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static;


    /**
     * Load the data for this users list into the object
     *
     * @param array|null $identifiers
     * @param bool       $clear
     * @param bool       $only_if_empty
     *
     * @return static
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;

    /**
     * Save the data for this users list in the database
     *
     * @return static
     */
    public function save(): static;
}
