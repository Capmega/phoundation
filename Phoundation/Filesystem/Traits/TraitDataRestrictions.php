<?php

/**
 * Trait TraitRestrictions
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

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\PhoRestrictions;


trait TraitDataRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var PhoRestrictionsInterface $o_restrictions
     */
    protected PhoRestrictionsInterface $o_restrictions;


    /**
     * Returns the server restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface
    {
        if (isset($this->o_restrictions)) {
            return $this->o_restrictions;
        }

        throw new OutOfBoundsException(tr('Cannot return file restrictions, restrictions have not yet been set'));
    }


    /**
     * Sets the server and filesystem restrictions for this object
     *
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions The file restrictions to apply to this object
     * @param bool                                       $write          If $restrictions is not specified as a
     *                                                                   FsRestrictions class, but as a path string, or
     *                                                                   array of path strings, then this method will
     *                                                                   convert that into a FsRestrictions object and
     *                                                                   this is the $write modifier for that object
     * @param string|null                                $label          If $restrictions is not specified as a
     *                                                                   FsRestrictions class, but as a path string, or
     *                                                                   array of path strings, then this method will
     *                                                                   convert that into a FsRestrictions object and
     *                                                                   this is the $label modifier for that object
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $o_restrictions = null, bool $write = false, ?string $label = null): static
    {
        $this->o_restrictions = PhoRestrictions::ensureObject($o_restrictions, $write, $label);
        return $this;
    }


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param PhoRestrictionsInterface|null $o_restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function ensureRestrictionsObject(?PhoRestrictionsInterface $o_restrictions): PhoRestrictionsInterface
    {
        if (isset($this->o_restrictions)) {
            return PhoRestrictions::getRestrictionsOrDefaultObject($o_restrictions, $this->o_restrictions);
        }

        return PhoRestrictions::getRestrictionsOrDefaultObject($o_restrictions);
    }
}
