<?php

/**
 * Trait TraitDataInline
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


trait TraitDataInline
{
    /**
     * Tracks if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @var bool $inline
     */
    protected bool $inline = true;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getInline(): bool
    {
        return $this->inline;
    }


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool $inline
     * return static
     */
    public function setInline(bool $inline): static
    {
        $this->inline = $inline;

        return $this;
    }
}
