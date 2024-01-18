<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntrySource
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntrySourceString
{
    /**
     * Returns the source for this object
     *
     * @return string|null
     */
    public function getSourceString(): ?string
    {
        return $this->getSourceFieldValue('string', 'source');
    }


    /**
     * Sets the source for this object
     *
     * @param string|null $source
     * @return static
     */
    public function setSourceString(?string $source): static
    {
        return $this->setSourceValue('source', $source);
    }
}
