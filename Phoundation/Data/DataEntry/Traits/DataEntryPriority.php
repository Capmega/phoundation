<?php

namespace Phoundation\Data\DataEntry\Traits;



/**
 * Trait DataEntryPriority
 *
 * This trait contains methods for DataEntry objects that require a priority
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPriority
{
    /**
     * Returns the priority for this object
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->getDataValue('priority');
    }



    /**
     * Sets the priority for this object
     *
     * @param int|null $priority
     * @return static
     */
    public function setPriority(?int $priority): static
    {
        return $this->setDataValue('priority', $priority);
    }
}