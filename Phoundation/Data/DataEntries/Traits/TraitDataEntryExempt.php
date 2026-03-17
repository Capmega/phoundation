<?php

/**
 * Trait TraitDataEntryExempt
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryExempt
{
    /**
     * Returns the exempt for this object
     *
     * @return string|null
     */
    public function getExempt(): ?string
    {
        return $this->getTypesafe('string', 'exempt');
    }


    /**
     * Sets the exempt for this object
     *
     * @param string|null $exempt
     *
     * @return static
     */
    public function setExempt(?string $exempt): static
    {
        return $this->set(get_null($exempt), 'exempt');
    }
}
