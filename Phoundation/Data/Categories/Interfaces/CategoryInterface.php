<?php

declare(strict_types=1);

namespace Phoundation\Data\Categories\Interfaces;

use Phoundation\Data\Categories\Category;

interface CategoryInterface
{
    /**
     * Returns the parents_id for this object
     *
     * @return int|null
     */
    public function getParentsId(): ?int;


    /**
     * Sets the parents_id for this object
     *
     * @param int|null $parents_id
     *
     * @return static
     */
    public function setParentsId(?int $parents_id): static;


    /**
     * Returns the parents_id for this user
     *
     * @return Category|null
     */
    public function getParent(): ?Category;


    /**
     * Returns the parents_id for this user
     *
     * @return string|null
     */
    public function getParentsName(): ?string;


    /**
     * Sets the parents_id for this user
     *
     * @param string|null $parents_name
     *
     * @return static
     */
    public function setParentsName(?string $parents_name): static;
}
