<?php

/**
 * Trait TraitPathConstructor
 *
 * This trait contains the ::__constructor() and ::new() methods for Directory classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Exception\PathNotDirectoryException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Strings;
use Stringable;


trait TraitDirectoryConstructor
{
    use TraitPathConstructor {
        __construct as protected ___construct;
    }


    /**
     * TraitDirectoryConstructor class constructor
     *
     * @param Stringable|string                 $source
     * @param FsRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null       $absolute_prefix
     */
    public function __construct(Stringable|string $source, FsRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false)
    {
        // Execute Path constructor, then apply directory-specific requirements
        $this->___construct($source, $restrictions, $absolute_prefix);

        // Path must always end with a /
        $this->source = Strings::slash($this->source);

        if (file_exists($this->source)) {
            // This exists, it must be a directory!
            if (!is_dir($this->source)) {
                throw new PathNotDirectoryException(tr('The specified path ":path" exists but is not a directory', [
                    ':path' => $source,
                ]));
            }
        }
    }
}
