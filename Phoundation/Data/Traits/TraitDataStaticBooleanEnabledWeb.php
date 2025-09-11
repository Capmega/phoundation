<?php

/**
 * Trait TraitDataStaticBooleanEnabledweb
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


trait TraitDataStaticBooleanEnabledWeb
{
    /**
     * Tracks the enabled flag
     *
     * @var bool $enabled_web
     */
    protected static ?bool $enabled_web = null;


    /**
     * Returns the enabled flag
     *
     * @return bool
     */
    public static function getEnabledWeb(): bool
    {
        return static::$enabled_web;
    }


    /**
     * Sets the enabled flag
     *
     * @param bool|null $enabled_web
     *
     * @return void
     */
    public static function setEnabledWeb(?bool $enabled_web): void
    {
        if ($enabled_web === null) {
            // Don't modify the enabled flag, keep the default
            return;
        }

        static::$enabled_web = $enabled_web;
    }
}
