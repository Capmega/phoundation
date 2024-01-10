<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryCollate
 *
 * This trait contains methods for DataEntry objects that require a collate
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryCollate
{
    /**
     * Returns the collate for this object
     *
     * @return string|null
     */
    public function getCollate(): ?string
    {
        return $this->getSourceColumnValue('string', 'collate');
    }


    /**
     * Sets the collate for this object
     *
     * @param string|null $collate
     * @return static
     */
    public function setCollate(?string $collate): static
    {
        return $this->setSourceValue('collate', $collate);
    }
}
