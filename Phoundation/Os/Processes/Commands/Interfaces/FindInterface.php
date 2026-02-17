<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands\Interfaces;

use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Stringable;

interface FindInterface extends CommandInterface
{
    /**
     * Returns the path object
     *
     * @return PhoPathInterface|null
     */
    public function getPathObject(): ?PhoPathInterface;

    /**
     * Sets the path in which to find
     *
     * @param PhoPathInterface|null $_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $_path): static;


    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     *
     * @param PhoPathInterface|null $find_path
     *
     * @return static
     */
    public function setFindPath(PhoPathInterface|null $find_path): static;


    /**
     * Returns if find should find empty files
     *
     * @return PhoDirectoryInterface|null
     */
    public function getFindPath(): ?PhoDirectoryInterface;


    /**
     * Sets if find should descend into other filesystems
     *
     * @note This is true by default for security to avoid searching on remote filesystems by accident
     *
     * @param bool $mount
     *
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
     *
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
     *
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
     *
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
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
     *
     * @return static
     */
    public function setType(string $type): static;


    /**
     * Returns a PhoFiles-object containing the found files
     *
     * @return PhoFilesInterface
     */
    public function getFoundFiles(): PhoFilesInterface;


    /**
     * Returns an array containing the found files
     *
     * @return array
     */
    public function executeReturnArray(): array;


    /**
     * Returns a PhoFiles-object containing the found files
     *
     * @return PhoFilesInterface
     */
    public function getFiles(): PhoFilesInterface;

    /**
     * Returns if all found files will be deleted
     *
     * @return bool
     */
    public function getDelete(): bool;

    /**
     * Sets what shell command to execute on each file
     *
     * @param bool $delete
     * @param bool $recursive
     * @return static
     */
    public function setDelete(bool $delete, bool $recursive = false): static;

    /**
     * Returns if permission denied in result set should be ignored or not
     *
     * @return bool
     */
    public function getIgnorePermissionDeniedInResults(): bool;

    /**
     * Sets if permission denied in result set should be ignored or not
     *
     * @param bool $ignore_permission_denied_in_results
     *
     * @return static
     */
    public function setIgnorePermissionDeniedInResults(bool $ignore_permission_denied_in_results): static;

    /**
     * Returns the "permission denied" items in the result set
     *
     * @return array
     */
    public function getResultsWithPermissionDenied(): array;

    /**
     * Returns the "permission denied" items in the result set
     *
     * @return int
     */
    public function getNumberOfResultsWithPermissionDenied(): int;

    /**
     * Returns if the returned files should be objects (true) or just strings (false)
     *
     * @return bool
     */
    public function getReturnObjects(): bool;

    /**
     * Sets if the returned files should be objects (true) or just strings (false)
     *
     * @param bool $return_objects
     *
     * @return static
     */
    public function setReturnObjects(bool $return_objects): static;
}
