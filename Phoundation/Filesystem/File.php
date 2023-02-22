<?php

namespace Phoundation\Filesystem;

use Exception;
use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\Sha256MismatchException;
use Phoundation\Processes\Commands\FilesystemCommands;
use Throwable;


/**
 * File class
 *
 * This library contains various filesystem file related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class File extends FileBasics
{
    /**
     * The default size of the file buffer
     *
     * @var int|null $buffer_size
     */
    protected ?int $buffer_size = null;



    /**
     * Returns the configured file buffer size
     *
     * @return int
     */
    public function getBufferSize(): int
    {
        $required  = Config::get('filesystem.buffer.size', $this->buffer_size ?? 4096);
        $available = Core::getMemoryAvailable();

        if ($required > $available) {
            // The required file buffer is larger than the available memory, oops...
            if (Config::get('filesystem.buffer.auto', false)) {
                throw new FilesystemException(tr('Failed to set file buffer of ":required", only ":available" memory available', [
                    ':required'  => $required,
                    ':available' => $available
                ]));
            }

            // Just auto adjust to half of the available memory
            Log::warning(tr('File buffer of ":required" requested but only ":available" memory available. Created buffer of ":size" instead', [
                ':required'  => $required,
                ':available' => $available,
                ':size'      => floor($available * .5)
            ]));

            $required = floor($available * .5);
        }

        return $required;
    }



    /**
     * Sets the configured file buffer size
     *
     * @param int|null $buffer_size
     * @return static
     */
    public function setBufferSize(?int $buffer_size): static
    {
        $this->buffer_size = $buffer_size;
        return $this;
    }



    /**
     * Append specified data string to the end of the object file
     *
     * @param string $data
     * @return File
     * @throws FilesystemException
     */
    public function appendData(string $data): File
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        // Make sure the file path exists
        Path::new(dirname($this->file), $this->restrictions)->ensure();

        $h = $this->open('a');
        fwrite($h, $data);
        fclose($h);

        return $this;
    }


    /**
     * Concatenates a list of files to a target file
     *
     * @param string|array $sources The source files
     * @return File
     */
    public function appendFiles(string|array $sources): File
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        // Ensure the target path exists
        Path::new(dirname($this->file), $this->restrictions)->ensure();

        // Open target file
        try {
            $target_h = $this->open('a');
        } catch (Throwable $e) {
            // Failed to open the target file
            $this->checkReadable('target', $e);
        }

        // Open each source file
        foreach (Arrays::force($sources, null) as $source) {
            try {
                $source_h = File::new($source, $this->restrictions)->open('r');

                while (!feof($source_h)) {
                    $data = fread($source_h, 8192);
                    fwrite($target_h, $data);
                }

                fclose($source_h);
            } catch (Throwable $e) {
                // Failed to open one of the sources, get rid of the partial target file
                $this->delete();
                $this->checkReadable('source', $e);
            }
        }

        fclose($target_h);

        return $this;
    }



    /**
     * Move uploaded image to correct target
     *
     * @param array|string $source The source file to process
     * @return string The new file path
     * @throws CoreException
     */
    public function getUploaded(array|string $source): string
    {
        throw new UnderConstructionException();
        $destination = PATH_ROOT . 'data/uploads/';

        $this->restrictions->check($source, true);
        $this->restrictions->check($destination, true);

        if (is_array($source)) {
            // Assume this is a PHP file upload array entry
            if (empty($source['tmp_name'])) {
                throw new FilesystemException(tr('Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry'));
            }

            $real = $source['name'];
            $source = $source['tmp_name'];

        } else {
            $real = basename($source);
        }

        is_file($source);
        Path::new($destination)->ensure();

        // Ensure we're not overwriting anything!
        if (file_exists($destination . $real)) {
            $real = Strings::untilReverse($real, '.') . '_' . substr(uniqid(), -8, 8) . '.' . Strings::fromReverse($real, '.');
        }

        if (!move_uploaded_file($source, $destination . $real)) {
            throw new FilesystemException(tr('Failed to move file ":source" to destination ":destination"', [
                ':source' => $source,
                ':destination' => $destination
            ]));
        }

        // Return destination file
        return $destination . $real;
    }



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
    public function ensureFile($mode = null, $pattern_mode = null): void
    {
        // Check filesystem restrictions
        $path = dirname($this->file);
        $mode = Config::get('filesystem.modes.defaults.file', 0640, $mode);

        $this->restrictions->check($path, true);

        Path::new(dirname($this->file), $this->restrictions)->ensure($pattern_mode);

        if (!file_exists($this->file)) {
            // Create the file
            Path::new(dirname($this->file), $this->restrictions)->execute()
                ->setMode(0770)
                ->onPathOnly(function () use ($mode) {
                    Log::warning(tr('File ":file" did not exist and was created empty to ensure system stability, but information may be missing', [
                        ':file' => $this->file
                    ]));

                    touch($this->file);

                    if ($mode) {
                        $this->chmod($mode);
                    }
                });
        }
    }



    /**
     * Returns true or false if file is ASCII or not
     *
     * @return bool True if the file is a text file, false if not
     * @version 2.4: Added documentation
     */
    public function isText(): bool
    {
        return !$this->isBinary();
    }



    /**
     * Returns true or false if file is ASCII or not
     *
     * @return bool True if the file is a text file, false if not
     */
    public function isBinary(): bool
    {
        $mimetype = $this->mimetype();
        return Filesystem::isBinary(Strings::until($mimetype, '/'), Strings::from($mimetype, '/'));
    }



    /**
     * Returns true if the object file is a PHP file
     *
     * @return bool
     */
    public function isPhp(): bool
    {
        if (str_ends_with($this->file, '.php')) {
            if ($this->isText()) {
                return true;
            }
        }

        return false;
    }



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
    public function copyProgress(string $target, callable $callback): static
    {
        $this->restrictions->check($this->file, true);
        $this->restrictions->check($target, false);

        $context = stream_context_create();

        stream_context_set_params($context, [
            'notification' => $callback
        ]);

        copy($this->file, $target, $context);
        return new static($target, $this->restrictions);
    }



    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param string $mode
     * @param resource $context
     * @return resource
     */
    public function open(#[ExpectedValues(values: ['r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'ce+'])] string $mode, $context = null)
    {
        if (!$mode) {
            throw new OutOfBoundsException(tr('No file open mode specified'));
        }

        $this->restrictions->check($this->file, ($mode[0] !== 'r'));

        // Check filesystem restrictions
        $handle = fopen($this->file, $mode, false, $context);

        if (!$handle) {
            // Check if the mode is valid and if the file can be opened for the requested mode
            $method = match ($mode) {
                'r' => FileBasics::READ,
                'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'ce+' => FileBasics::WRITE,
                default => throw new FilesystemException(tr('Could not open file ":file"', [
                    ':file' => $this->file
                ])),
            };

            // Mode is valid, check if file is accessible.
            switch ($method) {
                case FileBasics::READ:
                    $this->checkReadable();
                    break;

                case FileBasics::WRITE:
                    $this->checkWritable();
                    break;
            }

            throw new FilesystemException(tr('Failed to open file ":file"', [':file' => $this->file]));
        }

        return $handle;
    }



    /**
     * Check if the object file exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type             This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e    If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     * @return static
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static
    {
        parent::checkReadable($type, $previous_e);

        if (is_dir($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read because it is a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

        return $this;
    }



    /**
     * Check if the object file exists and is writable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type          This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return static
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null) : static
    {
        parent::checkWritable($type, $previous_e);

        if (is_dir($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be written because it is a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

        return $this;
    }



    /**
     * Returns if the link target exists or not
     *
     * @return bool
     */
    public function linkTargetExists(): bool
    {
        throw new UnderConstructionException();
        if (file_exists($this->file)) {
            return false;
        }

        if (is_link()) {
            throw new FilesystemException(tr('Symlink ":source" has non existing target ":target"', [
                'source' => $this->file,
                ':target' => readlink()
            ]));
        }

        throw new FilesystemException(tr('Symlink ":source" has non existing target ":target"', [
            'source' => $this->file,
            ':target' => readlink()
        ]));
    }



    /**
     * Search / replace the object files
     *
     * @param string $target
     * @param array $replaces The list of keys that will be replaced by values
     * @param bool $regex
     * @return static
     */
    public function replace(string $target, array $replaces, bool $regex = false): static
    {
        // Check filesystem restrictions and if file exists
        $this->restrictions->check($this->file, true);
        $this->restrictions->check($target, true);

        // Source file and target path exist?
        $this->exists();
        File::new(dirname($target), $this->restrictions)->exists();

        // Copy & replace
        $data = file_get_contents($this->file);

        if ($regex) {
            // Execute each regex
            foreach ($replaces as $from => $to) {
                $data = str_replace($from, $to, $data);
            }
        } else {
            $data = str_replace(array_keys($replaces), $replaces, $data);
        }

        file_put_contents($this->file, $data);

        return File::new($target, $this->restrictions);
    }



    /**
     * Return line count for the specified text file
     *
     * @param string $source
     * @return int
     */
    public function lineCount(string $source): int
    {
        throw new UnderConstructionException();
        $this->isText($source);
    }



    /**
     * Return word count for the specified text file
     *
     * @param string $source
     * @return int
     */
    public function wordCount(string $source): int
    {
        throw new UnderConstructionException();
        $this->isText($source);
    }



    /**
     * Returns true if any part of the object file path is a symlink
     *
     * @param string|null $prefix
     * @return boolean True if the specified $pattern (optionally prefixed by $prefix) contains a symlink, false if not
     */
    public function pathContainsSymlink(?string $prefix = null): bool
    {
        // Check filesystem restrictions and if file exists
        $this->restrictions->check($this->file, true);

        // Build up the path
        if (str_starts_with($this->file, '/')) {
            if ($prefix) {
                throw new FilesystemException(tr('The specified file ":file" is absolute, which requires $prefix to be null, but it is ":prefix"', [
                    ':file'   => $this->file,
                    ':prefix' => $prefix
                ]));
            }

            $location = '/';

        } else {
            // Specified $pattern is relative, so prefix it with $prefix
            if (!str_starts_with($prefix, '/')) {
                throw new FilesystemException(tr('The specified file ":file" is relative, which requires an absolute $prefix but it is ":prefix"', [
                    ':file'   => $this->file,
                    ':prefix' => $prefix
                ]));
            }

            $location = Strings::endsWith($prefix, '/');
        }

        $this->file = Strings::endsNotWith(Strings::startsNotWith($this->file, '/'), '/');

        // Check filesystem restrictions
        $this->restrictions->check($this->file, false);

        foreach (explode('/', $this->file) as $section) {
            $location .= $section;

            if (!file_exists($location)) {
                throw new FilesystemException(tr('The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', [
                    ':path'     => $this->file,
                    ':prefix'   => $prefix,
                    ':location' => $location
                ]));
            }

            if (is_link($location)) {
                return true;
            }

            $location .= '/';
        }

        return false;
    }



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
    public function createStreamContext(array $context)
    {
        if (!$context) return null;
        return stream_context_create($context);
    }



    /**
     * Filter out the lines that contain the specified filters
     *
     * @note Only supports line of up to 8KB which should be WAY more than enough, but still important to know
     * @param string|array $filters
     * @param int|null $until_line
     * @return array
     */
    public function grep(string|array $filters, ?int $until_line = null): array
    {
        // Validate filters
        foreach (Arrays::force($filters, null) as $filter) {
            if (!is_scalar($filter)) {
                throw new OutOfBoundsException(tr('The filter ":filter" is invalid, only string filters are allowed', [
                    ':filter' => $filter
                ]));
            }
        }

        // Open the file and start scanning each line
        $handle = $this->open('r');
        $count  = 0;
        $return = [];
        $buffer = $this->getBufferSize();

        while (($line = fgets($handle, $buffer)) !== false) {
            foreach ($filters as $filter) {
                if (str_contains($line, $filter)) {
                    $return[$filter][] = $line;
                }
            }

            if ($until_line and (++$count >= $until_line)) {
                // We're done, get out
                break;
            }
        }

        fclose($handle);
        return $return;
    }



    // GARBAGE BELOW, REIMPLEMENT
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
    public function assignTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return $this->moveToTarget('', $path, $extension, $singledir, $length);
    }



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
    public function assignTargetClean(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return str_replace($extension, '', $this->moveToTarget('', $path, $extension, $singledir, $length));
    }



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
    public function copyToTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return $this->moveToTarget($path, $extension, $singledir, $length, true);
    }


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
    public function moveToTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4, bool $copy = false, mixed $context = null): string
    {
        throw new UnderConstructionException();
        $this->restrictions->check($this->file, false);
        $this->restrictions->check($path, true);

        if (is_array($this->file)) {
            // Assume this is a PHP $_FILES array entry
            $upload     = $this->file;
            $this->file = $this->file['name'];
        }

        if (isset($upload) and $copy) {
            throw new FilesystemException(tr('Copy option has been set, but object file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', [':file' => $this->file]));
        }

        $path     = Path::new($path, $this->restrictions)->ensure();
        $this->filename = basename($this->file);

        if (!$this->filename) {
            // We always MUST have a filename
            $this->filename = bin2hex(random_bytes(32));
        }

        // Ensure we have a local copy of the file to work with
        if ($this->file) {
            $this->file = \Phoundation\Web\Http\File::new($this->restrictions)->download($is_downloaded, $context);
        }

        if (!$extension) {
            $extension = Filesystem::getExtension($this->filename);
        }

        if ($length) {
            $targetpath = Strings::slash(file_create_target_path($path, $singledir, $length));

        } else {
            $targetpath = Strings::slash($path);
        }

        $target = $targetpath . strtolower(Strings::convertAccents(Strings::untilReverse($this->filename, '.'), '-'));

        // Check if there is a "point" already in the extension not obligatory at the start of the string
        if ($extension) {
            if (!str_contains($extension, '.')) {
                $target .= '.' . $extension;

            } else {
                $target .= $extension;
            }
        }

        // Only move file is target does not yet exist
        if (file_exists($target)) {
            if (isset($upload)) {
                // File was specified as an upload array
                return $this->moveToTarget($upload, $path, $extension, $singledir, $length, $copy);
            }

            return $this->moveToTarget($path, $extension, $singledir, $length, $copy);
        }

        // Only move if file was specified. If no file specified, then we will only return the available path
        if ($this->file) {
            if (isset($upload)) {
                // This is an uploaded file
                $this->moveToTarget($upload['tmp_name'], $target);

            } else {
                // This is a normal file
                if ($copy and !$is_downloaded) {
                    copy($this->file, $target);

                } else {
                    rename($target);
                    Path::new(dirname($this->file))->clear(false);
                }
            }
        }

        return Strings::from($target, $path);
    }



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
    public function copyTree(string $destination, array $search = null, array $replace = null, string|array $extensions = null, mixed $mode = true, bool $novalidate = false): string
    {
        throw new UnderConstructionException('$this->copyTree() is under construction');

        // Check filesystem restrictions
        $this->restrictions->check($source, false);
        $this->restrictions->check($destination, true);

        // Choose between copy filemode (mode is null), set filemode ($mode is a string or octal number) or preset
        // filemode (take from config, TRUE)
        if (!is_bool($mode) and !is_null($mode)) {
            if (is_string($mode)) {
                $mode = intval($mode, 8);
            }

            $this->filemode = $mode;
        }

        if (substr($destination, 0, 1) != '/') {
            // This is not an absolute path
            $destination = PWD.$destination;
        }

        // Validations
        if (!$novalidate) {
            // Prepare search / replace
            if (!$search) {
                // We can only replace if we search
                $search     = null;
                $replace    = null;
                $extensions = null;

            } else {
                if (!is_array($extensions)) {
                    $extensions = array($extensions);
                }

                if (!is_array($search)) {
                    $search = explode(',', $search);
                }

                if (!is_array($replace)) {
                    $replace = explode(',', $replace);
                }

                if (count($search) != count($replace)) {
                    throw new FilesystemException(tr('The search parameters count ":search" and replace parameters count ":replace" do not match', [
                        ':search'  => count($search),
                        ':replace' => count($replace)
                    ]));
                }
            }

            if (!file_exists($source)) {
                throw new FilesystemException(tr('Specified source ":source" does not exist', [
                    ':source' => $source
                ]));
            }

            $destination = Strings::unslash($destination);

            if (!file_exists($destination)) {
// :TODO: Check if dirname($this->file) here is correct? It somehow does not make sense
                if (!file_exists(dirname($destination))) {
                    throw new FilesystemException(tr('Specified destination ":destination" does not exist', [
                        ':destination' => dirname($destination)
                    ]));
                }

                if (!is_dir(dirname($destination))) {
                    throw new FilesystemException(tr('Specified destination ":destination" is not a directory', [
                        ':destination' => dirname($destination)
                    ]));
                }

                if (is_dir($source)) {
                    // We are copying a directory, destination dir does not yet exist
                    mkdir($destination);

                } else {
                    // We are copying just one file
                }

            } else {
                // Destination already exists,
                if (is_dir($source)) {
                    if (!is_dir($destination)) {
                        throw new FilesystemException(tr('Cannot copy source directory ":source" into destination file ":destination"', [
                            ':source'      => $source,
                            ':destination' => $destination
                        ]));
                    }

                } else {
                    // Source is a file
                    if (!is_dir($destination)) {
                        // Remove destination file since it would be overwritten
                        file_delete($destination, $restrictions);
                    }
                }
            }
        }

        if (is_dir($source)) {
            $source      = Strings::slash($source);
            $destination = Strings::slash($destination);

            foreach (scandir($source) as $this->file) {
                if (($this->file == '.') or ($this->file == '..')) {
                    // Only replacing down
                    continue;
                }

                if (is_null($mode)) {
                    $this->filemode = Config::get('filesystem.modes.defaults.directories', 0640, $mode);

                } elseif (is_link($source . $this->file)) {
                    // No file permissions for symlinks
                    $this->filemode = false;

                } else {
                    $this->filemode = fileperms($source . $this->file);
                }

                if (is_dir($source . $this->file)) {
                    // Recurse
                    if (file_exists($destination . $this->file)) {
                        // Destination path already exists. This -by the way- means that the destination tree was not
                        // clean
                        if (!is_dir($destination . $this->file)) {
                            // Were overwriting here!
                            file_delete($destination . $this->file, $this->restrictions);
                        }
                    }

                    $this->path($destination . $this->file)->ensure($this->filemode);
                }

                file_copy_tree($source . $this->file, $destination . $this->file, $search, $replace, $extensions, $mode, true);
            }

        } else {
            if (is_link($source)) {
                $link = readlink($source);

                if (str_starts_with($link, '/')) {
                    // Absolute link, this is ok
                    $reallink = $link;

                } else {
                    // Relative link, get the absolute path
                    $reallink = Strings::slash(dirname($source)).$link;
                }

                if (!file_exists($reallink)) {
                    // This symlink points to no file, its dead
                    Log::warning(tr('Encountered dead symlink ":source", copying anyway...', [
                        ':source' => $source
                    ]));
                }

                // This is a symlink. Just create a new symlink that points to the same path
                return symlink($link, $destination);
            }

            // Determine mode
            if ($mode === null) {
                $this->filemode = $_CONFIG['file']['file_mode'];

            } elseif ($mode === true) {
                $this->filemode = fileperms($source);
            }

            // Check if the file requires search / replace
            if (!$search) {
                // No search specified, just copy tree
                $doreplace = false;

            } elseif (!$extensions) {
                // No extensions specified, search / replace all files in tree
                $doreplace = true;

            } else {
                // Check extension if we should search / replace
                $doreplace = false;

                foreach ($extensions as $extension) {
                    $len = strlen($extension);

                    if (!substr($source, -$len, $len) != $extension) {
                        $doreplace = true;
                        break;
                    }
                }
            }

            if (!$doreplace) {
                // Just a simple filecopy will suffice
                copy($source, $destination);

            } else {
                $data = file_get_contents($source);

                foreach ($search as $id => $svalue) {
                    if ((substr($svalue, 0, 1 == '/')) and (substr($svalue, -1, 1 == '/'))) {
                        // Do a regex search / replace
                        $data = preg_replace($svalue, $replace[$id], $data);

                    } else {
                        // Do a normal search / replace
                        $data = str_replace($svalue, $replace[$id], $data);
                    }
                }

                // Done, now write to the target file!
                file_put_contents($destination, $data);
            }

            if ($mode) {
                // Update file mode
                try {
                    chmod($destination, $this->filemode);

                }catch(Exception $e) {
                    throw new FilesystemException(tr('Failed to set file mode for ":destination"', [
                        ':destination' => $destination
                    ]), $e);
                }
            }
        }

        return $destination;
    }



    /**
     * Makes a backup of this file to the specified target and returns a new File object for the target
     *
     * @param string $pattern
     * @param bool $move
     * @return static
     */
    public function backup(string $pattern = '~date', bool $move = false): static
    {
        // Pattern shortcuts
        switch ($pattern) {
            case '~':
                $pattern = ':PATH:FILE~';
                break;

            case '~date':
                $pattern = ':PATH:FILE~:DATE';
                break;

            case 'backup/~':
                $pattern = PATH_DATA . 'backups/:FILE~';
                break;

        }

        // Apply pattern
        $dirname  = dirname($this->file) . '/';
        $basename = basename($this->file);

        $target = str_replace(':PATH', $dirname, $pattern);
        $target = str_replace(':FILE', $basename, $target);
        $target = str_replace(':DATE', date('ymd-his'), $target);

        // Make the backup
        if ($move) {
            rename($this->file, $target);
        } else {
            copy($this->file, $target);
        }

        return new static($target, $this->restrictions);
    }



    /**
     * Returns an array with PHP code statistics for this file
     *
     * @return array
     */
    public function getPhpStatistics(): array
    {
        if (!$this->isPhp()) {
            throw new FilesystemException(tr('Cannot gather PHP statistics for file ":file", it is not a PHP file', [
                ':file' => $this->file
            ]));
        }

        $return = [
            'size'           => filesize($this->file),
            'page_estimate'  => (int) (filesize($this->file) / 4096),
            'lines'          => 0,
            'words'          => 0,
            'code_lines'     => 0,
            'blank_lines'    => 0,
            'comment_lines'  => 0,
            'comment_blocks' => 0,
            'functions'      => 0,
            'class_methods'  => 0,
            'classes'        => 0,
            'interfaces'     => 0,
            'traits'         => 0,
            'enums'          => 0
        ];

        $data          = file($this->file);
        $method        = false;
        $block_comment = false;

        // Process file content
        foreach ($data as $line) {
            $line   = trim($line);
            $line   = strtolower($line);

            // Count words
            $words            = preg_split("@[\s+　]@u", $line);
            $return['words'] += count($words);

            if ($block_comment) {
                $return['comment_lines']++;

                // End of comment block
                if (str_contains($line, '*/')) {
                    $block_comment = false;
                    $line = Strings::from($line, '*/');
                } else {
                    // Nope, still block comment
                    continue;
                }
            }

            // Comment line
            if (str_starts_with($line, '//')) {
                $return['comment_lines']++;
                continue;
            }

            // Comment block
            if (str_contains($line, '/*')) {
                $block_comment = true;
                $return['comment_lines']++;
                $return['comment_blocks']++;
                continue;
            }

            // Interfaces
            if (str_starts_with($line, 'interface')) {
                $return['code_lines']++;
                $return['interfaces']++;
                continue;
            }

            // Traits
            if (str_starts_with($line, 'trait')) {
                $return['code_lines']++;
                $return['traits']++;
                continue;
            }

            // Enums
            if (str_starts_with($line, 'enum')) {
                $return['code_lines']++;
                $return['enums']++;
                continue;
            }

            // Clean line
            $line = str_replace(['abstract'], '', $line);
            $line = trim($line);

            // Classes
            if (str_starts_with($line, 'class')) {
                $return['code_lines']++;
                $return['classes']++;
                $method = true;
            } elseif (str_starts_with($line, 'trait')) {
                $return['code_lines']++;
                $return['traits']++;
                $method = true;
            }

            // Clean line
            $line = str_replace(['private', 'protected', 'public', 'static'], '', $line);
            $line = trim($line);

            // Functions & methods
            if (str_starts_with($line, 'function')) {
                $return['code_lines']++;

                if ($method) {
                    $return['class_methods']++;
                } else {
                    $return['functions']++;
                }
                continue;
            }

            // Blank line or code line?
            if (trim($line) == '') {
                $return['blank_lines']++;
            } else {
                $return['code_lines']++;
            }
        }

        $return['lines'] += count($data);

        return $return;
    }



    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return static
     */
    public function ensureReadable(?int $mode = null): static
    {
        // Get configuration. We need file and directory default modes
        $mode = Config::get('filesystem.mode.default.file', 0440, $mode);

        if (!$this->ensureFileReadable($mode)) {
            touch($this->file);
            $this->chmod($mode);
        }

        return $this;
    }



    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return static
     */
    public function ensureWritable(?int $mode = null): static
    {
        // Get configuration. We need file and directory default modes
        $mode = Config::get('filesystem.mode.default.file', 0640, $mode);

        if (!$this->ensureFileWritable($mode)) {
            Log::action(tr('Creating non existing file ":file" with file mode ":mode"', [
                ':mode' => Strings::fromOctal($mode),
                ':file' => $this->file
            ]), 3);

            touch($this->file);
            $this->chmod($mode);
        }

        return $this;
    }



    /**
     * Returns the extension of the object filename
     *
     * @return string
     */
    public function getExtension(): string
    {
        return Strings::fromReverse($this->file, '.');
    }



    /**
     * Ensure that this file has the specified sha256 hash
     *
     * @param string $sha256
     * @param bool $ignore_sha_fail
     * @return $this
     */
    public function checkSha256(string $sha256, bool $ignore_sha_fail = false): static
    {
        $file_sha = FilesystemCommands::new($this->restrictions)->sha256($this->file);

        if ($sha256 !== $file_sha) {
            if (!$ignore_sha_fail) {
                throw new Sha256MismatchException(tr('The SHA256 for file ":file" does not match with the required SHA256', [
                    ':file' => $this->file
                ]));
            }

            Log::warning(tr('WARNING: SHA256 hash for file ":file" does NOT match the required SHA256 hash! Continuing because SHA256 failures are ignored!', [
                ':file' => $this->file
            ]));
        }

        return $this;
    }



    /**
     * Tars this file and returns a file object for the tar file
     *
     * @return File
     */
    public function tar(): File
    {
        return File::new(FilesystemCommands::new($this->restrictions)->tar($this->file), $this->restrictions);
    }



    /**
     * Untars the file
     *
     * @return Path
     */
    public function untar(): Path
    {
        FilesystemCommands::new($this->restrictions)->untar($this->file);
        return Path::new(dirname($this->file), $this->restrictions);
    }



    /**
     * Gzips the file
     *
     * @return $this
     */
    public function gzip(): static
    {
        $file = FilesystemCommands::new($this->restrictions)->gzip($this->file);
        return File::new($file, $this->restrictions);
    }



    /**
     * Ungzips the file
     *
     * @return $this
     */
    public function gunzip(): static
    {
        $file = FilesystemCommands::new($this->restrictions)->gunzip($this->file);
        return File::new($file, $this->restrictions);
    }



    /**
     * Returns the contents of this file as a string
     *
     * @return string
     */
    public function getContentsAsString(): string
    {
        return file_get_contents($this->file);
    }



    /**
     * Returns the contents of this file as an array
     *
     * @return array
     */
    public function getContentsAsArray(): array
    {
        return file($this->file);
    }



    /**
     * Will unzip this file
     *
     * @return static
     */
    public function unzip(): static
    {
        FilesystemCommands::new($this->restrictions)->unzip($this->file);
        return $this;
    }
}
