<?php

/**
 * Trait TraitDataStaticRestrictions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoRestrictions;


trait TraitDataStaticRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var PhoRestrictionsInterface $restrictions
     */
    protected static PhoRestrictionsInterface $restrictions;


    /**
     * Returns the server restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public static function getRestrictions(): PhoRestrictionsInterface
    {
        if (isset(static::$restrictions)) {
            return static::$restrictions;
        }

        return PhoRestrictions::getRestrictionsOrDefault();
    }


    /**
     * Sets the server and filesystem restrictions for this object
     *
     * @param PhoRestrictionsInterface|array|string|null $restrictions The file restrictions to apply to this object
     * @param bool                                       $write        If $restrictions are not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and this
     *                                                                is the $write modifier for that object
     * @param string|null                                $label        If $restrictions are not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and this
     *                                                                is the $label modifier for that object
     *
     * @return void
     */
    public static function setRestrictions(PhoRestrictionsInterface|array|string|null $restrictions, bool $write = false, ?string $label = null): void
    {
        static::$restrictions = PhoRestrictions::ensure($restrictions, $write, $label);
    }


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param PhoRestrictionsInterface|null $restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public static function ensureRestrictions(?PhoRestrictionsInterface $restrictions): PhoRestrictionsInterface
    {
        if (static::$restrictions) {
            return PhoRestrictions::getRestrictionsOrDefault($restrictions, static::$restrictions);
        }

        return PhoRestrictions::getRestrictionsOrDefault($restrictions);
    }
}
