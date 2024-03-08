<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;


/**
 * Interface RightsInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface RightsInterface extends DataListInterface
{
    /**
     * Set the new rights for the current parents to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function setRights(?array $list): static;

    /**
     * Add the specified data entry to the data list
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @param bool $exception
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true, bool $exception = true): static;

    /**
     * Remove the specified data entry from the data list
     *
     * @param RightInterface|Stringable|array|string|float|int $keys
     * @return static
     */
    public function deleteKeys(RightInterface|Stringable|array|string|float|int $keys): static;

    /**
     * Remove all rights for this role
     *
     * @return static
     */
    public function clear(): static;

    /**
     * Load the data for this rights list into the object
     *
     * @param bool $clear
     * @param bool $only_if_empty
     * @return static
     */
    public function load(bool $clear = true, bool $only_if_empty = false): static;

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
