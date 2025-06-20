<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
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
     * Appends the specified user to the data list
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     * @param bool                             $exception
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true): static;

    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static;


    /**
     * Load the data for this users list into the object
     *
     * @param array|string|int|null $identifiers
     * @param bool                  $like
     *
     * @return static
     */
    public function load(array|string|int|null $identifiers = null, bool $like = false): static;

    /**
     * Save the data for this users list in the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;
}
