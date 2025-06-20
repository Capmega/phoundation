<?php

/**
 * Trait TraitPathNew
 *
 * This trait contains the ::new() method for PhoPath, PhoDirectory, and PhoFile classes
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


trait TraitPathNew
{
    /**
     * Returns a new class object with the specified restrictions
     *
     * @param Stringable|string                  $source
     * @param PhoRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null        $absolute_prefix
     *
     * @return static
     */
    public static function new(Stringable|string $source, PhoRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false): static
    {
        return new static($source, $restrictions, $absolute_prefix);
    }
}
