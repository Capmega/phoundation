<?php

/**
 * Trait TraitNewDirectory
 *
 * This trait contains just the static new() command without any parameters
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;

trait TraitNewDirectory
{
    /**
     * Returns a new static object that accepts $directory in the constructor
     *
     * @param FsDirectoryInterface|null $directory
     *
     * @return static
     */
    public static function new(FsDirectoryInterface|null $directory = null): static
    {
        return new static($directory);
    }
}
