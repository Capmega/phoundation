<?php

/**
 * Trait TraitDataVerbose
 *
 * This trait adds support for enabling / disabling verbose in static objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openverbose.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataStaticBoolVerbose
{
    /**
     * Tracks the verbose state
     *
     * @var bool $verbose
     */
    protected static bool $verbose = false;


    /**
     * Returns the verbose value
     *
     * @return bool
     */
    public static function getVerbose(): bool
    {
        return static::$verbose;
    }


    /**
     * Sets the verbose value
     *
     * @param bool $verbose
     *
     * @return void
     */
    public static function setVerbose(bool $verbose): void
    {
        static::$verbose = $verbose;
    }
}
