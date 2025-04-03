<?php

/**
 * Trait TraitDataEntryInitials
 *
 * This trait contains methods for DataEntry objects that require a code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;


trait TraitDataEntryInitials
{
    /**
     * Returns the initials for this user
     *
     * @return string|null
     */
    public function getInitials(): ?string
    {
        return $this->getTypesafe('string', 'initials');
    }


    /**
     * Sets the initials for this user
     *
     * @param string|null $initials
     *
     * @return static
     */
    public function setInitials(?string $initials): static
    {
        return $this->set(get_null($initials), 'initials');
    }
}
