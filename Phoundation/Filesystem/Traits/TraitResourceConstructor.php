<?php

/**
 * Trait TraitResourceConstructor
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Stringable;


trait TraitResourceConstructor
{
    /**
     * TraitResourceConstructor class constructor
     *
     * @param PhoPathInterface|Stringable|string         $source
     * @param PhoRestrictionsInterface|array|string|null $restrictions
     * @param Stringable|string|bool|null                $absolute_prefix
     */
    public function __construct(mixed $source, PhoRestrictionsInterface|array|string|null $restrictions = null, Stringable|string|bool|null $absolute_prefix = false)
    {
        throw new UnderConstructionException();
        if (is_resource($source)) {
            // todo Add support for resources
            // This is an input stream resource
            $this->stream = $source;
            $this->path   = '???';

        } else {
            throw new OutOfBoundsException(
                tr('Invalid path ":path" specified. Must be one if PhoPathInterface, Stringable, string, null, or resource', [
                    ':path' => $source,
                ])
            );
        }
    }
}
