<?php

/**
 * Trait TraitDataBoolVerbose
 *
 * This trait adds support for enabling / disabling verbose
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openverbose.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataBoolVerbose
{
    /**
     * Tracks the verbose state
     *
     * @var bool $verbose
     */
    protected bool $verbose = false;


    /**
     * Returns the verbose value
     *
     * @return bool
     */
    public function getVerbose(): bool
    {
        return $this->verbose;
    }


    /**
     * Sets the verbose value
     *
     * @param bool $verbose
     *
     * @return static
     */
    public function setVerbose(bool $verbose): static
    {
        $this->verbose = $verbose;
        return $this;
    }
}
