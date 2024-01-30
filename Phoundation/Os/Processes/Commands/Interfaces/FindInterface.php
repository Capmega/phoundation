<?php

namespace Phoundation\Os\Processes\Commands\Interfaces;


use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Os\Processes\Commands\Find;
use Stringable;

/**
 * Class Find
 *
 * This class manages the "find" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Os
 */
interface FindInterface
{
    /**
     * Returns the path in which to find
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Sets the path in which to find
     *
     * @param Stringable|string $path
     * @return $this
     */
    public function setPath(Stringable|string $path): static;

    /**
     * Returns the size in which to find
     *
     * @return string
     */
    public function getSize(): string;

    /**
     * Sets the size in which to find
     *
     * @param Stringable|string $size
     * @return $this
     */
    public function setSize(Stringable|string $size): static;

    /**
     * Returns the depth in which to find
     *
     * @return bool
     */
    public function getDepth(): bool;

    /**
     * Sets the depth in which to find
     *
     * @param bool $depth
     * @return $this
     */
    public function setDepth(bool $depth): static;

    /**
     * Returns the min_depth in which to find
     *
     * @return int|null
     */
    public function getMinDepth(): ?int;

    /**
     * Sets the min_depth in which to find
     *
     * @param int|null $min_depth
     * @return $this
     */
    public function setMinDepth(?int $min_depth): static;

    /**
     * Returns the max_depth in which to find
     *
     * @return int|null
     */
    public function getMaxDepth(): ?int;

    /**
     * Sets the max_depth in which to find
     *
     * @param int|null $max_depth
     * @return $this
     */
    public function setMaxDepth(?int $max_depth): static;

    /**
     * Returns the callback in which to find
     *
     * @return callable|null
     */
    public function getCallback(): ?callable;

    /**
     * Sets the callback in which to find
     *
     * @param callable|null $callback
     * @return $this
     */
    public function setCallback(?callable $callback): static;

    /**
     * Returns the action in which to find
     *
     * @return callable|null
     */
    public function getAction(): ?callable;

    /**
     * Sets the action in which to find
     *
     * @param callable|null $action
     * @param string|null $action_command
     * @return $this
     */
    public function setAction(?callable $action, ?string $action_command = null): static;

    /**
     * Returns the type in which to find
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Sets the type in which to find
     *
     * Allowed types:
     *
     * b      block (buffered) special
     * c      character (unbuffered) special
     * d      directory
     * p      named pipe (FIFO)
     * f      regular file
     * l      symbolic  link;  this  is  never  true if the -L option or the -follow option is in effect, unless the
     *        symbolic link is broken.  If you want to search for symboliclinks when -L is in effect, use -xtype.
     * s      socket
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): static;

    /**
     * Returns a Files-object containing the found files
     *
     * @return FilesInterface
     */
    public function getFoundFiles(): FilesInterface;
}
