<?php

/**
 * Trait TraitDataEntrySeconds
 *
 * This trait contains methods for DataEntry objects that require a seconds field
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntrySeconds
{
    /**
     * Returns the seconds for this object
     *
     * @return int|null
     */
    public function getSeconds(): ?int
    {
        return $this->getTypesafe('int', 'seconds');
    }


    /**
     * Sets the seconds for this object
     *
     * @param int|null $seconds
     *
     * @return static
     */
    public function setSeconds(?int $seconds): static
    {
        return $this->set(get_null($seconds), 'seconds');
    }
}
