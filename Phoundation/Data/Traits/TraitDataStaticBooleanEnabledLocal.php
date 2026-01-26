<?php

/**
 * Trait TraitDataStaticBooleanEnabledLocal
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


trait TraitDataStaticBooleanEnabledLocal
{
    /**
     * Tracks the enabled flag
     *
     * @var bool $enabled_local
     */
    protected static ?bool $enabled_local = null;


    /**
     * Returns the enabled flag
     *
     * @return bool
     */
    public static function getEnabledLocal(): bool
    {
        return static::$enabled_local;
    }


    /**
     * Sets the enabled flag
     *
     * @param bool|null $enabled_local
     *
     * @return void
     */
    public static function setEnabledLocal(?bool $enabled_local): void
    {
        if ($enabled_local === null) {
            // Do not modify the enabled flag, keep the default
            return;
        }

        static::$enabled_local = $enabled_local;
    }
}
