<?php

/**
 * Trait TraitResourceConstructor
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
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Stringable;

trait TraitResourceConstructor
{
    /**
     * TraitResourceConstructor class constructor
     *
     * @param FsPathInterface|Stringable|string         $source
     * @param FsRestrictionsInterface|array|string|null $restrictions
     * @param Stringable|string|bool|null               $absolute_prefix
     */
    public function __construct(mixed $source, FsRestrictionsInterface|array|string|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false)
    {
        throw new UnderConstructionException();
        if (is_resource($source)) {
            // todo Add support for resources
            // This is an input stream resource
            $this->stream = $source;
            $this->path   = '???';

        } else {
            throw new OutOfBoundsException(
                tr('Invalid path ":path" specified. Must be one if FsPathInterface, Stringable, string, null, or resource', [
                    ':path' => $source,
                ])
            );
        }
    }
}
