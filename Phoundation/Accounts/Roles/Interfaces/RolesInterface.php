<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
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
     * Load the data for this roles list into the object
     *
     * @param array|null $identifiers
     * @param bool       $clear
     * @param bool       $only_if_empty
     *
     * @return static
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;

    /**
     * Save the data for this roles list in the database
     *
     * @return static
     */
    public function save(): static;

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
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;
}
