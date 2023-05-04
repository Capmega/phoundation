<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryNumber
 *
 * This trait contains methods for DataEntry objects that require a number
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryNumber
{
    /**
     * Returns the number for this object
     *
     * @return int|null
     */
    public function getNumber(): ?int
    {
        return $this->getDataValue('number');
    }


    /**
     * Sets the number for this object
     *
     * @param string|null $number
     * @return static
     */
    public function setNumber(?string $number): static
    {
        return $this->setDataValue('number', $number);
    }
}