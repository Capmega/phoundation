<?php

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;


/**
 * Interface RightsInterface
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
interface RightsInterface extends DataListInterface
{
    /**
     * Set the entries to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function set(?array $list): static;

    /**
     * Add the specified data entry to the data list
     *
     * @param Right|array|string|int|null $right
     * @return static
     */
    public function add(Right|array|string|int|null $right): static;

    /**
     * Remove the specified data entry from the data list
     *
     * @param Right|array|int|null $right
     * @return static
     */
    public function remove(Right|array|int|null $right): static;

    /**
     * Remove all rights for this role
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
    public function load(?string $id_column = 'rights_id'): static;

    /**
     * Save the data for this rights list in the database
     *
     * @return static
     */
    public function save(): static;
}