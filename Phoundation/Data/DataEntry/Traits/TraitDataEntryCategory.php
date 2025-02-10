<?php

/**
 * Trait TraitDataEntryCategory
 *
 * This trait contains methods for DataEntry objects that require a category
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;



use Phoundation\Data\Categories\Category;
use Phoundation\Data\Categories\Interfaces\CategoryInterface;

trait TraitDataEntryCategory
{
    /**
     * Setup virtual configuration for Categories
     *
     * @return static
     */
    protected function addVirtualConfigurationCategories(): static
    {
        return $this->addVirtualConfiguration('categories', Category::class, [
            'id',
            'code',
            'name'
        ]);
    }


    /**
     * Returns the categories_id column
     *
     * @return int|null
     */
    public function getCategoriesId(): ?int
    {
        return $this->getVirtualData('categories', 'int', 'id');
    }


    /**
     * Sets the categories_id column
     *
     * @param int|null $id
     * @return static
     */
    public function setCategoriesId(?int $id): static
    {
        return $this->setVirtualData('categories', $id, 'id');
    }


    /**
     * Returns the categories_code column
     *
     * @return string|null
     */
    public function getCategoriesCode(): ?string
    {
        return $this->getVirtualData('categories', 'string', 'code');
    }


    /**
     * Sets the categories_code column
     *
     * @param string|null $code
     * @return static
     */
    public function setCategoriesCode(?string $code): static
    {
        return $this->setVirtualData('categories', $code, 'code');
    }


    /**
     * Returns the categories_name column
     *
     * @return string|null
     */
    public function getCategoriesName(): ?string
    {
        return $this->getVirtualData('categories', 'string', 'name');
    }


    /**
     * Sets the categories_name column
     *
     * @param string|null $name
     * @return static
     */
    public function setCategoriesName(?string $name): static
    {
        return $this->setVirtualData('categories', $name, 'name');
    }


    /**
     * Returns the Category Object
     *
     * @return CategoryInterface|null
     */
    public function getCategoryObject(): ?CategoryInterface
    {
        return $this->getVirtualObject('categories');
    }


    /**
     * Returns the categories_id for this user
     *
     * @param CategoryInterface|null $o_object
     *
     * @return static
     */
    public function setCategoryObject(?CategoryInterface $o_object): static
    {
        return $this->setVirtualObject('categories', $o_object);
    }
}
