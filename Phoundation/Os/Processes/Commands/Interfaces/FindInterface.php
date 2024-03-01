<?php

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Stringable;


/**
 * interface FindInterface
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
     * Sets the path in which to find
     *
     * @param PathInterface|string|null $path
     * @return $this
     */
    public function setPath(PathInterface|string|null $path): static;

    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @param PathInterface|string|null $find_path
     * @return static
     */
    public function setFindPath(PathInterface|string|null $find_path): static;

    /**
     * Returns if find should find empty files
     *
     * @return string|null
     */
    public function getFindPath(): ?string;

    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @param bool $mount
     * @return static
     */
    public function setMount(bool $mount): static;

    /**
     * Returns if find should find empty files
     *
     * @return bool
     */
    public function getMount(): bool;

    /**
     * Returns if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @return bool
     */
    public function getFollowSymlinks(): bool;

    /**
     * Sets if find should find follow_symlinks files
     *
     * @param bool $follow_symlinks
     * @return static
     */
    public function setFollowSymlinks(bool $follow_symlinks): static;

    /**
     * Returns if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     * @return bool
     */
    public function getEmpty(): bool;

    /**
     * Sets if find should find empty files
     *
     * @param bool $empty
     * @return static
     */
    public function setEmpty(bool $empty): static;

    /**
     * Returns the iname
     *
     * @return string|null
     */
    public function getIname(): ?string;

    /**
     * Sets the iname
     *
     * @param string|null $iname
     * @return static
     */
    public function setIname(?string $iname): static;

    /**
     * Returns the size for which to look
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
     * Returns the last modified time in minutes for which to look
     *
     * @return string
     */
    public function getMtime(): string;

    /**
     * Sets the last modified time in minutes for which to find
     *
     * @param Stringable|string $mtime
     * @return $this
     */
    public function setMtime(Stringable|string $mtime): static;

    /**
     * Returns the access time in minutes for which to look
     *
     * @return string
     */
    public function getAtime(): string;

    /**
     * Sets the access time in minutes for which to find
     *
     * @param Stringable|string $atime
     * @return $this
     */
    public function setAtime(Stringable|string $atime): static;

    /**
     * Returns the file status change time in minutes for which to look
     *
     * @return string
     */
    public function getCtime(): string;

    /**
     * Sets the file status change time in minutes for which to find
     *
     * @param Stringable|string $ctime
     * @return $this
     */
    public function setCtime(Stringable|string $ctime): static;

    /**
     * Returns the file types for which to look
     *
     * @return string
     */
    public function getTypes(): string;

    /**
     * Sets the file types in which to find
     *
     * @param Stringable|array|string $types
     * @return $this
     */
    public function setTypes(Stringable|array|string $types): static;

    /**
     * Returns the regex in which to find
     *
     * @return string|null
     */
    public function getRegex(): ?string;

    /**
     * Sets the regex in which to find
     *
     * @param string|null $regex
     * @return $this
     */
    public function setRegex(?string $regex): static;

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
     * Returns what shell command to execute on each file
     *
     * @return string|null
     */
    public function getExec(): ?string;

    /**
     * Sets what shell command to execute on each file
     *
     * @param string|null $exec
     * @return $this
     */
    public function setExec(?string $exec = null): static;

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

    /**
     * Returns an array containing the found files
     *
     * @return array
     */
    public function executeReturnArray(): array;

    /**
     * Returns a Files-object containing the found files
     *
     * @return FilesInterface
     */
    public function executeReturnFiles(): FilesInterface;
}