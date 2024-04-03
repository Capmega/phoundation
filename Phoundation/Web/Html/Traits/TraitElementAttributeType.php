<?php

/**
 * Trait TraitElementAttributeType
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

trait TraitElementAttributeType
{
    /**
     * Returns the type for this element block
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->attributes->get('type');
    }


    /**
     * Sets the type for this element block
     *
     * @param string|null $type
     * @return static
     */
    public function setType(?string $type): static
    {
        return $this->attributes->set($type, 'type');
    }
}
