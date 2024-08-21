<?php

/**
 * Trait TraitRestrictions
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\FsRestrictions;


trait TraitDataRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var FsRestrictionsInterface $restrictions
     */
    protected FsRestrictionsInterface $restrictions;


    /**
     * Returns the server restrictions
     *
     * @return FsRestrictionsInterface
     */
    public function getRestrictions(): FsRestrictionsInterface
    {
        if (isset($this->restrictions)) {
            return $this->restrictions;
        }

        throw new OutOfBoundsException(tr('Cannot return file restrictions, restrictions have not yet been set'));
    }


    /**
     * Sets the server and filesystem restrictions for this object
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
        if (isset($this->restrictions)) {
            return FsRestrictions::getRestrictionsOrDefault($restrictions, $this->restrictions);
        }

        return FsRestrictions::getRestrictionsOrDefault($restrictions);
    }
}
