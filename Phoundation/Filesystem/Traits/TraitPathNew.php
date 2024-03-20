<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Interfaces\RestrictionsInterface;


/**
 * Trait TraitPathNew
 *
 * This trait contains the ::new() method for Path, Directory, and File classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
trait TraitPathNew
{
    /**
     * Returns a new Path object with the specified restrictions
     *
     * @param mixed $source
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $make_absolute
     * @return static
     */
    public static function new(mixed $source = null, RestrictionsInterface|array|string|null $restrictions = null, bool $make_absolute = false): static
    {
        return new static($source, $restrictions, $make_absolute);
    }
}
