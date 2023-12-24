<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Filesystem\Files;
use Phoundation\Filesystem\Restrictions;
use Stringable;


/**
 * Interface FilesInterface
 *
 * This class manages a list of files that are not necessarily confined to the same directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface FilesInterface
{
    /**
     * Move all files to the specified target
     *
     * @note The specified target MUST be a directory, as multiple files will be moved there
     * @note The specified target either must exist or will be created automatically
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     * @return $this
     */
    public function move(Stringable|string $target, ?RestrictionsInterface $restrictions = null): static;

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
    public function copy(Stringable|string $target, callable $callback, ?RestrictionsInterface $restrictions = null): static;
}
