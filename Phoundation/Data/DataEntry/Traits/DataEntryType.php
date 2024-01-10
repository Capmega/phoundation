<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryType
 *
 * This trait contains methods for DataEntry objects that require a type
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryType
{
    /**
     * Returns the type for this object
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getSourceColumnValue('string', 'type');
    }


    /**
     * Sets the type for this object
     *
     * @param string|null $type
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->setSourceValue('type', $type);
    }
}
