<?php

namespace Phoundation\Filesystem\Interfaces;

use Exception;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Path;
use Throwable;


/**
 * Interface FileInterface
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface FileInterface extends FileBasicsInterface
{
    /**
     * Returns the configured file buffer size
     *
     * @return int
     */
    public function getBufferSize(): int;

    /**
     * Sets the configured file buffer size
     *
     * @param int|null $buffer_size
     * @return static
     */
    public function setBufferSize(?int $buffer_size): static;

    /**
     * Append specified data string to the end of the object file
     *
     * @param string $data
     * @return static
     * @throws FilesystemException
     */
    public function append(string $data): static;

    /**
     * Append specified data string to the end of the object file
     *
     * @param string $data
     * @return static
     * @throws FilesystemException
     */
    public function create(string $data): static;

    /**
     * Concatenates a list of files to a target file
     *
     * @param string|array $sources The source files
     * @return static
     */
    public function appendFiles(string|array $sources): static;

    /**
     * Move uploaded image to correct target
     *
     * @param array|string $source The source file to process
     * @return string The new file path
     * @throws CoreException
     */
    public function getUploaded(array|string $source): string;

    /**
     * Ensure that the object file exists in the specified path
     *
     * @note Will log to the console in case the file was created
     * @param null $mode If the specified $this->file does not exist, it will be created with this file mode. Defaults to $_CONFIG[fs][file_mode]
     * @param null $pattern_mode If parts of the path for the file do not exist, these will be created as well with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @return void
     * @version 2.4.16: Added documentation, improved log output
     *
     */
    public function ensureFile($mode = null, $pattern_mode = null): void;

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
     * Returns true if the object file is a PHP file
     *
     * @return bool
     */
    public function isPhp(): bool;

    /**
     * Copy a file with progress notification
     *
     * @
     * @example:
     * function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
     *     if ($notification_code == STREAM_Notification_PROGRESS) {
     *         // save $bytes_transferred and $bytes_max to file or database
     *     }
     * }
     *
     * file_copy_progress($source, $target, 'stream_notification_callback');
     */
    public function copyProgress(string $target, callable $callback): static;

    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param string $mode
     * @param resource $context
     * @return resource
     */
    public function open(string $mode, $context = null);

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
     * Returns if the link target exists or not
     *
     * @return bool
     */
    public function linkTargetExists(): bool;

    /**
     * Search / replace the object files
     *
     * @param string $target
     * @param array $replaces The list of keys that will be replaced by values
     * @param bool $regex
     * @return static
     */
    public function replace(string $target, array $replaces, bool $regex = false): static;

    /**
     * Return line count for the specified text file
     *
     * @param string $source
     * @return int
     */
    public function lineCount(string $source): int;

    /**
     * Return word count for the specified text file
     *
     * @param string $source
     * @return int
     */
    public function wordCount(string $source): int;

    /**
     * Returns true if any part of the object file path is a symlink
     *
     * @param string|null $prefix
     * @return boolean True if the specified $pattern (optionally prefixed by $prefix) contains a symlink, false if not
     */
    public function pathContainsSymlink(?string $prefix = null): bool;

    /**
     * ???
     *
     * @see https://secure.php.net/manual/en/migration56.openssl.php
     * @see https://secure.php.net/manual/en/function.stream-context-create.php
     * @see https://secure.php.net/manual/en/wrappers.php
     * @see https://secure.php.net/manual/en/context.php
     *
     * @param array $context
     * @return resource|null
     */
    public function createStreamContext(array $context);

    /**
     * Filter out the lines that contain the specified filters
     *
     * @note Only supports line of up to 8KB which should be WAY more than enough, but still important to know
     * @param string|array $filters
     * @param int|null $until_line
     * @return array
     */
    public function grep(string|array $filters, ?int $until_line = null): array;

    /**
     * Create a target, but don't put anything in it
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function assignTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string;

    /**
     * Create a target, but don't put anything in it, and return path+filename without extension
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function assignTargetClean(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string;

    /**
     * Copy object file, see file_move_to_target for implementation
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function copyToTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string;

    /**
     * Move object file (must be either file string or PHP uploaded file array) to a target and returns the target name
     *
     * IMPORTANT! Extension here is just "the rest of the filename", which may be _small.jpg, or just the extension, .jpg
     * If only an extension is desired, it is VERY important that its specified as ".jpg" and not "jpg"!!
     *
     * $pattern sets the base path for where the file should be stored
     * If $extension is false, the files original extension will be retained. If set to a value, the extension will be that value
     * If $singledir is set to false, the resulting file will be in a/b/c/d/e/, if its set to true, it will be in abcde
     * $length specifies howmany characters the subdir should have (4 will make a/b/c/d/ or abcd/)
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @param bool $copy
     * @param string $context
     * @return string The target file
     * @throws Exception
     */
    public function moveToTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4, bool $copy = false, mixed $context = null): string;

    /**
     * Copy an entire tree with replace option
     *
     * Extensions (may be string or array with strings) sets which file extensions will have search / replace. If set to
     * false all files will have search / replace applied.
     *
     * If either search or replace are not specified, both will be
     * set to null, and no replacements will be done
     *
     * Mode has 3 settings: (boolean) true, null, and some 0000 mode.
     * true will keep the copied file mode for the target, 0000 will
     * set the target file mode to the specified value, and null will
     * set $mode to the default value, specified in $_CONFIG, and then
     * do the same as 0000
     */
    public function copyTree(string $destination, array $search = null, array $replace = null, string|array $extensions = null, mixed $mode = true, bool $novalidate = false): string;

    /**
     * Makes a backup of this file to the specified target and returns a new File object for the target
     *
     * @param string $pattern
     * @param bool $move
     * @return static
     */
    public function backup(string $pattern = '~date', bool $move = false): static;

    /**
     * Returns an array with PHP code statistics for this file
     *
     * @return array
     */
    public function getPhpStatistics(): array;

    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return static
     */
    public function ensureReadable(?int $mode = null): static;

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
     * Returns the extension of the object filename
     *
     * @return string
     */
    public function getExtension(): string;

    /**
     * Ensure that this file has the specified sha256 hash
     *
     * @param string $sha256
     * @param bool $ignore_sha_fail
     * @return $this
     */
    public function checkSha256(string $sha256, bool $ignore_sha_fail = false): static;

    /**
     * Tars this file and returns a file object for the tar file
     *
     * @return static
     */
    public function tar(): static;

    /**
     * Untars the file
     *
     * @return Path
     */
    public function untar(): Path;

    /**
     * Gzips the file
     *
     * @return $this
     */
    public function gzip(): static;

    /**
     * Ungzips the file
     *
     * @return $this
     */
    public function gunzip(): static;

    /**
     * Returns the contents of this file as a string
     *
     * @return string
     */
    public function getContentsAsString(): string;

    /**
     * Returns the contents of this file as an array
     *
     * @return array
     */
    public function getContentsAsArray(): array;

    /**
     * Will unzip this file
     *
     * @return static
     */
    public function unzip(): static;
}