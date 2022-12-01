<?php

namespace Phoundation\Data\Categories;

use Phoundation\Data\DataEntry;
use Phoundation\Data\DataList;



/**
 * Class Categories
 *
 *
 *
 * @see \Phoundation\Data\DataList
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
class Categories extends DataList
{
    /**
     * DataList class constructor
     *
     * @param DataEntry|null $parent
     */
    public function __construct(?DataEntry $parent = null)
    {
        $this->entry_class = Category::class;
        parent::__construct($parent);
    }



    /**
     * Load the data for this categories list
     *
     * @return static
     */
    public function load(): static
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