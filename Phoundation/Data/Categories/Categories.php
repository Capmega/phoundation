<?php

namespace Phoundation\Accounts\Categories;

use Phoundation\Data\DataList;



/**
 * Class Categories
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Categories extends DataList
{
    /**
     * Load the data for this categories list
     *
     * @param bool $details
     * @return static
     */
    public function load(bool $details = false): static
    {

        return $this;
    }



    /**
     * Save this categories list
     *
     * @return static
     */
    public function save(): static
    {

        return $this;
    }
}