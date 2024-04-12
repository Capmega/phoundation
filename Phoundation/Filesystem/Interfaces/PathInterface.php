<?php

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\Exception\FileActionFailedException;
use Phoundation\Filesystem\Exception\FileNotOpenException;
use Phoundation\Filesystem\Restrictions;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Stringable;
use Throwable;

/**
 * Interface PathInterface
 *
 * This library contains the basic functionalities to manage filesystem paths
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
interface PathInterface
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
     * Returns the basename of this path
     *
     * @return string
     */
    public function getBasename(): string;


    /**
     * Returns the stream for this file if it's opened. Will return NULL if closed
     *
     * @return mixed
     */
    public function getStream(): mixed;


    /**
     * Returns the path
     *
     * @param string|null $from
     *
     * @return string|null
     */
    public function getPath(?string $from = null): ?string;


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
     * @param Stringable|string|null $path
     * @param string|null            $prefix
     * @param bool                   $must_exist
     * @param bool                   $make_absolute
     *
     * @return static
     */
    public function setPath(Stringable|string|null $path, string $prefix = null, bool $must_exist = false, bool $make_absolute = false): static;


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
     * @return $this
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
     * @return $this
     */
    public function secureDelete(string|bool $clean_path = true, bool $sudo = false): static;


    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @param string|bool $clean_path If specified true, all directories above each specified pattern will be deleted as
     *                                well as long as they are empty. This way, no empty directories will be left lying
     *                                around
     * @param boolean     $sudo       If specified true, the rm command will be executed using sudo
     * @param bool        $escape     If true, will escape the filename. This may cause issues when using wildcards, for
     *                                example
     * @param bool        $use_run_file
     *
     * @return static
     * @see Restrictions::check() This function uses file location restrictions
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static;


    /**
     * Moves this file to the specified target, will try to ensure target directory exists
     *
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     *
     * @return $this
     */
    public function movePath(Stringable|string $target, ?Restrictions $restrictions = null): static;


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
     * @return string|int|null
     */
    public function getMode(): string|int|null;


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
     * Returns the stat data for the object file
     *
     * @return array
     */
    public function getStat(): array;


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
     * @return ?string string The real directory extrapolated from the specified $directory, if exists. False if
     *                 whatever was specified does not exist.
     *
     * @example
     * code
     * show(File::new()->getRealPath());
     * showdie(File::new()->getRealPath());
     * /code
     *
     * This would result in
     * code
     * null
     * /bin
     * /code
     */
    public function getRealPath(): ?string;


    /**
     * Returns a normalized path that has all ./ and ../ resolved
     *
     * @param Stringable|string|bool|null $make_absolute
     *
     * @return ?string string The real directory extrapolated from the specified $directory, if exists. False if
     *                 whatever was specified does not exist.
     *
     * @example
     * code
     * show(File::new()->getRealPath());
     * showdie(File::new()->getRealPath());
     * /code
     *
     * This would result in
     * code
     * null
     * /bin
     * /code
     */
    public function getNormalizedPath(Stringable|string|bool|null $make_absolute = null): ?string;


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
     * @param RestrictionsInterface|null $restrictions
     *
     * @return DirectoryInterface
     */
    public function getParentDirectory(?RestrictionsInterface $restrictions = null): DirectoryInterface;


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
     * @param PathInterface|string|bool $make_absolute
     *
     * @return \Phoundation\Filesystem\Interfaces\PathInterface
     */
    public function readLink(PathInterface|string|bool $make_absolute = false): PathInterface;


    /**
     * Wrapper for Path::readlink()
     *
     * @param PathInterface|string|bool $absolute
     *
     * @return \Phoundation\Filesystem\Interfaces\PathInterface
     */
    public function getLinkTarget(PathInterface|string|bool $absolute = false): PathInterface;


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
    public function isDir(): bool;


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
     * @note Will return a NEW Path object (File or Directory, basically) for the specified target
     *
     * @param PathInterface|string      $target
     * @param PathInterface|string|bool $make_relative
     *
     * @return \Phoundation\Filesystem\Interfaces\PathInterface
     */
    public function symlinkTargetFromThis(PathInterface|string $target, PathInterface|string|bool $make_relative = true): PathInterface;


    /**
     * Makes this path a symlink that points to the specified target.
     *
     * @note Will return a NEW Path object (File or Directory, basically) for the specified target
     *
     * @param PathInterface|string      $target
     * @param PathInterface|string|bool $make_relative
     *
     * @return \Phoundation\Filesystem\Interfaces\PathInterface
     */
    public function symlinkThisToTarget(PathInterface|string $target, PathInterface|string|bool $make_relative = true): PathInterface;


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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function sync(): static;


    /**
     * Synchronizes data (but not meta-data) to the file
     *
     * @return $this
     */
    public function syncData(): static;


    /**
     * Will overwrite the file with random data before deleting it
     *
     * @param int $passes
     *
     * @return $this
     */
    public function shred(int $passes = 3): static;


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
     * @param PathInterface|string      $target
     * @param PathInterface|string|bool $make_absolute
     *
     * @return \Phoundation\Filesystem\Interfaces\PathInterface
     */
    public function getRelativePathTo(PathInterface|string $target, PathInterface|string|bool $make_absolute = null): PathInterface;


    /**
     * Checks restrictions
     *
     * @param bool $write
     *
     * @return $this
     */
    public function checkRestrictions(bool $write): static;


    /**
     * Replaces the current path by moving it out of the way and moving the target in its place, then deleting the
     * original
     *
     * @param PathInterface|string $target
     *
     * @return \Phoundation\Filesystem\Interfaces\PathInterface
     */
    public function replaceWithPath(PathInterface|string $target): PathInterface;


    /**
     * Ensures that this path is a symlink
     *
     * @return $this
     */
    public function checkSymlink(Stringable|string $target): static;


    /**
     * Returns a PathInterface object with the specified path appended to this path
     *
     * @param PathInterface|string $path
     * @param bool                 $make_absolute
     *
     * @return FileInterface
     */
    public function appendPath(PathInterface|string $path, bool $make_absolute = false): PathInterface;


    /**
     * Returns a PathInterface object with the specified path prepended to this path
     *
     * @param PathInterface|string $path
     * @param bool                 $make_absolute
     *
     * @return FileInterface
     */
    public function prependPath(PathInterface|string $path, bool $make_absolute = false): PathInterface;


    /**
     * Returns a FilesInterface object that will contain all the files under this current path
     *
     * @param bool $reload
     *
     * @return FilesInterface
     */
    public function getFilesObject(bool $reload = false): FilesInterface;


    /**
     * Copies all directories as directories and all files as symlinks in the tree starting at this objects path to the
     * specified target,
     *
     * Directories will remain directories, all files will be symlinks
     *
     * @param PathInterface|string       $target
     * @param PathInterface|string|null  $alternate_path
     * @param RestrictionsInterface|null $restrictions
     * @param bool                       $rename
     *
     * @return $this
     */
    public function symlinkTreeToTarget(PathInterface|string $target, PathInterface|string|null $alternate_path = null, ?RestrictionsInterface $restrictions = null, bool $rename = false): PathInterface;


    /**
     * Will scan this path for symlinks and delete all of them one by one
     *
     * @return $this
     */
    public function clearTreeSymlinks(bool $clean = false): static;
}