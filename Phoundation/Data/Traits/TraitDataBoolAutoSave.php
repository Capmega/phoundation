<?php

/**
 * Trait TraitDataBoolAutoSave
 *
 * Adds support for bool $auto_save
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataBoolAutoSave
{
    /**
     * The path to use
     *
     * @var bool $auto_save
     */
    protected bool $auto_save = false;


    /**
     * Returns the auto_save flag
     *
     * @return bool The current $auto_save value
     */
    public function getAutoSave(): bool
    {
        return $this->auto_save;
    }


    /**
     * Sets the auto_save flag
     *
     * @param bool $auto_save The new $auto_save value
     *
     * @return static
     */
    public function setAutoSave(bool $auto_save): static
    {
        $this->auto_save = $auto_save;
        return $this;
    }
}
