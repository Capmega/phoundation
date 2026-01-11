<?php

/**
 * Interface PhoPathInterface
 *
 * This library contains the basic functionalities to manage filesystem paths
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Date\Interfaces\PhoDateTimeInterface;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\Exception\FileActionFailedException;
use Phoundation\Filesystem\Exception\FileInvalidFormatException;
use Phoundation\Filesystem\Exception\FileNotOpenException;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Commands\Interfaces\ZipInterface;
use Stringable;
use Throwable;


interface PhoPathInterface extends Stringable
{
    /**
     * Returns the extension of the objects path
     *
     * @return string
     */
    public function getExtension(): string;

    /**
     * Returns true if this Path object has the specified extension
     *
     * @param string $extension
     *
     * @return bool
     */
    public function hasExtension(string $extension): bool;

    /**
     * Returns true if this file has the correct extension for its mimetype
     *
     * @return bool
     */
    public function extensionMatchesMimetype(): bool;

    /**
     * Will update the extension of this file if the current extension does not match the mimetype
     *
     * @return static
     */
    public function ensureExtensionMatchesMimetype(): static;

    /**
     * Will update the extension of this file if the current extension does not match the mimetype
     *
     * @return static
     */
    public function checkExtensionMatchesMimetype(): static;

    /**
     * Will remove any extra file extensions to avoid windoos computers fracking shoot up.
     *
     * @return static
     */
    public function ensureSingleExtension(): static;

    /**
     * Will throw a FilesystemExtensionException if this file has multiple extensions
     *
     * @return static
     */
    public function checkSingleExtension(): static;

    /**
     * Returns the basename of this path
     *
     * @return string
     */
    public function getBasename(): string;

    /**
     * Returns the path starting from DIRECTORY_ROOT
     *
     * @return string
     */
    public function getRootname(): string;

    /**
     * Returns the stream for this file if it's opened. Will return NULL if closed
     *
     * @return mixed
     */
    public function getStream(): mixed;


    /**
     * Returns the path
     *
     * @param PhoPathInterface|string|null $from
     * @param bool                         $from_required
     *
     * @return string|null
     */
    public function getSource(PhoPathInterface|string|null $from = null, bool $from_required = false): ?string;

    /**
     * Returns true if this object is the specified path
     *
     * @param string $path
     *
     * @return bool
     */
    public function isPath(string $path): bool;

    /**
     * Sets the file for this Path object
     *
     * @param Stringable|string|null       $path
     * @param \Stringable|string|bool|null $absolute_prefix
     * @param bool                         $must_exist
     *
     * @return static
     */
    public function setSource(Stringable|string|null $path, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): static;

    /**
     * Sets the target file name in case operations create copies of this file
     *
     * @param Stringable|string $target
     *
     * @return static
     */
    public function setTarget(Stringable|string $target): static;

    /**
     * Returns the target file name in case operations create copies of this file
     *
     * @return string|null
     */
    public function getTarget(): ?string;

    /**
     * Checks if the specified file exists
     *
     * @param bool $auto_mount
     * @param bool $check_dead_symlink
     *
     * @return bool
     */
    public function exists(bool $check_dead_symlink = false, bool $auto_mount = true): bool;

    /**
     * Checks if the specified file exists, throws exception if it doesn't
     *
     * @param bool $force
     * @param bool $check_dead_symlink
     * @param bool $auto_mount
     *
     * @return static
     */
    public function checkExists(bool $force = false, bool $check_dead_symlink = false, bool $auto_mount = true): static;

    /**
     * Checks if the specified file does not exist, throws exception if it does
     *
     * @param bool $force
     * @param bool $check_dead_symlink
     * @param bool $auto_mount
     *
     * @return static
     */
    public function checkNotExists(bool $force = false, bool $check_dead_symlink = false, bool $auto_mount = true): static;

    /**
     * Ensures that the path is completely mounted and executes the callback if a mount was made
     *
     * @return bool
     * @todo Add support for recursive auto mounting
     */
    public function attemptAutoMount(): bool;

    /**
     * Renames a file or directory
     *
     * @param Stringable|string $to_filename
     * @param null              $context
     *
     * @return static
     */
    public function rename(Stringable|string $to_filename, $context = null): static;

    /**
     * Truncates a file to a given length
     *
     * @param int $size
     *
     * @return static
     */
    public function truncate(int $size): static;

    /**
     * Output all remaining data on a file pointer to the output buffer
     *
     * @return int The number of bytes
     */
    public function fpassthru(): int;

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
     * Returns true if the path for this Path object is relative (and as such, starts NOT with /)
     *
     * @return bool
     */
    public function isRelative(): bool;

    /**
     * Returns true if the path for this Path object is absolute (and as such, starts with /)
     *
     * @return bool
     */
    public function isAbsolute(): bool;

    /**
     * Returns true if this path can be read
     *
     * @return bool
     */
    public function isReadable(): bool;

    /**
     * Returns true if this path can be written
     *
     * @return bool
     */
    public function isWritable(): bool;

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
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return string
     */
    public function getHumanReadableFileType(): string;

    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileMode(): array;

    /**
     * Returns the mimetype data for the object file
     *
     * @return string The mimetype data for the object file
     * @version 2.4: Added documentation
     */
    public function getMimetype(): string;

    /**
     * Securely delete a file weather it exists or not, without error, using the "shred" command
     *
     * Since shred doesn't have a recursive option, this function will use "find" to find all files matching the
     * specified pattern, and will delete them all
     *
     * @param string|bool $clean_path
     * @param bool        $sudo
     *
     * @return static
     */
    public function secureDelete(string|bool $clean_path = true, bool $sudo = false): static;

    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @param string|bool $clean_path If specified, true, all directories above each specified pattern will be deleted as
     *                                well as long as they are empty. This way, no empty directories will be left lying
     *                                around
     * @param boolean     $sudo       If specified, true, the rm command will be executed using sudo
     * @param bool        $escape     If true, will escape the filename. This may cause issues when using wildcards, for
     *                                example
     * @param bool        $use_run_file
     *
     * @return static
     * @see PhoRestrictions::check() This function uses file location restrictions
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static;

    /**
     * Moves this file to the specified target, will try to ensure target directory exists
     *
     * @param PhoPathInterface     $o_target
     * @param PhoRestrictions|null $restrictions
     *
     * @return static
     */
    public function move(PhoPathInterface $o_target, ?PhoRestrictions $restrictions = null): static;

    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
     *
     * @return string|int
     */
    public function switchMode(string|int $mode): string|int;

    /**
     * Returns the file mode for the object file
     *
     * @return int|null
     */
    public function getRequiredMode(): int|null;

    /**
     * Returns the path octal filemode into a text readable filemode (rwxrwxrwx)
     *
     * @return string
     */
    public function getModeHumanReadable(): string;

    /**
     * Returns the file type
     *
     * @return string|int|null
     */
    public function getType(): string|int|null;

    /**
     * Returns the name of the file type
     *
     * @return string
     */
    public function getTypeName(): string;

    /**
     * Returns all the stat data for the object file, or if key is specified, the value for said key
     *
     * $key can have one of the following values. If a different value is specified, an OutOfBoundsException will be
     * thrown
     *
     * 0  dev     device number ***
     * 1  ino     inode number ****
     * 2  mode    inode protection mode *****
     * 3  nlink   number of links
     * 4  uid     userid of owner *
     * 5  gid     groupid of owner *
     * 6  rdev    device type, if inode device
     * 7  size    size in bytes
     * 8  atime   time of last access (Unix timestamp)
     * 9  mtime   time of last modification (Unix timestamp)
     * 10 ctime   time of last inode change (Unix timestamp)
     * 11 blksize blocksize of filesystem IO **
     * 12 blocks  number of 512-byte blocks allocated **
     *
     * @param string|null $key
     *
     * @return array|int
     * @throws Throwable
     */
    public function getStat(?string $key = null): array|int;

    /**
     * Update the object file owner and group
     *
     * @param string|null $user
     * @param string|null $group
     * @param bool        $recursive
     *
     * @return static
     * @see  $this->chmod()
     *
     * @note This function ALWAYS requires sudo as chown is a root only filesystem command
     */
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false): static;

    /**
     * Change file mode, optionally recursively
     *
     * @param string|int $mode      The mode to apply to the specified file (and all files below if recursive is
     *                              specified)
     * @param boolean    $recursive If set to true, apply specified mode to the specified file and all files below by
     *                              recursion
     * @param bool       $sudo
     *
     * @return static
     * @see $this->chown()
     */
    public function chmod(string|int $mode, bool $recursive = false, bool $sudo = false): static;

    /**
     * Ensure that the object file is readable
     *
     * This method will ensure that the object file will exist and is readable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     *
     * @return bool
     */
    public function ensureFileReadable(?int $mode = null): bool;


    /**
     * Wrapper for realpath() that won't crash with an exception if the specified string is not a real directory
     *
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     * @param bool                        $resolve_basename
     *
     * @return PhoPathInterface string The real path extrapolated from the specified $directory, if exists. False if
     *                         whatever was specified does not exist.
     */
    public function getRealPath(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false, bool $resolve_basename = false): string;

    /**
     * Wrapper for realpath() that won't crash with an exception if the specified string is not a real directory
     *
     * @return PhoPathInterface string The real path extrapolated from the specified $directory, if exists. False if
     *                         whatever was specified does not exist.
     */
    public function getReal(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): PhoPathInterface;

    /**
     * Make this path a real path
     *
     * Real path will resolve all symlinks, requires that the path exists!
     *
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool $must_exist
     * @return static
     */
    public function makeReal(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): static;

    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     *
     * @return bool
     */
    public function ensureFileWritable(?int $mode = null): bool;

    /**
     * Returns the size in bytes of this file or directory
     *
     * @param bool $recursive
     *
     * @return int
     */
    public function getSize(bool $recursive = true): int;

    /**
     * Returns the parent directory for this file
     *
     * @param PhoRestrictionsInterface|null $restrictions
     *
     * @return PhoDirectoryInterface
     */
    public function getParentDirectoryObject(?PhoRestrictionsInterface $restrictions = null): PhoDirectoryInterface;

    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param EnumFileOpenMode $mode
     * @param resource         $context
     *
     * @return static
     */
    public function open(EnumFileOpenMode $mode, $context = null): static;

    /**
     * Returns true if the file is a symlink, whether its target exists or not
     *
     * @return bool
     */
    public function isLink(): bool;

    /**
     * Returns the path that this link points to
     *
     * @param PhoPathInterface|string|bool $absolute_prefix
     *
     * @return PhoPathInterface
     */
    public function readLink(PhoPathInterface|string|bool $absolute_prefix = false): PhoPathInterface;

    /**
     * Wrapper for Path::readlink()
     *
     * @param PhoPathInterface|string|bool $absolute
     *
     * @return PhoPathInterface
     */
    public function getLinkTarget(PhoPathInterface|string|bool $absolute = false): PhoPathInterface;

    /**
     * Returns true if the file is a symlink AND its target exists
     *
     * @return bool
     */
    public function isLinkAndTargetExists(): bool;

    /**
     * Returns true if the file is a directory
     *
     * @return bool
     */
    public function isDirectory(): bool;

    /**
     * Returns true if this file is a FIFO
     *
     * @return bool
     */
    public function isFifo(): bool;

    /**
     * Returns true if this file is a Character device
     *
     * @return bool
     */
    public function isChr(): bool;

    /**
     * Returns true if this file is a block device
     *
     * @return bool
     */
    public function isBlk(): bool;

    /**
     * Returns true if this file is ???
     *
     * @return bool
     */
    public function isReg(): bool;

    /**
     * Returns true if this file is a socket device
     *
     * @return bool
     */
    public function isSock(): bool;

    /**
     * Returns true if the file is opened
     *
     * @return bool
     */
    public function isOpen(): bool;


    /**
     * Creates a symlink $target that points to this file.
     *
     * @note Will return a NEW Path object (PhoFileInterface or PhoDirectory, basically) for the specified target
     *
     * @param PhoPathInterface             $target
     * @param PhoPathInterface|string|bool $make_relative
     *
     * @return PhoPathInterface
     */
    public function symlinkTargetFromThis(PhoPathInterface $target, PhoPathInterface|string|bool $make_relative = true): PhoPathInterface;

    /**
     * Makes this path a symlink that points to the specified target.
     *
     * @note Will return a NEW Path object (PhoFile or PhoDirectory, basically) for the specified target
     *
     * @param PhoPathInterface|string      $target
     * @param PhoPathInterface|string|bool $make_relative
     *
     * @return PhoPathInterface
     */
    public function symlinkThisToTarget(PhoPathInterface|string $target, PhoPathInterface|string|bool $make_relative = true): PhoPathInterface;

    /**
     * Returns true if the file pointer is at EOF
     *
     * @return bool
     */
    public function isEof(): bool;

    /**
     * Returns how the file was opened, NULL if the file is not open
     *
     * @return EnumFileOpenMode|null
     */
    public function getOpenMode(): ?EnumFileOpenMode;

    /**
     * Sets the internal file pointer to the specified offset
     *
     * @param int $offset
     * @param int $whence
     *
     * @return static
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function seek(int $offset, int $whence = SEEK_SET): static;

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function tell(): int;

    /**
     * Rewinds the position of the file pointer
     *
     * @return static
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function rewind(): static;

    /**
     * Reads and returns the specified number of bytes from the current pointer location
     *
     * @param int|null $buffer
     * @param int|null $seek
     *
     * @return string|false
     */
    public function read(?int $buffer = null, ?int $seek = null): string|false;

    /**
     * Reads and returns the next text line in this file
     *
     * @param int|null $buffer
     *
     * @return string|false
     */
    public function readLine(?int $buffer = null): string|false;

    /**
     * Reads line from file pointer and parse for CSV fields
     *
     * @param int|null $max_length
     * @param string   $separator
     * @param string   $enclosure
     * @param string   $escape
     *
     * @return array|false
     */
    public function readCsv(?int $max_length = null, string $separator = ",", string $enclosure = "\"", string $escape = "\\"): array|false;

    /**
     * Reads and returns a single character from the current file pointer
     *
     * @return string|false
     */
    public function readCharacter(): string|false;

    /**
     * Reads and returns the specified number of bytes at the specified location from this CLOSED file
     *
     * @note Will throw an exception if the file is already open
     *
     * @param int $length
     * @param int $start
     *
     * @return string|false
     */
    public function readBytes(int $length, int $start = 0): string|false;

    /**
     * Binary-safe write the specified data to this file
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return static
     */
    public function write(string $data, ?int $length = null): static;

    /**
     * Write the specified data to this
     *
     * @param bool          $use_include_path
     * @param resource|null $context
     * @param int           $offset
     * @param int|null      $length
     *
     * @return static
     */
    public function getContentsAsString(bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string;

    /**
     * Returns the contents of this file as an array
     *
     * @param int $flags
     * @param     $context
     *
     * @return array
     */
    public function getContentsAsArray(int $flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context = null): array;

    /**
     * Returns the contents of this file as an Iterator object
     *
     * @param int $flags
     * @param     $context
     *
     * @return IteratorInterface
     */
    public function getContentsAsIterator(int $flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context = null): IteratorInterface;

    /**
     * Write the specified data to this file
     *
     * @param string $data
     * @param int    $flags
     * @param null   $context
     *
     * @return static
     */
    public function putContents(string $data, int $flags = 0, $context = null): static;

    /**
     * Append specified data string to the end of the object file
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return static
     */
    public function appendData(string $data, ?int $length = null): static;

    /**
     * Create the specified file
     *
     * @param bool $force
     *
     * @return static
     */
    public function create(bool $force = false): static;

    /**
     * Sets access and modification time of file
     *
     * @return static
     */
    public function touch(): static;

    /**
     * Concatenates a list of files to a target file
     *
     * @param string|array $sources The source files
     *
     * @return static
     */
    public function appendFiles(string|array $sources): static;

    /**
     * Closes this file
     *
     * @param bool $force
     *
     * @return static
     */
    public function close(bool $force = false): static;

    /**
     * Synchronizes changes to the file (including meta-data)
     *
     * @return static
     */
    public function sync(): static;

    /**
     * Synchronizes data (but not meta-data) to the file
     *
     * @return static
     */
    public function syncData(): static;

    /**
     * Will overwrite the file with random data before deleting it
     *
     * @param int $passes
     *
     * @return static
     */
    public function shred(int $passes = 3, bool $randomized = false, int $block_size = 4096): static;

    /**
     * Returns the device path of the filesystem where this file is stored
     *
     * @return string
     */
    public function getMountDevice(): string;

    /**
     * Returns a find object that will search for files in the specified path and upon execution returns a files-object
     * that can execute callbacks on said files
     *
     * @return FindInterface
     */
    public function find(): FindInterface;

    /**
     * Returns the relative path between the specified path and this object's path
     *
     * @param PhoPathInterface|string      $target
     * @param PhoPathInterface|string|bool $absolute_prefix
     *
     * @return PhoPathInterface
     */
    public function getRelativePathTo(PhoPathInterface|string $target, PhoPathInterface|string|bool|null $absolute_prefix = null): PhoPathInterface;


    /**
     * Checks restrictions
     *
     * @param bool           $write
     * @param Throwable|null $e
     *
     * @return static
     */
    public function checkRestrictions(bool $write, ?Throwable $e): static;

    /**
     * Replaces the current path by moving it out of the way and moving the target in its place, then deleting the
     * original
     *
     * @param PhoPathInterface|string $o_target
     *
     * @return PhoPathInterface
     */
    public function replaceWithPath(PhoPathInterface|string $o_target): PhoPathInterface;

    /**
     * Ensures that this path is a symlink
     *
     * @return static
     */
    public function checkSymlink(Stringable|string $target): static;

    /**
     * Returns a PhoPathInterface object with the specified path appended to this path
     *
     * @param PhoPathInterface|string     $path
     * @param Stringable|string|bool|null $absolute_prefix
     *
     * @return PhoFileInterface
     */
    public function appendPath(PhoPathInterface|string $path, Stringable|string|bool|null $absolute_prefix = false): PhoPathInterface;

    /**
     * Returns a PhoPathInterface object with the specified path prepended to this path
     *
     * @param PhoPathInterface|string     $path
     * @param Stringable|string|bool|null $absolute_prefix
     *
     * @return PhoFileInterface
     */
    public function prependPath(PhoPathInterface|string $path, Stringable|string|bool|null $absolute_prefix = false): PhoPathInterface;

    /**
     * Returns a PhoFilesInterface object that will contain all the files under this current path
     *
     * @param bool $reload
     *
     * @return PhoFilesInterface
     */
    public function getFilesObject(bool $reload = false): PhoFilesInterface;

    /**
     * Copies all directories as directories and all files as symlinks in the tree starting at this objects path to the
     * specified target,
     *
     * Directories will remain directories, all files will be symlinks
     *
     * @param PhoPathInterface|string       $target
     * @param PhoPathInterface|string|null  $alternate_path
     * @param PhoRestrictionsInterface|null $restrictions
     * @param bool                          $rename
     *
     * @return static
     */
    public function symlinkTreeToTarget(PhoPathInterface|string $target, PhoPathInterface|string|null $alternate_path = null, ?PhoRestrictionsInterface $restrictions = null, bool $rename = false): PhoPathInterface;

    /**
     * Will scan this path for symlinks and delete all of them one by one
     *
     * @return static
     */
    public function clearTreeSymlinks(bool $clean = false): static;

    /**
     * Returns the server restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function getRestrictionsObject(): PhoRestrictionsInterface;

    /**
     * Sets the server and filesystem restrictions for this PhoPath object
     *
     * @param PhoRestrictionsInterface|array|string|null $o_restrictions   The file restrictions to apply to this object
     * @param bool                                       $write            If $restrictions is not specified as a
     *                                                                     FsRestrictions class, but as a path string,
     *                                                                     or array of path strings, then this method
     *                                                                     will convert that into a FsRestrictions
     *                                                                     object and this is the $write modifier for
     *                                                                     that object
     * @param string|null                                $label            If $restrictions is not specified as a
     *                                                                     FsRestrictions class, but as a path string,
     *                                                                     or array of path strings, then this method
     *                                                                     will convert that into a FsRestrictions
     *                                                                     object and this is the $label modifier for
     *                                                                     that object
     */
    public function setRestrictionsObject(PhoRestrictionsInterface|array|string|null $o_restrictions = null, bool $write = false, ?string $label = null): static;

    /**
     * Returns either the specified restrictions, or this object's restrictions, or system default restrictions
     *
     * @param PhoRestrictionsInterface|null $o_restrictions
     *
     * @return PhoRestrictionsInterface
     */
    public function ensureRestrictionsObject(?PhoRestrictionsInterface $o_restrictions): PhoRestrictionsInterface;

    /**
     * Ensures the existence of the parent directory
     *
     * @param string|int|null $mode  octal $mode If the specified $this->directory does not exist, it will be created
     *                               with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean         $clear If set to true, and the specified directory already exists, it will be deleted and
     *                               then re-created
     * @param bool            $sudo
     *
     * @return static
     */
    public function ensureParentDirectory(string|int|null $mode = null, ?bool $clear = false, bool $sudo = false): static;

    /**
     * Returns true if the path for this object is NULL (not set)
     *
     * @return bool
     */
    public function isNull(): bool;

    /**
     * Returns true if the path for this object is NOT NULL (is set)
     *
     * @return bool
     */
    public function isSet(): bool;

    /**
     * Returns the filesystem for the current path
     *
     * @return PhoFilesystemInterface
     */
    public function getFilesystemObject(): PhoFilesystemInterface;

    /**
     * Returns true if this file is stored on an encrypted filesystem
     *
     * @return bool
     */
    public function isEncrypted(): bool;

    /**
     * Returns true or false if file is ASCII or not
     *
     * @return bool True if the file is a text file, false if not
     * @version 2.4: Added documentation
     */
    public function isText(): bool;

    /**
     * Returns true or false if file is ASCII or not
     *
     * @return bool True if the file is a text file, false if not
     */
    public function isBinary(): bool;

    /**
     * Returns the file mode for the object file in octal mode
     *
     * @return string
     */
    public function getOctalMode(): string;

    /**
     * Returns the file mode permission for the object file in octal form
     *
     * @return string
     */
    public function getModePermissions(): string;

    /**
     * Returns the file owner UID
     *
     * @return int
     */
    public function getOwnerUid(): int;

    /**
     * Returns the file owner UID
     *
     * @return int
     */
    public function getGroupUid(): int;

    /**
     * Returns the file owner UID
     *
     * @return string|null
     */
    public function getOwnerName(): ?string;

    /**
     * Returns the file owner UID
     *
     * @return string|null
     */
    public function getGroupName(): ?string;

    /**
     * Returns true if this file has the exact same owner UID as the process UID
     *
     * @return bool
     */
    public function uidMatchesPuid(): bool;

    /**
     * Returns true if this file has the exact same group UID as the process UID
     *
     * @return bool
     */
    public function gidMatchesPuid(): bool;

    /**
     * Returns true if this path is in the specified directory
     *
     * To be in the specified directory, this path must start with the directory path.
     *
     * @param PhoDirectoryInterface|string $directory
     *
     * @return bool
     */
    public function isInDirectory(PhoDirectoryInterface|string $directory): bool;

    /**
     * Returns true if the specified path is on a domain
     *
     * @param string $path
     * @return bool
     */
    public static function onDomain(string $path): bool;

    /**
     * Returns true if this objects' path is on a domain
     *
     * @return bool
     */
    public function isOnDomain(): bool;

    /**
     * Returns the domain part for this domain path
     *
     * @return string
     */
    public function getDomain(): string;

    /**
     * Zips this path and returns a file object for the tar file
     *
     * @param PhoFileInterface|null $target
     * @param bool                  $compression
     * @param int                   $timeout
     *
     * @return PhoFileInterface
     */
    public function tar(?PhoFileInterface $target = null, bool $compression = true, int $timeout = 600): PhoFileInterface;


    /**
     * Zips this path and returns a file object for the tar file
     *
     * @return ZipInterface
     */
    public function getZipObject(): ZipInterface;

    /**
     * Make this (absolute) path a relative path
     *
     * @param PhoDirectoryInterface $from
     *
     * @return static
     */
    public function makeRelative(PhoDirectoryInterface $from): static;

    /**
     * Checks if write access is available for this file
     *
     * @param Throwable|null $e
     *
     * @return static
     * @todo Add Core::fileWriteEnabled() checks in here
     */
    public function checkWriteAccess(?Throwable $e = null): static;

    /**
     * Checks if read access is available
     *
     * @param Throwable|null $e
     *
     * @return static
     */
    public function checkReadAccess(?Throwable $e = null): static;

    /**
     * Returns the mtime (file access time) for this path
     *
     * @return PhoDateTimeInterface
     */
    public function getAtime(): PhoDateTimeInterface;

    /**
     * Returns the mtime (file modification time) for this path
     *
     * @return PhoDateTimeInterface
     */
    public function getMtime(): PhoDateTimeInterface;

    /**
     * Returns the ctime (inode change time of file) for this path
     *
     * @return PhoDateTimeInterface
     */
    public function getCtime(): PhoDateTimeInterface;

    /**
     * @param string $algo
     * @param bool   $binary
     * @param array  $options
     *
     * @see https://www.php.net/manual/en/function.hash-file.php
     * @return string
     */
    public function getHash(string $algo = 'sha256', bool $binary = false, array $options = []): string;

    /**
     * Returns true if this path is the same as the specified path
     *
     * @param PhoPathInterface $path
     *
     * @return bool
     */
    public function isSameAs(PhoPathInterface $path): bool;

    /**
     * Checks that this file is a binary file or throws a FileInvalidFormatException
     *
     * @return static
     *
     * @throws FileInvalidFormatException
     */
    public function checkIsBinary(): static;

    /**
     * Checks that this file is a text file or throws a FileInvalidFormatException
     *
     * @return static
     *
     * @throws FileInvalidFormatException
     */
    public function checkIsText(): static;

    /**
     * Ensures that the file has the specified extension
     *
     * @param string $extension
     *
     * @return static
     */
    public function ensureExtension(string $extension): static;

    /**
     * Returns the absolute version of this path
     *
     * @param Stringable|string|bool|null $prefix
     * @param bool                        $must_exist
     *
     * @return string
     */
    public function getAbsolutePath(Stringable|string|bool|null $prefix = null, bool $must_exist = true): string;

    /**
     * Returns the absolute version of this path
     *
     * @param Stringable|string|bool|null $prefix
     * @param bool                        $must_exist
     *
     * @return PhoPathInterface
     */
    public function getAbsolute(Stringable|string|bool|null $prefix = null, bool $must_exist = true): PhoPathInterface;

    /**
     * Checks if the specified file exists
     *
     * @param bool $auto_mount
     * @param bool $check_dead_symlink
     *
     * @return bool
     */
    public function getExists(bool $check_dead_symlink = false, bool $auto_mount = true): bool;

    /**
     * Returns a PhoMimetypeInterface object for this file
     *
     * @return PhoMimetypeInterface
     */
    public function getMimetypeObject(): PhoMimetypeInterface;

    /**
     * Returns a new file object starting from the specified path part
     *
     * @param PhoPathInterface|string|null $from
     *
     * @return PhoFileInterface
     */
    public function getFrom(PhoPathInterface|string|null $from = null): PhoFileInterface;

    /**
     * Returns a new file object starting from the project ROOT directory plus the specified path part
     *
     * @param PhoPathInterface|string|null $from
     *
     * @return PhoFileInterface
     */
    public function getFromRoot(PhoPathInterface|string|null $from = null): PhoFileInterface;

    /**
     * Returns a PhoDirectory object from this PhoPath object
     *
     * If the current path is a directory, the PhoDirectory object will have the same path
     *
     * If the current path is not a directory, the parent directory object will be returned instead
     *
     * @param bool $allow_parent If true, and the path is not a directory, the parent directory will be returned instead
     * @return PhoDirectoryInterface
     */
    public function getDirectoryObject(bool $allow_parent = true): PhoDirectoryInterface;

    /**
     * Returns true if the path for this object is in the specified path
     *
     * @param PhoPathInterface|string $o_path
     *
     * @return bool
     */
    public function isIn(PhoPathInterface|string $o_path): bool;

    /**
     * Returns true if the path for this object is in the specified path
     *
     * @param PhoPathInterface|string $o_path
     *
     * @return static
     */
    public function checkIn(PhoPathInterface|string $o_path): static;

    /**
     * Returns permissions for the owner of this file
     *
     * @return string
     */
    public function getOwnerPermissions(): string;

    /**
     * Returns permissions for users that are members of the group of this file
     *
     * @return string
     */
    public function getGroupPermissions(): string;

    /**
     * Returns permissions for world users
     *
     * @return string
     */
    public function getWorldPermissions(): string;

    /**
     * Returns the file permissions for owner, group, world
     *
     * @return string
     */
    public function getPermissions(): string;

    /**
     * Returns true if the file is owner executable
     *
     * @return bool
     */
    public function isOwnerReadable(): bool;

    /**
     * Returns true if the file is owner executable
     *
     * @return bool
     */
    public function isOwnerWritable(): bool;

    /**
     * Returns true if the file is owner executable
     *
     * @return bool
     */
    public function isOwnerExecutable(): bool;

    /**
     * Returns true if the file is group executable
     *
     * @return bool
     */
    public function isGroupReadable(): bool;

    /**
     * Returns true if the file is group executable
     *
     * @return bool
     */
    public function isGroupWritable(): bool;

    /**
     * Returns true if the file is group executable
     *
     * @return bool
     */
    public function isGroupExecutable(): bool;

    /**
     * Returns true if the file is world executable
     *
     * @return bool
     */
    public function isWorldReadable(): bool;

    /**
     * Returns true if the file is world executable
     *
     * @return bool
     */
    public function isWorldWritable(): bool;

    /**
     * Returns true if the file is world executable
     *
     * @return bool
     */
    public function isWorldExecutable(): bool;

    /**
     * Returns true if the basename of this path matches the regular expression
     *
     * @param string $regular_expression
     * @return bool
     */
    public function basenameMatchesRegex(string $regular_expression): bool;

    /**
     * Returns true if the basename of this path matches the regular expression
     *
     * @param string $regular_expression
     * @return bool
     */
    public function pathMatchesRegex(string $regular_expression): bool;

    /**
     * Returns true if the basename of this path matches the regular expression
     *
     * @param string $regular_expression
     * @return bool
     */
    public function parentDirectoryMatchesRegex(string $regular_expression): bool;

    /**
     * Makes the specified path absolute if it's not
     *
     * The start character will be treated as follows:
     *
     * . Is DIRECTORY_START
     * ~ is the current shell's user home directory
     * / is considered absolute
     *
     * @param PhoPathInterface|string|null $path
     * @param Stringable|string|bool|null  $absolute_prefix
     * @param bool                         $must_exist
     *
     * @return static
     */
    public static function absolutePath(PhoPathInterface|string|null $path = null, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = true): string;

    /**
     * Write the specified data to this file
     *
     * @param string $data
     * @param int    $flags
     * @param null   $context
     *
     * @return static
     */
    public function setContents(string $data, int $flags = 0, $context = null): static;
}
