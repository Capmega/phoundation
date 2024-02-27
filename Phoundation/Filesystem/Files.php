<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Utils\Arrays;
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
class Files extends Directory implements FilesInterface
{
    /**
     * Files class constructor
     *
     * @param mixed $files
     * @param RestrictionsInterface|array|string|null $restrictions
     */
    public function __construct(mixed $files = null, RestrictionsInterface|array|string|null $restrictions = null)
    {
        $this->source = Arrays::force($files, null);
        parent::__construct(null, $restrictions);
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
            File::new($file)->movePath($target, $restrictions);
        }

        return $this;
    }


    /**
     * Copy all files to the specified target
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
