<?php

namespace Phoundation\Accounts\Roles\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\SelectInterface;


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
     * Set the entries to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function set(?array $list): static;

    /**
     * Add the specified role to the data list
     *
     * @param RoleInterface|array|string|int|null $role
     * @return static
     */
    public function addRole(RoleInterface|array|string|int|null $role): static;

    /**
     * Remove the specified role from the roles list
     *
     * @param RoleInterface|array|string|int|null $role
     * @return static
     */
    public function remove(RoleInterface|array|string|int|null $role): static;

    /**
     * Remove all rights for this right
     *
     * @return static
     */
    public function clear(): static;

    /**
     * Load the data for this rights list into the object
     *
     * @param string|null $id_column
     * @return static
     */
    public function load(?string $id_column = 'roles_id'): static;

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
     * @return SelectInterface
     */

    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', string $key_column = 'id', ?string $order = null): SelectInterface;
}