<?php

namespace Phoundation\Data\DataEntry\Traits;



/**
 * Trait DataEntryColor
 *
 * This trait contains methods for DataEntry objects that require a color
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryColor
{
    /**
     * Returns the color for this object
     *
     * @return int|null
     */
    public function getColor(): ?int
    {
        return $this->getDataValue('color');
    }



    /**
     * Sets the color for this object
     *
     * @param string|null $color
     * @return static
     */
    public function setColor(?string $color): static
    {
        return $this->setDataValue('color', $color);
    }
}