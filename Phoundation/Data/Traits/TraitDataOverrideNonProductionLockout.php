<?php

/**
 * Trait TraitDataOverrideNonProductionLockout
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataOverrideNonProductionLockout
{
    /**
     * @var bool $override_non_production_lockout
     */
    protected bool $override_non_production_lockout = false;


    /**
     * Returns the source
     *
     * @return bool
     */
    public function getOverrideNonProductionLockout(): bool
    {
        return $this->override_non_production_lockout;
    }


    /**
     * Sets the source
     *
     * @param bool $override
     *
     * @return static
     */
    public function setOverrideNonProductionLockout(bool $override): static
    {
        $this->override_non_production_lockout = $override;
        return $this;
    }
}
