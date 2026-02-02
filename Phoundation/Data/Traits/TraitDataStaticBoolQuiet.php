<?php

/**
 * Trait TraitDataQuiet
 *
 * This trait adds support for enabling / disabling "quiet" in static objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openquiet.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataStaticBoolQuiet
{
    /**
     * Tracks the quiet state
     *
     * @var bool $quiet
     */
    protected static bool $quiet = false;


    /**
     * Returns the quiet value
     *
     * @return bool
     */
    public static function getQuiet(): bool
    {
        return static::$quiet;
    }


    /**
     * Sets the quiet value
     *
     * @param bool $quiet
     *
     * @return void
     */
    public static function setQuiet(bool $quiet): void
    {
        static::$quiet = $quiet;
    }
}
