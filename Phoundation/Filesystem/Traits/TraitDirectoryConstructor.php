<?php

/**
 * Trait TraitPathConstructor
 *
 * This trait contains the ::__constructor() and ::new() methods for Directory classes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Filesystem\Exception\NoRestrictionsSpecifiedExceptions;
use Phoundation\Filesystem\Exception\PathNotDirectoryException;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Utils\Strings;
use Stringable;

trait TraitDirectoryConstructor
{
    /**
     * TraitDirectoryConstructor class constructor
     *
     * @param Stringable|string|null            $directory
     * @param FsRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null       $absolute_prefix
     */
    public function __construct(Stringable|string|null $directory, FsRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false) {
        if ($directory instanceof FsPathInterface) {
            // The Specified file is a Directory object
            $this->setPath($directory, absolute_prefix: $absolute_prefix);
            $this->setTarget($directory->getTarget());
            $this->setRestrictions($restrictions ?? $directory->getRestrictions());

        } else {
            if (empty($restrictions)) {
                throw new NoRestrictionsSpecifiedExceptions(
                    tr('Cannot create FsDirectory object for path ":path", no restrictions were specified.', [
                        ':path' => $directory
                    ])
                );
            }

            $this->setPath($directory, absolute_prefix: $absolute_prefix);
            $this->setRestrictions($restrictions);
        }

        // Path must always end with a /
        $this->path = Strings::slash($this->path);

        if (file_exists($this->path)) {
            // This exists, it must be a directory!
            if (!is_dir($this->path)) {
                throw new PathNotDirectoryException(tr('The specified path ":path" is not a directory', [
                    ':path' => $directory,
                ]));
            }
        }
    }
}
