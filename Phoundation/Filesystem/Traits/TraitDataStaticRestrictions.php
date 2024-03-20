<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;


/**
 * Trait TraitDataStaticRestrictions
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package \Phoundation\Developer
 */
trait TraitDataStaticRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var RestrictionsInterface $restrictions
     */
    protected static RestrictionsInterface $restrictions;


    /**
     * Returns the server restrictions
     *
     * @return RestrictionsInterface
     */
    public static function getRestrictions(): RestrictionsInterface
    {
        if (static::$restrictions) {
            return static::$restrictions;
        }

        return Restrictions::default();
    }


    /**
     * Sets the server and filesystem restrictions for this File object
     *
     * @param RestrictionsInterface $restrictions  The file restrictions to apply to this object
     * @param bool $write                          If $restrictions are not specified as a Restrictions class,
     *                                             but as a path string, or array of path strings, then this
     *                                             method will convert that into a Restrictions object and this
     *                                             is the $write modifier for that object
     * @param string|null $label                   If $restrictions are not specified as a Restrictions class,
     *                                             but as a path string, or array of path strings, then this
     *                                             method will convert that into a Restrictions object and this
     *                                             is the $label modifier for that object
     * @return void
     */
    public static function setRestrictions(RestrictionsInterface $restrictions, bool $write = false, ?string $label = null): void
    {
        static::$restrictions = Restrictions::ensure($restrictions, $write, $label);
    }


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param RestrictionsInterface|null $restrictions
     * @return RestrictionsInterface
     */
    public static function ensureRestrictions(?RestrictionsInterface $restrictions): RestrictionsInterface
    {
        if (static::$restrictions) {
            return Restrictions::default($restrictions, static::$restrictions);
        }

        return Restrictions::default($restrictions);
    }
}
