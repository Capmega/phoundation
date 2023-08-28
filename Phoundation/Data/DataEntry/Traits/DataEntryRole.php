<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryRole
 *
 * This trait contains methods for DataEntry objects that require a role
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryRole
{
    /**
     * Returns the role for this object
     *
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->getSourceValue('string', 'role');
    }


    /**
     * Sets the role for this object
     *
     * @note This method prefixes each role with a "#" symbol to ensure that roles are never seen as numeric, which
     *       would cause issues with $identifier detection, as $identifier can be numeric (ALWAYS id) or non numeric
     *       (The other unique column)
     * @param string|null $role
     * @return static
     */
    public function setRole(?string $role): static
    {
        return $this->setSourceValue('role', $role);
    }
}