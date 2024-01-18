<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryDescription
 *
 * This trait contains methods for DataEntry objects that require a description
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        return $this->getSourceColumnValue('string', 'description');
    }


    /**
     * Sets the description for this object
     *
     * @param string|null $description
     * @return static
     */
    public function setDescription(?string $description): static
    {
        return $this->setSourceValue('description', $description);
    }
}
