<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataIteratorInterface;
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
     * Add the specified data entry to the data list
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
     * Remove all rights for this role
     *
     * @return static
     */
    public function clear(): static;


    /**
     * Load the data for this rights list into the object
     *
     * @param array|null $identifiers
     * @param bool       $clear
     * @param bool       $only_if_empty
     *
     * @return static
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static;

    /**
     * Save the data for this rights list in the database
     *
     * @return static
     */
    public function save(): static;

    /**
     * Returns a select with the available rights
     *
     * @return InputSelect
     */
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', ?string $key_column = 'seo_name', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface;
}
