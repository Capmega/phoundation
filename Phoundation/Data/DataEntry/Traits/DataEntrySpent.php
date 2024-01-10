<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntrySpent
 *
 * This trait contains methods for DataEntry objects that require a spent value
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntrySpent
{
    /**
     * Returns the spent value for this object
     *
     * @return float|null
     */
    public function getSpent(): ?float
    {
        return $this->getSourceColumnValue('float', 'spent');
    }


    /**
     * Sets the spent value for this object
     *
     * @param float|null $spent
     * @return static
     */
    public function setSpent(float|null $spent): static
    {
        return $this->setSourceValue('spent', $spent);
    }
}
