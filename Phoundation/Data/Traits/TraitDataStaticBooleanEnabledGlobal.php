<?php

/**
 * Trait TraitDataStaticBooleanEnabledGlobal
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


trait TraitDataStaticBooleanEnabledGlobal
{
    /**
     * Tracks the enabled flag
     *
     * @var bool $enabled_global
     */
    protected static ?bool $enabled_global = null;


    /**
     * Returns the enabled flag
     *
     * @return bool
     */
    public static function getEnabledGlobal(): bool
    {
        return static::$enabled_global;
    }


    /**
     * Sets the enabled flag
     *
     * @param bool|null $enabled_global
     *
     * @return void
     */
    public static function setEnabledGlobal(?bool $enabled_global): void
    {
        if ($enabled_global === null) {
            // Don't modify the enabled flag, keep the default
            return;
        }

        static::$enabled_global = $enabled_global;
    }
}
