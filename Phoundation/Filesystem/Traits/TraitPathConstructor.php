<?php

/**
 * Trait TraitPathConstructor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\NoRestrictionsSpecifiedExceptions;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Stringable;

trait TraitPathConstructor
{
    /**
     * TraitPathConstructor class constructor
     *
     * @param Stringable|string|null            $source
     * @param FsRestrictionsInterface|bool|null $restrictions
     * @param Stringable|string|bool|null       $absolute_prefix
     */
    public function __construct(Stringable|string|null $source = null, FsRestrictionsInterface|bool|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false)
    {
        if ($source instanceof FsPathInterface) {
            // The Specified file was actually a FsFileFileInterface or Directory object, get the file from there
            $this->setSource($source, $absolute_prefix)
                 ->setTarget($source->getTarget())
                 ->setRestrictions($restrictions ?? $source->getRestrictions());

        } else {
            // Path is specified by string, so we MUST get restrictions separately!
            if (empty($restrictions)) {
                throw new NoRestrictionsSpecifiedExceptions(
                    tr('Cannot create ":class" object for path ":path", no restrictions were specified.', [
                        ':class' => static::class,
                        ':path'  => $source
                    ])
                );
            }

            $source = (string) $source;

            // The Specified file was actually a FsFileFileInterface or Directory object, get the file from there
            if (strlen($source) > 2048) {
                throw new OutOfBoundsException(
                    tr('Specified path ":path" is invalid, the path string should be no longer than 2048 characters', [
                        ':file' => $source,
                    ])
                );
            }

            $this->setSource($source, $absolute_prefix)
                 ->setRestrictions($restrictions);
        }
    }
}
