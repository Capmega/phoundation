<?php

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Filesystem\Enums\Interfaces\EnumFileOpenModeInterface;
use Phoundation\Filesystem\Exception\FileActionFailedException;
use Phoundation\Filesystem\Exception\FileExistsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FileNotOpenException;
use Phoundation\Filesystem\Restrictions;
use Stringable;
use Throwable;


/**
 * interface FileBasicsInterface
 *
 * This library contains the variables used in the File class
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
     * Returns the configured file buffer size
     *
     * @param int|null $requested_buffer_size
     * @return int
     */
    public function getBufferSize(?int $requested_buffer_size = null): int;

    /**
     * Sets the configured file buffer size
     *
     * @param int|null $buffer_size
     * @return static
     */
    public function setBufferSize(?int $buffer_size): static;

    /**
     * Returns the stream for this file if its opened. Will return NULL if closed
     *
     * @return mixed
     */
    public function getStream(): mixed;

    /**
     * Returns the file
     *
     * @return string|null
     */
    public function getPath(): ?string;

    /**
     * Sets the file for this File object
     *
     * @param Stringable|string|null $file
     * @param string|null $prefix
     * @param bool $must_exist
     * @return static
     */
    public function setPath(Stringable|string|null $file, string $prefix = null, bool $must_exist = false): static;

    /**
     * Sets the target file name in case operations create copies of this file
     *
     * @param Stringable|string $target
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
     * @return bool
     */
    public function exists(): bool;

    /**
     * Checks if the specified file exists, throws exception if it doesn't
     *
     * @param bool $force
     * @return static
     * @throws FileNotExistException
     */
    public function checkExists(bool $force = false): static;

    /**
     * Checks if the specified file does not exist, throws exception if it does
     *
     * @param bool $force
     * @return static
     * @throws FileExistsException
     */
    public function checkNotExists(bool $force = false): static;

    /**
     * Renames a file or directory
     *
     * @param string $to_filename
     * @param $context
     * @return $this
     */
    public function rename(string $to_filename, $context = null): static;

    /**
     * Truncates a file to a given length
     *
     * @param int $size
     * @return $this
     */
    public function truncate(int $size): static;

    /**
     * Output all remaining data on a file pointer to the output buffer
     *
     * @return int The amount of bytes
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
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileType(): array;

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
     * @param bool $sudo
     * @return $this
     */
    public function secureDelete(string|bool $clean_path = true, bool $sudo = false): static;

    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @param string|bool $clean_path If specified true, all directories above each specified pattern will be deleted as
     *                                well as long as they are empty. This way, no empty directories will be left lying
     *                                around
     * @param boolean $sudo If specified true, the rm command will be executed using sudo
     * @param bool $escape If true, will escape the filename. This may cause issues when using wildcards, for
     *                                example
     * @param bool $use_run_file
     * @return static
     * @see Restrictions::check() This function uses file location restrictions
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static;

    /**
     * Moves this file to the specified target, will try to ensure target directory exists
     *
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     * @return $this
     */
    public function move(Stringable|string $target, ?Restrictions $restrictions = null): static;

    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
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
     * Returns the file type
     *
     * @return string|int|null
     */
    public function getType(): string|int|null;

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
     * @param bool $recursive
     * @return static
     * @see $this->chmod()
     *
     * @note This function ALWAYS requires sudo as chown is a root only filesystem command
     */
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false): static;

    /**
     * Change file mode, optionally recursively
     *
     * @param string|int $mode The mode to apply to the specified file (and all files below if recursive is specified)
     * @param boolean $recursive If set to true, apply specified mode to the specified file and all files below by
     *                           recursion
     * @param bool $sudo
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
     * @return bool
     */
    public function ensureFileReadable(?int $mode = null): bool;

    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return bool
     */
    public function ensureFileWritable(?int $mode = null): bool;

    /**
     * Returns the size in bytes of this file or directory
     *
     * @param bool $recursive
     * @return int
     */
    public function getSize(bool $recursive = true): int;

    /**
     * Returns the parent directory for this file
     *
     * @param RestrictionsInterface|null $restrictions
     * @return DirectoryInterface
     */
    public function getParentDirectory(?RestrictionsInterface $restrictions = null): DirectoryInterface;

    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param EnumFileOpenModeInterface $mode
     * @param resource $context
     * @return static
     */
    public function open(EnumFileOpenModeInterface $mode, $context = null): static;

    /**
     * Returns true if the file is a symlink, whether its target exists or not
     *
     * @return bool
     */
    public function isLink(): bool;

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
     * @note Will return a NEW FileBasics object (File or Directory, basically) for the specified target
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     * @param bool $absolute
     * @return $this
     */
    public function symlink(Stringable|string $target, ?Restrictions $restrictions = null, bool $absolute = false): static;

    /**
     * Returns true if the file pointer is at EOF
     *
     * @return bool
     */
    public function isEof(): bool;

    /**
     * Returns how the file was opened, NULL if the file is not open
     *
     * @return EnumFileOpenModeInterface|null
     */
    public function getOpenMode(): ?EnumFileOpenModeInterface;

    /**
     * Sets the internal file pointer to the specified offset
     *
     * @param int $offset
     * @param int $whence
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
     * Reads and returns the specified amount of bytes from the current pointer location
     *
     * @param int|null $buffer
     * @param int|null $seek
     * @return string|false
     */
    public function read(?int $buffer = null, ?int $seek = null): string|false;

    /**
     * Reads and returns the next text line in this file
     *
     * @param int|null $buffer
     * @return string|false
     */
    public function readLine(?int $buffer = null): string|false;

    /**
     * Reads line from file pointer and parse for CSV fields
     *
     * @param int|null $max_length
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
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
     * Reads and returns the specified amount of bytes at the specified location from this CLOSED file
     *
     * @note Will throw an exception if the file is already open
     * @param int $length
     * @param int $start
     * @return string|false
     */
    public function readBytes(int $length, int $start = 0): string|false;

    /**
     * Binary-safe write the specified data to this file
     *
     * @param string $data
     * @param int|null $length
     * @return $this
     */
    public function write(string $data, ?int $length = null): static;

    /**
     * Write the specified data to this
     *
     * @param bool $use_include_path
     * @param resource|null $context
     * @param int $offset
     * @param int|null $length
     * @return $this
     */
    public function getContentsAsString(bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string;

    /**
     * Returns the contents of this file as an array
     *
     * @param int $flags
     * @param $context
     * @return array
     */
    public function getContentsAsArray(int $flags = 0, $context = null): array;

    /**
     * Append specified data string to the end of the object file
     *
     * @param string $data
     * @param int|null $length
     * @return static
     */
    public function append(string $data, ?int $length = null): static;

    /**
     * Create the specified file
     *
     * @param bool $force
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
     * @return static
     */
    public function appendFiles(string|array $sources): static;

    /**
     * Closes this file
     *
     * @param bool $force
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
     * @return $this
     */
    public function shred(int $passes = 3): static;

    /**
     * Returns the relative path between the specified path and this objects path
     *
     * @param mixed $path
     * @return string
     */
    public function getRelativePathTo(mixed $path): string;

    /**
     * Returns the amount of directories counted in the specified path
     *
     * @param mixed $path
     * @return int
     */
    public static function countDirectories(mixed $path): int;
}
