<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Roles\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Stringable;


/**
 * Interface RolesInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface RolesInterface extends DataListInterface
{
    /**
     * Set the new roles for the current parents to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function setRoles(?array $list): static;

    /**
     * Add the specified role to the data list
     *
     * @param mixed $value
     * @param Stringable|string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function add(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null = true): static;

    /**
     * Remove the specified role from the roles list
     *
     * @param RoleInterface|Stringable|array|string|float|int $keys
     * @return static
     */
    public function delete(RoleInterface|Stringable|array|string|float|int $keys): static;

    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static;

    /**
     * Load the data for this roles list into the object
     *
     * @param bool $clear
     * @return static
     */
    public function load(bool $clear = true): static;

    /**
     * Save the data for this roles list in the database
     *
     * @return static
     */
    public function save(): static;

    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string $value_column
     * @param string $key_column
     * @param string|null $order
     * @return InputSelectInterface
     */

    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', string $key_column = 'id', ?string $order = null): InputSelectInterface;
}
