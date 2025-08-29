<?php

/**
 * Trait TraitDataStaticBooleanEnabled
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


trait TraitDataStaticBooleanEnabled
{
    /**
     * Tracks the enabled flag
     *
     * @var bool $enabled
     */
    protected static bool $enabled = true;


    /**
     * Returns the enabled flag
     *
     * @return bool
     */
    public static function getEnabled(): bool
    {
        return static::$enabled;
    }


    /**
     * Sets the enabled flag
     *
     * @param bool|null $enabled
     *
     * @return void
     */
    public static function setEnabled(?bool $enabled): void
    {
        if ($enabled === null) {
            // Don't modify the enabled flag, keep the default
            return;
        }

        static::$enabled = $enabled;
    }
}
