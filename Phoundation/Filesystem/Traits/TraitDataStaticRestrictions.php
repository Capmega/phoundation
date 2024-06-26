<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsRestrictions;

/**
 * Trait TraitDataStaticRestrictions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */
trait TraitDataStaticRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var FsRestrictionsInterface $restrictions
     */
    protected static FsRestrictionsInterface $restrictions;


    /**
     * Returns the server restrictions
     *
     * @return FsRestrictionsInterface
     */
    public static function getRestrictions(): FsRestrictionsInterface
    {
        if (isset(static::$restrictions)) {
            return static::$restrictions;
        }

        return FsRestrictions::getRestrictionsOrDefault();
    }


    /**
     * Sets the server and filesystem restrictions for this FsFileFileInterface object
     *
     * @param FsRestrictionsInterface|array|string|null $restrictions The file restrictions to apply to this object
     * @param bool                                      $write        If $restrictions are not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and this
     *                                                                is the $write modifier for that object
     * @param string|null                               $label        If $restrictions are not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and this
     *                                                                is the $label modifier for that object
     *
     * @return void
     */
    public static function setRestrictions(FsRestrictionsInterface|array|string|null $restrictions, bool $write = false, ?string $label = null): void
    {
        static::$restrictions = FsRestrictions::ensure($restrictions, $write, $label);
    }


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return FsRestrictionsInterface
     */
    public static function ensureRestrictions(?FsRestrictionsInterface $restrictions): FsRestrictionsInterface
    {
        if (static::$restrictions) {
            return FsRestrictions::getRestrictionsOrDefault($restrictions, static::$restrictions);
        }

        return FsRestrictions::getRestrictionsOrDefault($restrictions);
    }
}
