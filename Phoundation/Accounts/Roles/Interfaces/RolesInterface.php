<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles\Interfaces;

use Phoundation\Data\DataEntries\Interfaces\DataIteratorInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;

interface RolesInterface extends DataIteratorInterface
{
    /**
     * Set the new roles for the current parents to the specified list
     *
     * @param array|null $list
     *
     * @return static
     */
    public function setRoles(?array $list): static;

    /**
     * Appends the specified role to the data list
     *
     * @param mixed                            $value                   The value to add
     * @param Stringable|string|float|int|null $key              [null] The key under which to store the value. If NULL, the key is determined automatically
     * @param bool                             $skip_null_values [true] If true, will skipp adding the value if it is NULL
     * @param bool                             $exception        [true] If true, will throw an exception if the DataEntry object already exists in this list
     * @param bool                             $auto_save        [true] If true, will ensure the DataEntry object $value is saved before adding it to the list
     *
     * @return static
     *
     * @throws OutOfBoundsException
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
     * Load the data for this roles list into the object
     *
     * @param IdentifierInterface|array|string|int|null $identifiers
     * @param bool                                      $like
     *
     * @return static
     */
    public function load(IdentifierInterface|array|string|int|null $identifiers = null, bool $like = false): static;

    /**
     * Save the data for this "roles" list in the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static;

    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelectOld(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;
}
