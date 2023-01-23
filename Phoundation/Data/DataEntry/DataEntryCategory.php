<?php

namespace Phoundation\Data\DataEntry;

use Phoundation\Data\Categories\Category;



/**
 * Trait DataEntryCategory
 *
 * This trait contains methods for DataEntry objects that require a category
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCategory
{
    /**
     * Returns the categories_id for this object
     *
     * @return string|null
     */
    public function getCategoriesId(): ?string
    {
        return $this->getDataValue('categories_id');
    }



    /**
     * Sets the categories_id for this object
     *
     * @param string|null $categories_id
     * @return static
     */
    public function setCategoriesId(?string $categories_id): static
    {
        return $this->setDataValue('categories_id', $categories_id);
    }



    /**
     * Returns the categories_id for this user
     *
     * @return Category|null
     */
    public function getCategory(): ?Category
    {
        $categories_id = $this->getDataValue('categories_id');

        if ($categories_id) {
            return new Category($categories_id);
        }

        return null;
    }



    /**
     * Sets the categories_id for this user
     *
     * @param Category|null $category
     * @return static
     */
    public function setCategory(?Category $category): static
    {
        if (is_object($category)) {
            $category = $category->getId();
        }

        return $this->setDataValue('categories_id', $category);
    }
}