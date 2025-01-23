<?php

/**
 * Trait TraitDataIgnoreDeleted
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


trait TraitDataIgnoreDeleted
{
    /**
     * Tracks if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @var bool $ignore_deleted
     */
    protected bool $ignore_deleted = true;


    /**
     * Returns if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @return bool
     */
    public function getIgnoreDeleted(): bool
    {
        return $this->ignore_deleted;
    }


    /**
     * Sets if the meta-system is enabled or disabled for this (type of) DataEntry
     *
     * @param bool|null $ignore_deleted
     * @return static
     */
    public function setIgnoreDeleted(?bool $ignore_deleted): static
    {
        if ($ignore_deleted === null) {
            // Don't modify the ignore_deleted flag, keep the default
            return $this;
        }

        $this->ignore_deleted = $ignore_deleted;
        return $this;
    }
}
