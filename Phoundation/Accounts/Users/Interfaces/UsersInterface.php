<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Accounts\Users\User;
use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Exception\OutOfBoundsException;
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
     * @param mixed                            $value                   The value to add
     * @param Stringable|string|float|int|null $key              [null] The key under which to store the value. If NULL, the key is determined automatically
     * @param bool                             $skip_null_values [true] If true, will skipp adding the value if it is NULL
     * @param bool                             $exception        [true] If true, will throw an exception if the DataEntry object already exists in this list
     * @param bool                             $auto_save        [true] If true, will ensure the DataEntry object $value is saved before adding it to the list
     *
     * @return static
     * @todo Move saving part to ->save(). ->add() should NOT immediately save to database!
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true, bool $auto_save = true): static;

    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static;


    /**
     * Load the data for this users list into the object
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static;

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
