<?php

namespace Phoundation\Data\DataEntry\Traits;



/**
 * Trait DataEntryDescription
 *
 * This trait contains methods for DataEntry objects that require a description
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryDescription
{
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