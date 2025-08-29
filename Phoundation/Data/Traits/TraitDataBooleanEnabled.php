<?php

/**
 * Trait TraitDataBooleanEnabled
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opendebug.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataBooleanEnabled
{
    /**
     * Tracks the enabled flag
     *
     * @var bool $enabled
     */
    protected bool $enabled = true;


    /**
     * Returns the enabled flag
     *
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }


    /**
     * Sets the enabled flag
     *
     * @param bool|null $enabled
     *
     * @return static
     */
    public function setEnabled(?bool $enabled): static
    {
        if ($enabled === null) {
            // Don't modify the enabled flag, keep the default
            return $this;
        }

        $this->enabled = $enabled;
        return $this;
    }
}
