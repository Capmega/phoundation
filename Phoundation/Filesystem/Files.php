<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Data\Iterator;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Traits\DataBufferSize;
use Phoundation\Filesystem\Traits\DataRestrictions;
use Stringable;


/**
 * Files class
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Files extends Iterator implements FilesInterface
{
    use DataRestrictions;
    use DataBufferSize;


    /**
     * Files class constructor
     *
     * @param mixed $paths
     * @param array|string|Restrictions|null $restrictions
     */
    public function __construct(mixed $paths = null, array|string|Restrictions|null $restrictions = null)
    {
        parent::__construct($paths);
        $this->setRestrictions($restrictions);
    }


    /**
     * Returns a new File object with the specified restrictions
     *
     * @param mixed $paths
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public static function new(mixed $paths = null, RestrictionsInterface|array|string|null $restrictions = null): static
    {
        return new static($paths, $restrictions);
    }


    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     * @return $this
     */
    public function move(Stringable|string $target, ?RestrictionsInterface $restrictions = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);

        Directory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            File::new($file)->move($target, $restrictions);
        }

        return $this;
    }


    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param callable $callback
     * @param RestrictionsInterface|null $restrictions
     * @return $this
     */
    public function copy(Stringable|string $target, callable $callback, ?RestrictionsInterface $restrictions = null): static
    {
        $restrictions = $this->ensureRestrictions($restrictions);

        Directory::new($target, $restrictions)->ensure();

        foreach ($this->source as $file) {
            File::new($file)->copy($target, $callback, $restrictions);
        }

        return $this;
    }
}
