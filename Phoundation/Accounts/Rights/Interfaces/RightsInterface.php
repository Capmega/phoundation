<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;

interface RightsInterface extends DataIteratorInterface
{
    /**
     * Set the new rights for the current parents to the specified list
     *
     * @param array|null $list
     *
     * @return static
     */
    public function setRights(?array $list): static;

    /**
     * Appends the specified data entry to the data list
     *
     * @param mixed                            $value                   The value to add
     * @param Stringable|string|float|int|null $key              [null] The key under which to store the value. If NULL, the key is determined automatically
     * @param bool                             $skip_null_values [true] If true, will skipp adding the value if it is NULL
     * @param bool                             $exception        [true] If true, will throw an exception if the DataEntry object already exists in this list
     * @param bool                             $auto_save        [true] If true, will ensure the DataEntry object $value is saved before adding it to the list
     *
     * @return static
     */
    public function append(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true, bool $exception = true, bool $auto_save = true): static;

    /**
     * Remove all rights for this role
     *
     * @return static
     */
    public function clear(): static;


    /**
     * Load the data for this rights list into the object
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static;

    /**
     * Save the data for this "rights" list in the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;

    /**
     * Returns a select with the available rights
     *
     * @return InputSelect
     */
    public function getHtmlSelectOld(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'seo_name', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;

    /**
     * Returns the auto_create flag
     *
     * @return bool
     */
    public function getAutoCreate(): bool;

    /**
     * Sets the auto_create flag
     *
     * @param bool $auto_create
     *
     * @return static
     */
    public function setAutoCreate(bool $auto_create): static;

    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights             The required rights
     * @param string|null  $always_match [god] If specified, if the user has this right, this method will always return true, even if the user does not have the
     *                                         required rights
     *
     * @return bool
     */
    public function hasSome(array|string $rights, ?string $always_match = 'god'): bool;

    /**
     * Returns true if the user has ALL the specified rights
     *
     *
     * @param array|string $rights             The required rights
     * @param string|null  $always_match [god] If specified, if the user has this right, this method will always return true, even if the user does not have the
     *                                         required rights
     *
     * @return bool
     */
    public function hasAll(array|string $rights, ?string $always_match = 'god'): bool;

    /**
     * Returns an array of what rights this user misses
     *
     * @param array|string $rights
     *
     * @return array
     */
    public function getMissing(array|string $rights): array;
}
