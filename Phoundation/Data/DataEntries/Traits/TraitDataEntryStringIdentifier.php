<?php

/**
 * Trait TraitDataEntryIdentifier
 *
 * This trait contains methods for DataEntry objects that require an identifier field
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryStringIdentifier
{
    /**
     * Returns the comments for this object
     *
     * @return string|null
     */
    public function getIdentifier(): ?string
    {
        return $this->getTypesafe('string', 'identifier');
    }


    /**
     * Sets the comments for this object
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function setIdentifier(?string $comments): static
    {
        return $this->set(get_null($comments), 'identifier');
    }
}
