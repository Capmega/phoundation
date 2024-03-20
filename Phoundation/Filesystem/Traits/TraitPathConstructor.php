<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Stringable;


/**
 * Trait TraitPathConstructor
 *
 * This trait contains the ::__constructor() and ::new() methods for Path, Directory, and File classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
trait TraitPathConstructor
{
    /**
     * Path class constructor
     *
     * @param mixed $source
     * @param RestrictionsInterface|array|string|null $restrictions
     * @param bool $make_absolute
     */
    public function __construct(mixed $source = null, RestrictionsInterface|array|string|null $restrictions = null, bool $make_absolute = false)
    {
        if (is_null($source) or is_string($source) or ($source instanceof Stringable)) {
            // The Specified file was actually a File or Directory object, get the file from there
            if ($source instanceof PathInterface) {
                $this->setPath($source, make_absolute: $make_absolute);
                $this->setTarget($source->getTarget());
                $this->setRestrictions($source->getRestrictions() ?? $restrictions);

            } else {
                $this->setPath($source, make_absolute: $make_absolute);
                $this->setRestrictions($restrictions);
            }

        } elseif (is_resource($source)) {
            // This is an input stream resource
            $this->stream = $source;
            $this->path   = '?';

        } else {
            throw new OutOfBoundsException(tr('Invalid path ":path" specified. Must be one if PathInterface, Stringable, string, null, or resource', [
                ':path' => $source
            ]));
        }
    }
}
