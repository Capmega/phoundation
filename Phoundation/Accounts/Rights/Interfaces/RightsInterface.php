<?php

namespace Phoundation\Accounts\Rights\Interfaces;

use Phoundation\Accounts\Rights\Right;
use Phoundation\Data\DataEntry\Interfaces\DataListInterface;
use Phoundation\Web\Http\Html\Components\Input\InputSelect;
use Phoundation\Web\Http\Html\Components\Input\Interfaces\InputSelectInterface;


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
     * Set the new rights for the current parents to the specified list
     *
     * @param array|null $list
     * @return static
     */
    public function setRights(?array $list): static;

    /**
     * Add the specified data entry to the data list
     *
     * @param RightInterface|array|string|int|null $right
     * @return static
     */
    public function addRight(RightInterface|array|string|int|null $right): static;

    /**
     * Remove the specified data entry from the data list
     *
     * @param RightInterface|array|string|float|int $right
     * @return static
     */
    public function remove(RightInterface|array|string|float|int $right): static;

    /**
     * Remove all rights for this role
     *
     * @return static
     */
    public function clear(): static;

    /**
     * Load the data for this rights list into the object
     *
     * @return static
     */
    public function load(): static;

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
    public function getHtmlSelect(string $value_column = 'CONCAT(UPPER(LEFT(`name`, 1)), SUBSTRING(`name`, 2)) AS `name`', string $key_column = 'seo_name', ?string $order = null): InputSelectInterface;
}
