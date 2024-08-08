<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Interfaces;

use Exception;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Filesystem\FsDirectory;
use Stringable;
use Throwable;

interface FsFileInterface extends FsPathInterface
{
    /**
     * Move uploaded image to correct target
     *
     * @param array|string $source The source file to process
     *
     * @return string The new file path
     * @throws CoreException
     */
    public function getUploaded(array|string $source): string;

    /**
     * Ensure that the object file exists in the specified path
     *
     * @note    Will log to the console in case the file was created
     *
     * @param null $mode         If the specified $this->file does not exist, it will be created with this file mode.
     *                           Defaults to $_CONFIG[fs][file_mode]
     * @param null $pattern_mode If parts of the path for the file do not exist, these will be created as well with
     *                           this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     *
     * @return void
     * @version 2.4.16: Added documentation, improved log output
     *
     */
    public function ensureFile($mode = null, $pattern_mode = null): void;

    /**
     * Returns true if the object file is a PHP file
     *
     * @return bool
     */
    public function isPhp(): bool;

    /**
     * Copy a file with progress notification
     *
     * @param Stringable|string            $target
     * @param FsRestrictionsInterface|null $restrictions
     * @param callable|null                $callback
     * @param mixed|null                   $context
     *
     * @return static
     * @example:
     * FsFileFileInterface::new($source)->copy($target, function ($notification_code, $severity, $message, $message_code,
     * $bytes_transferred, $bytes_max) { if ($notification_code == STREAM_Notification_PROGRESS) {
     *          // save $bytes_transferred and $bytes_max to file or database
     *      }
     *  });
     */
    public function copy(Stringable|string $target, ?FsRestrictionsInterface $restrictions = null, ?callable $callback = null, mixed $context = null): static;

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
     * Search / replace the object files
     *
     * @param array                $replaces The list of keys that will be replaced by values
     * @param FsFileInterface|null $target
     * @param bool                 $regex
     *
     * @return static
     */
    public function replace(array $replaces, ?FsFileInterface $target = null, bool $regex = false): static;

    /**
     * Return line count for the specified text file
     *
     * @param string $source
     *
     * @return int
     */
    public function getLineCount(string $source, int $buffer = 1048576): int;

    /**
     * Return word count for the specified text file
     *
     * @param int         $format
     * @param string|null $characters
     * @param int         $buffer
     *
     * @return array|int
     */
    public function getWordCount(int $format = 0, ?string $characters = null, int $buffer = 1048576): array|int;

    /**
     * Return word frequency for the specified text file
     *
     * @param string|null $characters
     * @param int         $buffer
     *
     * @return array
     */
    public function getWordFrequency(?string $characters = null, int $buffer = 1048576): array;

    /**
     * Returns true if any part of the object file path is a symlink
     *
     * @param string|null $prefix
     *
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
     *
     * @return resource|null
     */
    public function createStreamContext(array $context);

    /**
     * Filter out the lines that contain the specified filters
     *
     * @note Only supports line of up to 8KB which should be WAY more than enough, but still important to know
     *
     * @param string|array $filters
     * @param int|null     $until_line
     *
     * @return array
     */
    public function grep(string|array $filters, ?int $until_line = null): array;

    /**
     * Copy object file, see file_move_to_target for implementation
     *
     * @param string $directory
     * @param bool   $extension
     * @param bool   $singledir
     * @param int    $length
     *
     * @return string
     * @throws Exception
     */
    public function copyToTarget(string $directory, bool $extension = false, bool $singledir = false, int $length = 4): string;

    /**
     * Move object file (must be either file string or PHP uploaded file array) to a target and returns the target name
     *
     * IMPORTANT! Extension here is just "the rest of the filename", which may be _small.jpg, or just the extension,
     * .jpg If only an extension is desired, it is VERY important that its specified as ".jpg" and not "jpg"!!
     *
     * $pattern sets the base path for where the file should be stored
     * If $extension is false, the files original extension will be retained. If set to a value, the extension will be
     * that value If $singledir is set to false, the resulting file will be in a/b/c/d/e/, if its set to true, it will
     * be in abcde
     * $length specifies howmany characters the subdir should have (4 will make a/b/c/d/ or abcd/)
     *
     * @param string $directory
     * @param bool   $extension
     * @param bool   $singledir
     * @param int    $length
     * @param bool   $copy
     * @param string $context
     *
     * @return string The target file
     * @throws Exception
     */
    public function moveToTarget(string $directory, bool $extension = false, bool $singledir = false, int $length = 4, bool $copy = false, mixed $context = null): string;

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
     * Makes a backup of this file to the specified target and returns a new FsFileFileInterface object for the target
     *
     * @param string $pattern
     * @param bool   $move
     *
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
     *
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
     *
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
     * @param bool   $ignore_sha_fail
     *
     * @return $this
     */
    public function checkSha256(string $sha256, bool $ignore_sha_fail = false): static;

    /**
     * Untars the file
     *
     * @return FsPathInterface
     */
    public function untar(): FsPathInterface;

    /**
     * Unzips the file
     *
     * @param FsDirectoryInterface|null $directory
     *
     * @return FsPathInterface
     */
    public function unzip(FsDirectoryInterface|null $directory = null): FsPathInterface;

    /**
     * Gzips the file
     *
     * @return FsFileInterface
     */
    public function gzip(): FsFileInterface;

    /**
     * Ungzips the file
     *
     * @return FsFileInterface
     */
    public function gunzip(): FsFileInterface;

    /**
     * Ensure that the line endings in this file are as specified
     *
     * @param string $line_endings
     *
     * @return $this
     */
    public function ensureLineEndings(string $line_endings = PHP_EOL): static;

    /**
     * Create the specified file
     *
     * @param bool $force
     *
     * @return static
     */
    public function create(bool $force = false): static;

    /**
     * Will upload this file to the remote client
     *
     * @param bool $attachment
     * @param bool $exit
     *
     * @return $this
     */
    public function upload(bool $attachment, bool $exit = true): static;
}
