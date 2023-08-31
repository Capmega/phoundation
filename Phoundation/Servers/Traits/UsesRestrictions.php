<?php

declare(strict_types=1);

namespace Phoundation\Servers\Traits;

use Phoundation\Core\Core;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Restrictions;


/**
 * UsesServer trait
 *
 * This trait contains basic server access restrictions architecture
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Servers
 */
trait UsesRestrictions
{
    /**
     * The file access permissions
     *
     * @var Restrictions $restrictions
     */
    protected Restrictions $restrictions;


    /**
     * Returns the server and filesystem restrictions for this File object
     *
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions
    {
        return $this->restrictions;
    }


    /**
     * Sets the server and filesystem restrictions for this File object
     *
     * @param RestrictionsInterface|array|string|null $restrictions  The file restrictions to apply to this object
     * @param bool $write                                   If $restrictions is not specified as a Restrictions class,
     *                                                      but as a path string, or array of path strings, then this
     *                                                      method will convert that into a Restrictions object and this
     *                                                      is the $write modifier for that object
     * @param string|null $label                            If $restrictions is not specified as a Restrictions class,
     *                                                      but as a path string, or array of path strings, then this
     *                                                      method will convert that into a Restrictions object and this
     *                                                      is the $label modifier for that object
     * @return static
     */
    public function setRestrictions(RestrictionsInterface|array|string|null $restrictions = null, bool $write = false, ?string $label = null): static
    {
        $this->restrictions = Restrictions::ensure($restrictions, $write, $label);
        return $this;
    }
}
