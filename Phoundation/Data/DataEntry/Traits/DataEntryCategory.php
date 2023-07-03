<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\Categories\Category;
use Phoundation\Data\Categories\Interfaces\CategoryInterface;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Trait DataEntryCategory
 *
 * This trait contains methods for DataEntry objects that require a category
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCategory
{
    /**
     * Returns the categories_id for this object
     *
     * @return int|null
     */
    public function getCategoriesId(): ?int
    {
        return $this->getDataValue('int', 'categories_id');
    }


    /**
     * Sets the categories_id for this object
     *
     * @param int|null $categories_id
     * @return static
     */
    public function setCategoriesId(int|null $categories_id): static
    {
        return $this->setDataValue('categories_id', $categories_id);
    }


    /**
     * Returns the category for this object
     *
     * @return CategoryInterface|null
     */
    public function getCategory(): ?CategoryInterface
    {
        $categories_id = $this->getDataValue('string', 'categories_id');

        if ($categories_id) {
            return new Category($categories_id);
        }

        return null;
    }


    /**
     * Returns the categories_name for this object
     *
     * @return string|null
     */
    public function getCategoriesName(): ?string
    {
        return $this->getDataValue('string', 'categories_name');
    }


    /**
     * Returns the categories_name for this object
     *
     * @param string|null $categories_name
     * @return static
     */
    public function setCategoriesName(string|null $categories_name): static
    {
        return $this->setDataValue('categories_name', $categories_name);
    }
}