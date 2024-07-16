<?php

/**
 * Trait TraitDataEntryTarget
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryTargetString
{
    /**
     * Returns the target for this object
     *
     * @return string|null
     */
    public function getTargetString(): ?string
    {
        return $this->getValueTypesafe('string', 'target');
    }


    /**
     * Sets the target for this object
     *
     * @param string|null $target
     *
     * @return static
     */
    public function setTargetString(?string $target): static
    {
        return $this->set($target, 'target');
    }
}
