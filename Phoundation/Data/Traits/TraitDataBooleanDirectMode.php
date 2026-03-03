<?php

/**
 * Trait TraitDataAction
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


trait TraitDataBooleanDirectMode
{
    /**
     * Tracks direct mode which, if engaged, will fail immediately on the first field
     *
     * @var bool $direct_mode
     */
    protected bool $direct_mode = false;


    /**
     * Returns the direct-mode configuration setting
     *
     * @return bool
     */
    public static function getConfigDirectMode(): bool
    {
        return config()->getBoolean('security.validation.direct', false);
    }


    /**
     * Returns the direct-mode setting for this validator object
     *
     * @return bool
     */
    public function getDirectMode(): bool
    {
        return $this->direct_mode;
    }


    /**
     * Sets the direct-mode setting for this validator object.
     *
     * NOTE: If null, will use the configured value instead
     *
     * @param bool|null $direct_mode The new value for direct mode. True will enable it, false will disable it, null will use the current configured setting
     *                               instead
     *
     * @return $this
     */
    public function setDirectMode(?bool $direct_mode): static
    {
        $this->direct_mode = ($direct_mode ?? $this->getConfigDirectMode());
        return $this;
    }
}
