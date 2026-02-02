<?php

/**
 * Trait TraitDataStaticBooleanEnabledCli
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


trait TraitDataStaticBooleanEnabledCli
{
    /**
     * Tracks the enabled flag
     *
     * @var bool $enabled_cli
     */
    protected static ?bool $enabled_cli = null;


    /**
     * Returns the enabled flag
     *
     * @return bool
     */
    public static function getEnabledCli(): bool
    {
        return static::$enabled_cli;
    }


    /**
     * Sets the enabled flag
     *
     * @param bool|null $enabled_cli
     *
     * @return void
     */
    public static function setEnabledCli(?bool $enabled_cli): void
    {
        if ($enabled_cli === null) {
            // Do not modify the enabled flag, keep the default
            return;
        }

        static::$enabled_cli = $enabled_cli;
    }
}
