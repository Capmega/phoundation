<?php

/**
 * Trait TraitDataPermitValidationFailures
 *
 * This adds permit_validation_failures state registration to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Enums\EnumSoftHard;


trait TraitDataPermitValidationFailures
{
    /**
     * Tracks what validation failures are permitted
     *
     * @var EnumSoftHard $permit_validation_failures
     */
    protected EnumSoftHard $permit_validation_failures = EnumSoftHard::none;


    /**
     * Returns true if this object has the specified permit_validation_failures
     *
     * @param EnumSoftHard $permit
     *
     * @return bool
     */
    public function hasPermitValidationFailures(EnumSoftHard $permit): bool
    {
        return $this->permit_validation_failures === $permit;
    }


    /**
     * Returns if this object is permit_validation_failures or not
     *
     * @return EnumSoftHard
     */
    public function getPermitValidationFailures(): EnumSoftHard
    {
        return $this->permit_validation_failures;
    }


    /**
     * Sets if this object is permit_validation_failures or not
     *
     * @param EnumSoftHard $permit_validation_failures
     *
     * @return static
     */
    public function setPermitValidationFailures(EnumSoftHard $permit_validation_failures): static
    {
        $this->permit_validation_failures = $permit_validation_failures;
        return $this;
    }
}
