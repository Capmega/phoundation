<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\Categories\Category;
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
     * @param string|int|null $categories_id
     * @return static
     */
    public function setCategoriesId(string|int|null $categories_id): static
    {
        if ($categories_id and !is_natural($categories_id)) {
            throw new OutOfBoundsException(tr('Specified categories_id ":id" is not numeric', [
                ':id' => $categories_id
            ]));
        }

        return $this->setDataValue('categories_id', get_null(isset_get_typed('integer', $categories_id)));
    }


    /**
     * Returns the categories_id for this user
     *
     * @return static|null
     */
    public function getCategory(): ?static
    {
        $categories_id = $this->getDataValue('string', 'categories_id');

        if ($categories_id) {
            return new static($categories_id);
        }

        return null;
    }


    /**
     * Sets the categories_id for this user
     *
     * @param Category|string|int|null $category
     * @return static
     */
    public function setCategory(Category|string|int|null $category): static
    {
        if ($category) {
            if (!is_numeric($category)) {
                $category = static::get($category);
            }

            if (is_object($category)) {
                $category = $category->getId();
            }
        }

        return $this->setCategoriesId(get_null($category));
    }
}