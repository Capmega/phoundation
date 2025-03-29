<?php

/**
 * Trait TraitDataAutoCreate
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


trait TraitDataAutoCreate
{
    /**
     * Tracks the auto_create flag
     *
     * @var bool $auto_create
     */
    protected bool $auto_create = false;


    /**
     * Returns the auto_create flag
     *
     * @return bool
     */
    public function getAutoCreate(): bool
    {
        return $this->auto_create;
    }


    /**
     * Sets the auto_create flag
     *
     * @param bool $auto_create
     *
     * @return static
     */
    public function setAutoCreate(bool $auto_create): static
    {
        $this->auto_create = $auto_create;
        return $this;
    }
}
