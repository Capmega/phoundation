<?php

/**
 * Trait TraitDataEntryBranch
 *
 * This trait contains methods for DataEntry objects that require a branch
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Versioning\Git\Traits;


trait TraitDataEntryBranch
{
    /**
     * Returns the branch for this object
     *
     * @return string|null
     */
    public function getBranch(): ?string
    {
        return $this->getTypesafe('string', 'branch');
    }


    /**
     * Sets the branch for this object
     *
     * @param string|null $branch
     *
     * @return static
     */
    public function setBranch(?string $branch): static
    {
        return $this->set(get_null($branch), 'branch');
    }
}
