<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;

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
trait TraitDataRestrictions
{
    /**
     * Server object where the image conversion commands will be executed
     *
     * @var RestrictionsInterface $restrictions
     */
    protected RestrictionsInterface $restrictions;


    /**
     * Returns the server restrictions
     *
     * @return RestrictionsInterface
     */
    public function getRestrictions(): RestrictionsInterface
    {
        if (isset($this->restrictions)) {
            return $this->restrictions;
        }
        throw new OutOfBoundsException(tr('Cannot return file restrictions, restrictions have not yet been set'));
    }


    /**
     * Sets the server and filesystem restrictions for this File object
     *
     * @param RestrictionsInterface|array|string|null $restrictions The file restrictions to apply to this object
     * @param bool                                    $write        If $restrictions is not specified as a Restrictions
     *                                                              class, but as a path string, or array of path
     *                                                              strings, then this method will convert that into a
     *                                                              Restrictions object and this is the $write modifier
     *                                                              for that object
     * @param string|null                             $label        If $restrictions is not specified as a Restrictions
     *                                                              class, but as a path string, or array of path
     *                                                              strings, then this method will convert that into a
     *                                                              Restrictions object and this is the $label modifier
     *                                                              for that object
     */
    public function setRestrictions(RestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static
    {
        $this->restrictions = Restrictions::ensure($restrictions, $write, $label);

        return $this;
    }


    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param RestrictionsInterface|null $restrictions
     *
     * @return RestrictionsInterface
     */
    public function ensureRestrictions(?RestrictionsInterface $restrictions): RestrictionsInterface
    {
        if (isset($this->restrictions)) {
            return Restrictions::default($restrictions, $this->restrictions);
        }

        return Restrictions::default($restrictions);
    }
}
