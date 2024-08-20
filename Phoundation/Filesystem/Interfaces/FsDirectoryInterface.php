<?php

/**
 * Interface FsDirectoryInterface
 *
 * This class represents a single directory and contains various methods to manipulate directories.
 *
 * It can rename, copy, traverse, mount, and much more
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Exception\DirectoryNotMountedException;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Stringable;
use Throwable;

interface FsDirectoryInterface extends FsPathInterface
{
    /**
     * Returns the path
     *
     * @param FsPathInterface|string|null $from
     * @param bool                        $remove_terminating_slash
     *
     * @return string|null
     */
    public function getSource(FsPathInterface|string|null $from = null, bool $remove_terminating_slash = false): ?string;

    /**
     * @inheritDoc
     */
    public function getRealPath(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): string;

    /**
     * Returns an Execute object to execute callbacks on each file in specified directories
     *
     * @return FsExecuteInterface
     */
    public function execute(): FsExecuteInterface;

    /**
     * Check if the object file exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null    $type          This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e    If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     *
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
     * @param string|null    $type       This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     *
     * @return static
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null): static;

    /**
     * Ensures existence of the specified directory
     *
     * @param string|int|null $mode  octal $mode If the specified $this->directory does not exist, it will be created
     *                               with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean         $clear If set to true, and the specified directory already exists, it will be deleted and
     *                               then re-created
     * @param bool            $sudo
     *
     * @return static
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         * @package   file
     * @version   2.4.16: Added documentation
     */
    public function ensure(string|int|null $mode = null, ?bool $clear = false, bool $sudo = false): static;

    /**
     * Returns true if the object directories are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool;

    /**
     * Delete the directory, and each parent directory until a non-empty directory is encountered
     *
     * @param string|null $until_directory If specified, as a directory, the method will stop deleting upwards when the
     *                                     specified directory is encountered as well. If specified, as true, the method
     *                                     will continue deleting until either FsRestrictions stops it, or a non empty
     *                                     directory has been encountered
     * @param bool        $sudo
     * @param bool        $use_run_file
     *
     * @return void
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more
     *      information
     *
     */
    public function clearDirectory(?string $until_directory = null, bool $sudo = false, bool $use_run_file = true): void;

    /**
     * Creates a random directory in specified base directory (If it does not exist yet), and returns that directory
     *
     * @param bool $single
     * @param int  $length
     *
     * @return string
     */
    public function createTarget(?bool $single = null, int $length = 0): string;

    /**
     * Return all files in this directory
     *
     * @return FsFilesInterface The files
     */
    public function list(): FsFilesInterface;

    /**
     * Return all files in a directory that match the specified pattern with optional recursion.
     *
     * @param array|string|null $filters   One or multiple regex filters
     * @param boolean           $recursive If set to true, return all files below the specified directory, including in
     *                                     sub-directories
     *
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
     *
     * @return string|null
     */
    public function scanUpwardsForFile(string $filename): ?string;

    /**
     * Returns true if the specified file exists in this directory
     *
     * If the object file doesn't exist in the specified directory, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     *
     * @return bool
     */
    public function hasFile(string $filename): bool;

    /**
     * Returns the total size in bytes of the tree under the specified directory
     *
     * @return int The number of bytes this tree takes
     */
    public function treeFileSize(): int;

    /**
     * Returns the number of files under the object directory (directories not included in count)
     *
     * @return int The number of files
     */
    public function treeFileCount(): int;

    /**
     * Returns PHP code statistics for this directory
     *
     * @param bool $recurse
     *
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
     *
     * @return static
     */
    public function ensureWritable(?int $mode = null): static;

    /**
     * Returns the single one file in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool        $allow_multiple
     *
     * @return FsFileInterface
     */
    public function getSingleFile(?string $regex = null, bool $allow_multiple = false): FsFileInterface;

    /**
     * Returns the single one directory in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool        $allow_multiple
     *
     * @return FsDirectoryInterface
     */
    public function getSingleDirectory(?string $regex = null, bool $allow_multiple = false): FsDirectoryInterface;

    /**
     * Returns the number of available files in the current file directory
     *
     * @param bool $recursive
     *
     * @return int
     */
    public function getCount(bool $recursive = true): int;

    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_patterns The single or multiple pattern(s) that should be matched
     * @param int         $glob_flags    Flags for the internal glob() call
     * @param int         $match_flags   Flags for the internal fnmatch() call
     *
     * @return FsFilesInterface          The resulting directory files
     */
    public function scan(?string $file_patterns = null, int $glob_flags = GLOB_MARK, int $match_flags = FNM_PERIOD | FNM_CASEFOLD): FsFilesInterface;

    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_pattern The single or multiple pattern(s) that should be matched
     * @param int         $glob_flags   Flags for the internal glob() call
     *
     * @return FsFilesInterface         The resulting directory files
     */
    public function scanRegex(?string $file_pattern = null, int $glob_flags = GLOB_MARK): FsFilesInterface;

    /**
     * Returns true if this specific directory is mounted from somewhere, false if not mounted, NULL if mounted, but
     * with issues
     *
     * Issues can be either that the .isnotmounted file is visible (which it should NOT be if mounted) or (if specified)
     * $source does not match the mounted source
     *
     * @param array|Stringable|string|null $sources
     *
     * @return bool|null
     */
    public function isMounted(array|Stringable|string|null $sources): ?bool;

    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param array|Stringable|string|null $sources
     *
     * @return static
     * @throws DirectoryNotMountedException
     */
    public function checkMounted(array|Stringable|string|null $sources): static;

    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param array|Stringable|string|null $sources
     * @param array|null                   $options
     * @param string|null                  $filesystem
     *
     * @return static
     */
    public function ensureMounted(array|Stringable|string|null $sources, ?array $options = null, ?string $filesystem = null): static;

    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param Stringable|string|null $source
     * @param string|null            $filesystem
     * @param array|null             $options
     *
     * @return static
     */
    public function mount(Stringable|string|null $source, ?string $filesystem = null, ?array $options = null): static;

    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @return static
     */
    public function unmount(): static;

    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param Stringable|string|null $source
     * @param array|null             $options
     *
     * @return static
     */
    public function bind(Stringable|string|null $source, ?array $options = null): static;

    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @return static
     */
    public function unbind(): static;

    /**
     * Copy this directory with progress notification
     *
     * @param Stringable|string            $target
     * @param FsRestrictionsInterface|null $restrictions
     * @param callable                     $callback
     * @param mixed|null                   $context
     * @param bool                         $recursive
     *
     * @return static
     * @example:
     * FsFile::new($source)->copy($target, function ($notification_code, $severity, $message, $message_code,
     * $bytes_transferred, $bytes_max) { if ($notification_code == STREAM_Notification_PROGRESS) {
     *          // save $bytes_transferred and $bytes_max to file or database
     *      }
     *  });
     */
    public function copy(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null, ?callable $callback = null, mixed $context = null, bool $recursive = true): static;

    /**
     * Returns a new Find object
     *
     * @return FindInterface
     */
    public function find(): FindInterface;

    /**
     * Returns the specified directory added to this directory
     *
     * @param FsPathInterface|string $directory
     *
     * @return FsDirectoryInterface
     */
    public function addDirectory(FsPathInterface|string $directory): FsDirectoryInterface;

    /**
     * Returns true if this path contains any files
     *
     * @return bool
     */
    public function containFiles(): bool;

    /**
     * Returns the specified directory added to this directory
     *
     * @param FsPathInterface|string $file
     *
     * @return FsFileInterface
     */
    public function addFile(FsPathInterface|string $file): FsFileInterface;
}
