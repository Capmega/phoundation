<?php

/**
 * Trait TraitDataRestrictions
 *
 * This adds filesystem restrictions to objects
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsRestrictions;

trait TraitDataRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var FsRestrictionsInterface|null $restrictions
     */
    protected ?FsRestrictionsInterface $restrictions = null;


    /**
     * Returns the server restrictions
     *
     * @return FsRestrictionsInterface
     */
    public function getRestrictions(): FsRestrictionsInterface
    {
        return $this->restrictions;
    }


    /**
     * Sets the server and filesystem restrictions for this FsFileFileInterface object
     *
     * @param FsRestrictionsInterface|array|string|null $restrictions The file restrictions to apply to this object
     * @param bool                                      $write        If $restrictions is not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and this
     *                                                                is the $write modifier for that object
     * @param string|null                               $label        If $restrictions is not specified as a
     *                                                                FsRestrictions class, but as a path string, or
     *                                                                array of path strings, then this method will
     *                                                                convert that into a FsRestrictions object and this
     *                                                                is the $label modifier for that object
     */
    public function setRestrictions(FsRestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static
    {
        $this->restrictions = FsRestrictions::ensure($restrictions, $write, $label);

        return $this;
    }


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return FsRestrictionsInterface
     */
    public function ensureRestrictions(?FsRestrictionsInterface $restrictions): FsRestrictionsInterface
    {
        return FsRestrictions::getRestrictionsOrDefault($restrictions, $this->restrictions);
    }
}
