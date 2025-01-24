<?php

/**
 * Trait TraitDirectoryNew
 *
 * This trait contains the ::new() method for PhoDirectory, PhoFiles, and PhoPath classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Stringable;


trait TraitDirectoryNew
{
    /**
     * Returns a new Directory object with the specified restrictions
     *
     * @param Stringable|string                  $directory
     * @param PhoRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null        $absolute_prefix
     *
     * @return static
     */
    public static function new(Stringable|string $directory, PhoRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false): static
    {
        return new static($directory, $restrictions, $absolute_prefix);
    }
}
