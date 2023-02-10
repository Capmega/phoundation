<?php

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryTitleDescription
 *
 * This trait contains methods for DataEntry objects that require a title and description
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryTitleDescription
{
    /**
     * Returns the title for this object
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getDataValue('title');
    }



    /**
     * Sets the title for this object
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        return $this->setDataValue('title', $title);
    }



    /**
     * Returns the description for this object
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getDataValue('description');
    }



    /**
     * Sets the description for this object
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        return $this->setDataValue('description', $description);
    }
}