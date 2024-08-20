<?php

/**
 * Trait TraitUsesAttributeMultiple
 *
 * Adds support for multiple data entries to elements
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;


trait TraitUsesAttributeMultiple
{
    /**
     * Sets if this object allows multiple data entries
     *
     * @var bool $multiple
     */
    protected bool $multiple = false;


    /**
     * Returns if this object allows multiple data entries
     *
     * @return bool
     */
    public function getRendered(): bool
    {
        return $this->multiple;
    }


    /**
     * Sets if this object allows multiple data entries
     *
     * @param bool $multiple
     *
     * @return static
     */
    public function setRendered(bool $multiple): static
    {
        $this->multiple = $multiple;

        return $this;
    }
}
