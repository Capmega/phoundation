<?php

/**
 * Trait TraitDataIdentifier
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;


trait TraitDataIdentifier
{
    /**
     * Tracks if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @var IdentifierInterface|array|string|int|null $identifier
     */
    protected IdentifierInterface|array|string|int|null $identifier = null;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return IdentifierInterface|array|string|int|null
     */
    public function getIdentifier(): IdentifierInterface|array|string|int|null
    {
        return $this->identifier;
    }


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     *
     * @return static
     */
    public function setIdentifier(IdentifierInterface|array|string|int|null $identifier): static
    {
        if ($identifier === null) {
            // Don't modify the identifier flag, keep the default
            return $this;
        }

        $this->identifier = $identifier;
        return $this;
    }
}
