<?php

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Filesystem\Execute;
use Throwable;


/**
 * interface PathInterface
 *
 * This library contains various filesystem path related functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface PathInterface
{
    /**
     * Returns an Execute object to execute callbacks on each file in specified paths
     *
     * @return ExecuteInterface
     */
    public function execute(): ExecuteInterface;

    /**
     * Check if the object file exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     * @return static
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static;

    /**
     * Check if the object file exists and is writable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return static
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null): static;

    /**
     * Ensures existence of the specified path
     *
     * @param string|null $mode octal $mode If the specified $this->path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
     * @return string The specified file
     * @category Function reference
     * @package file
     * @version 2.4.16: Added documentation
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     */
    public function ensure(?string $mode = null, ?bool $clear = false, bool $sudo = false): string;

    /**
     * Returns true if the object paths are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Delete the path, and each parent directory until a non empty directory is encountered
     *
     * @param string|null $until_path
     * @param bool $sudo
     * @param bool $use_run_file
     * @return void
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
     */
    public function clear(?string $until_path = null, bool $sudo = false, bool $use_run_file = true): void;

    /**
     * Creates a random path in specified base path (If it does not exist yet), and returns that path
     *
     * @param bool $single
     * @param int $length
     * @return string
     */
    public function createTarget(?bool $single = null, int $length = 0): string;

    /**
     * Return all files in a directory that match the specified pattern with optional recursion.
     *
     * @param array|string|null $filters One or multiple regex filters
     * @param boolean $recursive If set to true, return all files below the specified path, including in sub-directories
     * @return array The matched files
     */
    public function listTree(array|string|null $filters = null, bool $recursive = true): array;

    /**
     * Pick and return a random file name from the specified path
     *
     * @note This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
     *
     * @return string A random file from a random path from the object paths
     */
    public function random(): string;

    /**
     * Scan the entire object path STRING upward for the specified file.
     *
     * If the object file doesn't exist in the specified path, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     * @return string|null
     */
    public function scanUpwardsForFile(string $filename): ?string;

    /**
     * Returns the total size in bytes of the tree under the specified path
     *
     * @return int The amount of bytes this tree takes
     */
    public function treeFileSize(): int;

    /**
     * Returns the amount of files under the object path (directories not included in count)
     *
     * @return int The amount of files
     */
    public function treeFileCount(): int;

    /**
     * Returns PHP code statistics for this path
     *
     * @param bool $recurse
     * @return array
     */
    public function getPhpStatistics(bool $recurse = false): array;

    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return static
     */
    public function ensureWritable(?int $mode = null): static;

    /**
     * Tars this path and returns a file object for the tar file
     *
     * @return FileInterface
     */
    public function tar(): FileInterface;

    /**
     * Returns the single one file in this path IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return FileInterface
     */
    public function getSingleFile(?string $regex = null, bool $allow_multiple = false): FileInterface;

    /**
     * Returns the single one directory in this path IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return PathInterface
     */
    public function getSingleDirectory(?string $regex = null, bool $allow_multiple = false): PathInterface;

    /**
     * Returns a list of all available files in this path matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_patterns The single or multiple pattern(s) that should be matched
     * @param int $glob_flags Flags for the internal glob() call
     * @param int $match_flags Flags for the internal fnmatch() call
     * @return array                     The resulting file paths
     */
    public function scan(?string $file_patterns = null, int $glob_flags = GLOB_MARK, int $match_flags = FNM_PERIOD | FNM_CASEFOLD): array;
}
