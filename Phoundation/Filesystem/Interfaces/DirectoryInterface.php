<?php

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Filesystem\File;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * interface DirectoryInterface
 *
 * This class represents a single directory and contains various methods to manipulate directories.
 *
 *  It can rename, copy, traverse, mount, and much more
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface DirectoryInterface extends PathInterface
{
    /**
     * Returns an Execute object to execute callbacks on each file in specified directories
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
     * Ensures existence of the specified directories
     *
     * @param string|null $mode octal $mode If the specified $this->directory does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified directory already exists, it will be deleted and then re-created
     * @param bool $sudo
     * @return static
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package file
     * @version 2.4.16: Added documentation
     *
     */
    public function ensure(?string $mode = null, ?bool $clear = false, bool $sudo = false): static;

    /**
     * Returns true if the object directories are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Delete the directory, and each parent directory until a non-empty directory is encountered
     *
     * @param string|null $until_directory If specified as a directory, the method will stop deleting upwards when the specified
     *                                directory is encountered as well. If specified as true, the method will continue
     *                                deleting until either Restrictions stops it, or a non empty directory has been
     *                                encountered
     * @param bool $sudo
     * @param bool $use_run_file
     * @return void
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
     *
     */
    public function clear(?string $until_directory = null, bool $sudo = false, bool $use_run_file = true): void;

    /**
     * Creates a random directory in specified base directory (If it does not exist yet), and returns that directory
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
     * @param boolean $recursive If set to true, return all files below the specified directory, including in sub-directories
     * @return array The matched files
     */
    public function listTree(array|string|null $filters = null, bool $recursive = true): array;

    /**
     * Pick and return a random file name from the specified directory
     *
     * @note This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
     *
     * @return string A random file from a random directory from the object directories
     */
    public function random(): string;

    /**
     * Scan the entire object directory STRING upward for the specified file.
     *
     * If the object file doesn't exist in the specified directory, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     * @return string|null
     */
    public function scanUpwardsForFile(string $filename): ?string;

    /**
     * Returns the total size in bytes of the tree under the specified directory
     *
     * @return int The amount of bytes this tree takes
     */
    public function treeFileSize(): int;

    /**
     * Returns the amount of files under the object directory (directories not included in count)
     *
     * @return int The amount of files
     */
    public function treeFileCount(): int;

    /**
     * Returns PHP code statistics for this directory
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
     * Tars this directory and returns a file object for the tar file
     *
     * @return FileInterface
     */
    public function tar(): FileInterface;

    /**
     * Returns the single one file in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return FileInterface
     */
    public function getSingleFile(?string $regex = null, bool $allow_multiple = false): FileInterface;

    /**
     * Returns the single one directory in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return \Phoundation\Filesystem\Interfaces\DirectoryInterface
     */
    public function getSingleDirectory(?string $regex = null, bool $allow_multiple = false): DirectoryInterface;

    /**
     * Returns the amount of available files in the current file directory
     *
     * @param bool $recursive
     * @return int
     */
    public function getCount(bool $recursive = true): int;

    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_patterns The single or multiple pattern(s) that should be matched
     * @param int $glob_flags Flags for the internal glob() call
     * @param int $match_flags Flags for the internal fnmatch() call
     * @return array                     The resulting file directories
     */
    public function scan(?string $file_patterns = null, int $glob_flags = GLOB_MARK, int $match_flags = FNM_PERIOD | FNM_CASEFOLD): array;

    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_pattern The single or multiple pattern(s) that should be matched
     * @param int $glob_flags Flags for the internal glob() call
     * @return array                    The resulting file directories
     */
    public function scanRegex(?string $file_pattern = null, int $glob_flags = GLOB_MARK): array;

    /**
     * Copy this directory with progress notification
     *
     * @param Stringable|string $target
     * @param callable $callback
     * @param RestrictionsInterface $restrictions
     * @return static
     * @example:
     * File::new($source)->copy($target, function ($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
     *      if ($notification_code == STREAM_Notification_PROGRESS) {
     *          // save $bytes_transferred and $bytes_max to file or database
     *      }
     *  });
     */
    public function copy(Stringable|string $target, callable $callback, RestrictionsInterface $restrictions): static;

    /**
     * Returns the path
     *
     * @param bool $remove_terminating_slash
     * @return string|null
     */
    public function getPath(bool $remove_terminating_slash = false): ?string;

    /**
     * Returns true if the specified file exists in this directory
     *
     * @param PathInterface|string $file
     * @return bool
     */
    public function pathExists(PathInterface|string $file): bool;

    /**
     * Returns the specified file added to this directory
     *
     * @param PathInterface|string $file
     * @return FileInterface
     */
    public function addFile(PathInterface|string $file): FileInterface;

    /**
     * Returns the specified directory added to this directory
     *
     * @param PathInterface|string $directory
     * @return DirectoryInterface
     */
    public function addDirectory(PathInterface|string $directory): DirectoryInterface;
}
