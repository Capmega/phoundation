<?php

/**
 * Trait TraitDataMetaEnabled
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


trait TraitDataMetaEnabled
{
    /**
     * Tracks if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @var bool $meta_enabled
     */
    protected bool $meta_enabled = true;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getMetaEnabled(): bool
    {
        return $this->meta_enabled;
    }


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool|null $meta_enabled
     * return static
     */
    public function setMetaEnabled(?bool $meta_enabled): static
    {
        if ($meta_enabled === null) {
            // Don't modify the meta_enabled flag, keep the default
            return $this;
        }

        $this->meta_enabled = $meta_enabled;

        return $this;
    }
}
