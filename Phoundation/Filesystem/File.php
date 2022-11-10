<?php

namespace Phoundation\Filesystem;

use Exception;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Date\Date;
use Phoundation\Debug\Php;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Processes\Commands;
use Phoundation\Processes\Exception\ProcessesException;
use Phoundation\Processes\Process;
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
class File
{
    /**
     * File READ method
     */
    public const READ = 1;

    /**
     * File WRITE method
     */
    public const WRITE = 2;

    /**
     * The file access permissions
     *
     * @var Restrictions
     */
    protected Restrictions $restrictions;

    /**
     * The files for this File object
     *
     * @var array|null $files
     */
    protected array|null $files = null;



    /**
     * File class constructor
     *
     * @param array|string|null $file
     * @param Restrictions|array|string|null $restrictions
     */
    public function __construct(array|string|null $file = null, Restrictions|array|string|null $restrictions = null)
    {
        Filesystem::validateFilename($file);

        $this->files = Arrays::force($file, null);
        $this->setRestrictions($restrictions);
    }



    /**
     * Returns a new File object with the specified restrictions
     *
     * @param array|string|null $file
     * @param Restrictions|array|string|null $restrictions
     * @return File
     */
    public static function new(array|string|null $file, Restrictions|array|string|null $restrictions = null): File
    {
        return new File($file, $restrictions);
    }



    /**
     * Returns a Php object
     *
     * @param array|string|null $paths
     * @return Php
     */
    public function php(array|string|null $paths = null): Php
    {
        return new Php($paths, $this->restrictions);
    }



    /**
     * Returns the filesystem restrictions for this File object
     *
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Sets the filesystem restrictions for this File object
     *
     * @param Restrictions|array|string|null $restrictions
     * @return void
     */
    public function setRestrictions(Restrictions|array|string|null $restrictions): void
    {
        $this->restrictions = Core::ensureRestrictions($restrictions);
    }



    /**
     * Returns the files for this File object
     *
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }



    /**
     * Returns the first file for this File object
     *
     * @return string
     */
    public function getFile(): string
    {
        return Arrays::firstValue($this->files);
    }



    /**
     * Returns the file mode for the object file
     *
     * @return int
     */
    public function getMode(): int
    {
        return $this->getStat()['mode'];
    }



    /**
     * Returns the stat data for the object file
     *
     * @return array
     */
    public function getStat(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, false);
        $this->requireSingleFile();

        try {
            $stat = stat($this->files);

            if ($stat) {
                return $stat;
            }
        } catch (Throwable $e) {
            $this->checkReadable(null, false, $e);
        }
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
        $this->checkRestrictions($this->files, true);

        foreach ($this->files as $file) {
            // Check filesystem restrictions
            $this->checkRestrictions($file, true);

            // Make sure the file path exists
            Path::new(dirname($file), $this->restrictions)->ensure();

            $h = $this->open('a');
            fwrite($h, $data);
            fclose($h);
        }

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
        $this->checkRestrictions($this->files, true);

        foreach ($this->files as $file) {
            // Ensure the target path exists
            Path::new(dirname($file), $this->restrictions)->ensure();

            // Open target file
            try {
                $target_h = $this->open('a', $file);
            } catch (Throwable $e) {
                // Failed to open the target file
                $this->checkReadable('target', true, $e);
            }

            // Open each source file
            foreach (Arrays::force($sources, null) as $source) {
                try {
                    $source_h = $this->open('r', $source);
                } catch (Throwable $e) {
                    // Failed to open one of the sources, get rid of the partial target file
                    $this->delete();
                    $this->checkReadable('source', true, $e);
                }

                while (!feof($source_h)) {
                    $data = fread($source_h, 8192);
                    fwrite($target_h, $data);
                }

                fclose($source_h);
            }

            fclose($target_h);
        }

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

        $this->checkRestrictions($source     , true);
        $this->checkRestrictions($destination, true);

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
     * @version 2.4.16: Added documentation, improved log output
     *
     * @param null $mode If the specified $this->file does not exist, it will be created with this file mode. Defaults to $_CONFIG[fs][file_mode]
     * @param null $pattern_mode If parts of the path for the file do not exist, these will be created as well with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @return void
     */
    public function ensureFile($mode = null, $pattern_mode = null): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions(dirname($this->files), true);

        $mode = Config::get('filesystem.modes.defaults.file', 0640, $mode);

        foreach ($this->files as $file) {
            Path::new(dirname($file), $this->restrictions)->ensure($pattern_mode);

            if (!file_exists($file)) {
                // Create the file
                Path::new(dirname($file), $this->restrictions)->each()
                    ->setMode(0770)
                    ->executePath(function() use ($file, $mode) {
                        Log::warning(tr('File ":file" did not exist and was created empty to ensure system stability, but information may be missing', [
                            ':file' => $file
                        ]));

                        touch($file);

                        if ($mode) {
                            File::new($file, $this->restrictions)->chmod($mode);
                        }
                    });
            }
        }
    }



    /**
     * Returns the mimetype data for the object file
     *
     * @version 2.4: Added documentation
     * @return string The mimetype data for the object file
     */
    public function mimetype(): string
    {
        static $finfo = false;

        // Check filesystem restrictions
        $this->checkRestrictions($this->files, false);
        $this->requireSingleFile();

        foreach ($this->files as $file) {
            try {
                if (!$finfo) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                }

                $mimetype = finfo_file($finfo, $file);
                return $mimetype;
            } catch (Exception $e) {
                // We failed to get mimetype data. Find out why and throw exception
                $this->checkReadable('', true, new FilesystemException(tr('Failed to get mimetype information for file ":file"', [
                    ':file' => $file
                ]), previous: $e));
            }
        }
    }



    /**
     * Returns true or false if file is ASCII or not
     *
     * @version 2.4: Added documentation
     * @return bool True if the file is a text file, false if not
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
        foreach ($this->files as $file) {
            if (str_ends_with($file, '.php')) {
                if ($this->isText()) {
                    return true;
                }
            }
        }

        return false;
    }



    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @see Restrictions::check() This function uses file location restrictions
     *
     * @param boolean $clean_path If specified true, all directories above each specified pattern will be deleted as well as long as they are empty. This way, no empty directories will be left laying around
     * @param boolean $sudo If specified true, the rm command will be executed using sudo
     * @return void
     */
    public function delete(bool $clean_path = true, bool $sudo = false): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, true);

        // Delete all specified patterns
        foreach ($this->files as $file) {
            // Execute the rm command
            Process::new('rm')
                ->setSudo($sudo)
                ->setTimeout(10)
                ->addArgument($file)
                ->addArgument('-rf')
                ->executeReturnArray();

            // If specified to do so, clear the path upwards from the specified pattern
            if ($clean_path) {
                Path::new(dirname($file))->clear($sudo);
            }
        }
    }



    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
     * @return string
     */
    public function switchMode(string|int $mode): string
    {
        $old_mode = $this->getMode();
        $this->chmod($mode);

        return $old_mode;
    }



    /**
     * Update the object file owner and group
     *
     * @note This function ALWAYS requires sudo as chown is a root only filesystem command
     * @param string|null $user
     * @param string|null $group
     * @param bool $recursive
     * @return void
     */
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, true);

        if (!$user) {
            $user = posix_getpwuid(posix_getuid());
            $user = $user['name'];
        }

        if (!$group) {
            $group = posix_getpwuid(posix_getuid());
            $group = $group['name'];
        }

        foreach ($this->files as $pattern) {
            Process::new('chown')
                ->setSudo(true)
                ->addArgument($recursive ? '-R' :null)
                ->addArgument($user.':' . $group)
                ->addArguments($this->files)
                ->executeReturnArray();
        }
    }



    /**
     * Change file mode, optionally recursively
     *
     * @see $this->chown()
     *
     * @param string|int $mode   The mode to apply to the specified path (and all files below if recursive is specified)
     * @param boolean $recursive If set to true, apply specified mode to the specified path and all files below by
     *                           recursion
     * @param bool $sudo
     * @return void
     */
    public function chmod(string|int $mode, bool $recursive = false, bool $sudo = false): void
    {
        if (!($mode)) {
            throw new OutOfBoundsException(tr('No file mode specified'));
        }

        if (!$this->files) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        // Check filesystem restrictions
        $this->checkRestrictions($this->files, true);

        Process::new('chmod')
            ->setSudo($sudo)
            ->addArgument($recursive ? '-R' :null)
            ->addArgument($mode)
            ->addArguments($this->files)
            ->executeReturnArray();
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
    public function copyProgress(string $target, callable $callback): void
    {
        $this->requireSingleFile();
        $this->checkRestrictions($this->files, true);
        $this->checkRestrictions($target     , false);

        foreach ($this->files as $source) {
            $context = stream_context_create();

            stream_context_set_params($context, [
                'notification' => $callback
            ]);

            copy($source, $target, $context);
        }
    }



    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param string $mode
     * @param resource $context
     * @return resource
     */
    public function open(#[ExpectedValues(values:['r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'ce+'])] string $mode, $context = null)
    {
        $this->requireSingleFile();
        $this->checkRestrictions($this->files, true);

        foreach ($this->files as $file) {
            // Check filesystem restrictions
            $handle = fopen($file, $mode, false, $context);

            if (!$handle) {
                // Check if the mode is valid and if the file can be opened for the requested mode
                $method = match ($mode) {
                    'r' => FILE::READ,
                    'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'ce+' => FILE::WRITE,
                    default => throw new FilesystemException(tr('Could not open file ":file"', [':file' => $this->files])),
                };

                // Mode is valid, check if file is accessible.
                switch ($method) {
                    case FILE::READ:
                        $this->checkReadable();
                        break;

                    case FILE::WRITE:
                        $this->checkWritable();
                        break;
                }

                throw new FilesystemException(tr('Failed to open file ":file"', [':file' => $this->files]));
            }

            return $handle;
        }
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
     * @param string|null $type          This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param bool $no_directories       If true, the object file cannot be a directory
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return void
     */
    #[NoReturn] public function checkReadable(?string $type = null, bool $no_directories = false, ?Throwable $previous_e = null) : void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, false);

        foreach ($this->files as $file) {
            if (!file_exists($file)) {
                if (!file_exists(dirname($file))) {
                    // The file doesn't exist and neither does its parent directory
                    throw new FilesystemException(tr('The:type file ":file" cannot be read because the directory ":path" does not exist', [
                        ':type' => ($type ? '' : ' ' . $type),
                        ':file' => $file,
                        ':path' => dirname($file)
                    ]), previous: $previous_e);
                }

                throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $file
                ]), previous: $previous_e);
            }

            if (!is_readable($file)) {
                throw new FilesystemException(tr('The:type file ":file" cannot be read', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $file
                ]), previous: $previous_e);
            }

            if ($no_directories and is_dir($file)) {
                throw new FilesystemException(tr('The:type file ":file" cannot be read because it is a directory', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $file
                ]), previous: $previous_e);
            }

            if ($previous_e) {
                throw $previous_e;

//            // This method was called because a read action failed, throw an exception for it
//            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [
//                ':type' => ($type ? '' : ' ' . $type),
//                ':file' => $this->file
//            ]), previous: $previous_e);
            }
        }
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
     * @param bool $no_directories       If true, the object file cannot be a directory
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return void
     */
    #[NoReturn] public function checkWritable(?string $type = null, bool $no_directories = false, ?Throwable $previous_e = null) : void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, true);

        foreach ($this->files as $file) {
            if (!file_exists($file)) {
                if (!file_exists(dirname($file))) {
                    // The file doesn't exist and neither does its parent directory
                    throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist and neither does the parent path ":path"', [
                        ':type' => ($type ? '' : ' ' . $type),
                        ':file' => $file,
                        ':path' => dirname($file)
                    ]), previous: $previous_e);
                }

                throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $file
                ]), previous: $previous_e);
            }

            if (!is_readable($file)) {
                throw new FilesystemException(tr('The:type file ":file" cannot be written', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $file
                ]), previous: $previous_e);
            }

            if ($no_directories and is_dir($file)) {
                throw new FilesystemException(tr('The:type file ":file" cannot be written because it is a directory', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $file
                ]), previous: $previous_e);
            }

            if ($previous_e) {
                throw $previous_e;
            }
        }
    }



    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return void
     */
    public function ensureWritable(?int $mode = null): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, true);

        // Get configuration. We need file and directory default modes
        $mode = Config::get('filesystem.mode.default.file', 0640, $mode);

        foreach ($this->files as $file) {
            // If the object file exists and is writable, then we're done.
            if (is_writable($file)) {
                continue;
            }

            // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...

            if (file_exists($file)) {
                // Great! The file exists, but it is not writable at this moment. Try to make it writable.
                try {
                    Log::warning(tr('The object file ":file" (Realpath ":path") is not writable. Attempting to apply default file mode ":mode"', [
                        ':file' => $file,
                        ':path' => realpath($file),
                        ':mode' => $mode
                    ]));

                    $this->chmod('u+w');

                } catch (ProcessesException $e) {
                    throw new FileNotWritableException(tr('The object file ":file" (Realpath ":path") is not writable, and could not be made writable', [
                        ':file' => $file,
                        ':path' => realpath($file)
                    ]));
                }
            }

            // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
            Path::new(dirname($file), $this->restrictions)->ensure();

            Log::warning(tr('The object file ":file" (Realpath ":path") does not exist. Attempting to create it with file mode ":mode"', [
                ':mode' => Strings::fromOctal($mode),
                ':file' => $this->files,
                ':path' => realpath($file)
            ]));

            touch($file);
            $this->chmod($mode);
        }
    }



    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileType(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, true);
        $return = [];

        foreach ($this->files as $file) {
            $this->fileExists($file);

            $perms     = fileperms($file);
            $socket    = (($perms & 0xC000) == 0xC000);
            $symlink   = (($perms & 0xA000) == 0xA000);
            $regular   = (($perms & 0x8000) == 0x8000);
            $bdevice   = (($perms & 0x6000) == 0x6000);
            $cdevice   = (($perms & 0x2000) == 0x2000);
            $directory = (($perms & 0x4000) == 0x4000);
            $fifopipe  = (($perms & 0x1000) == 0x1000);

            if ($socket) {
                // This file is a socket
                $return[$file] = 'socket';

            } elseif ($symlink) {
                // This file is a symbolic link
                $return[$file] = 'symbolic link';

            } elseif ($regular) {
                // This file is a regular file
                $return[$file] = 'regular file';

            } elseif ($bdevice) {
                // This file is a block device
                $return[$file] = 'block device';

            } elseif ($directory) {
                // This file is a directory
                $return[$file] = 'directory';

            } elseif ($cdevice) {
                // This file is a character device
                $return[$file] = 'character device';

            } elseif ($fifopipe) {
                // This file is a FIFO pipe
                $return[$file] = 'fifo pipe';
            } else {
                // This file is an unknown type
                $return[$file] = 'unknown';
            }
        }

        return $return;
    }


    
    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileMode(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, false);
        $return = [];

        foreach ($this->files as $file) {
            $this->fileExists($file);

            $perms = fileperms($file);
            $mode  = [];

            $mode['socket']    = (($perms & 0xC000) == 0xC000);
            $mode['symlink']   = (($perms & 0xA000) == 0xA000);
            $mode['regular']   = (($perms & 0x8000) == 0x8000);
            $mode['bdevice']   = (($perms & 0x6000) == 0x6000);
            $mode['cdevice']   = (($perms & 0x2000) == 0x2000);
            $mode['directory'] = (($perms & 0x4000) == 0x4000);
            $mode['fifopipe']  = (($perms & 0x1000) == 0x1000);
            $mode['perms']     = $perms;
            $mode['unknown']   = false;

            if ($mode['socket']) {
                // This file is a socket
                $mode['mode'] = 's';
                $mode['type'] = 'socket';

            } elseif ($mode['symlink']) {
                // This file is a symbolic link
                $mode['mode'] = 'l';
                $mode['type'] = 'symbolic link';

            } elseif ($mode['regular']) {
                // This file is a regular file
                $mode['mode'] = '-';
                $mode['type'] = 'regular file';

            } elseif ($mode['bdevice']) {
                // This file is a block device
                $mode['mode'] = 'b';
                $mode['type'] = 'block device';

            } elseif ($mode['directory']) {
                // This file is a directory
                $mode['mode'] = 'd';
                $mode['type'] = 'directory';

            } elseif ($mode['cdevice']) {
                // This file is a character device
                $mode['mode'] = 'c';
                $mode['type'] = 'character device';

            } elseif ($mode['fifopipe']) {
                // This file is a FIFO pipe
                $mode['mode'] = 'p';
                $mode['type'] = 'fifo pipe';

            } else {
                // This file is an unknown type
                $mode['mode']    = 'u';
                $mode['type']    = 'unknown';
                $mode['unknown'] = true;
            }

            $mode['owner'] = [
                'r' =>  ($perms & 0x0100),
                'w' =>  ($perms & 0x0080),
                'x' => (($perms & 0x0040) and !($perms & 0x0800)),
                's' => (($perms & 0x0040) and  ($perms & 0x0800)),
                'S' =>  ($perms & 0x0800)
            ];

            $mode['group'] = [
                'r' =>  ($perms & 0x0020),
                'w' =>  ($perms & 0x0010),
                'x' => (($perms & 0x0008) and !($perms & 0x0400)),
                's' => (($perms & 0x0008) and  ($perms & 0x0400)),
                'S' =>  ($perms & 0x0400)
            ];

            $mode['other'] = [
                'r' =>  ($perms & 0x0004),
                'w' =>  ($perms & 0x0002),
                'x' => (($perms & 0x0001) and !($perms & 0x0200)),
                't' => (($perms & 0x0001) and  ($perms & 0x0200)),
                'T' =>  ($perms & 0x0200)
            ];

            // Owner
            $mode['mode'] .= (($perms & 0x0100) ? 'r' : '-');
            $mode['mode'] .= (($perms & 0x0080) ? 'w' : '-');
            $mode['mode'] .= (($perms & 0x0040) ?
                (($perms & 0x0800) ? 's' : 'x' ) :
                (($perms & 0x0800) ? 'S' : '-'));

            // Group
            $mode['mode'] .= (($perms & 0x0020) ? 'r' : '-');
            $mode['mode'] .= (($perms & 0x0010) ? 'w' : '-');
            $mode['mode'] .= (($perms & 0x0008) ?
                (($perms & 0x0400) ? 's' : 'x' ) :
                (($perms & 0x0400) ? 'S' : '-'));

            // Other
            $mode['mode'] .= (($perms & 0x0004) ? 'r' : '-');
            $mode['mode'] .= (($perms & 0x0002) ? 'w' : '-');
            $mode['mode'] .= (($perms & 0x0001) ?
                (($perms & 0x0200) ? 't' : 'x' ) :
                (($perms & 0x0200) ? 'T' : '-'));

            $return[$file] = $mode;
        }

        return $return;
    }



    /**
     * Returns if the link target exists or not
     *
     * @return bool
     */
    public function linkTargetExists(): bool
    {
        throw new UnderConstructionException();
        if (file_exists($this->files)) {
            return false;
        }

        if (is_link()) {
            throw new FilesystemException(tr('Symlink ":source" has non existing target ":target"', [
                'source' => $this->files,
                ':target' => readlink()
            ]));
        }

        throw new FilesystemException(tr('Symlink ":source" has non existing target ":target"', [
            'source' => $this->files,
            ':target' => readlink()
        ]));
    }



    /**
     * Open the specified source, read the contents, and replace $search with $replace. Write results in $target
     * $replaces should be a $search => $replace key value array, where the $search values are regex expressions
     *
     * @param string $source
     * @param string $target
     * @param array $replaces
     * @return void
     */
    public function searchReplace(string $source, string $target, array $replaces): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($source, false);
        $this->checkRestrictions($target, true);

        if (!file_exists($source)) {
            throw new FilesystemException(tr('Specified source file ":source" does not exist', [
                ':source' => $source
            ]));
        }

        if (!file_exists(dirname($target))) {
            throw new FilesystemException(tr('Specified target path ":target" does not exist', [
                ':target' => $target
            ]));
        }

        $fs       = fopen($source, 'r');
        $ft       = fopen($target, 'w');
        $position = 0;
        $length   = 8192;
        $this->filesize = filesize($source);

        while ($position < $this->filesize) {
            $data      = fread($fs, $length);
            $position += $length;
            fseek($fs, $position);

            // Execute search / replaces
            foreach ($replaces as $search => $replace) {
                $data = preg_replace($search, $replace, $data);
            }

            fwrite($ft, $data, strlen($data));
        }

        fclose($fs);
        fclose($ft);
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
     * Move specified path to a backup
     *
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function moveToBackup(string $path, string $name): bool
    {
        throw new UnderConstructionException();
        // Check filesystem restrictions
        $this->checkRestrictions($path, false);

        if (!file_exists($path)) {
            Log::warning(tr('Cannot move the specified path ":path" to backup, it does not exist', [
                ':path' => $path
            ]));

            return false;
        }

        $backup_path = $path . '~' . Date::convert(null, 'Ymd-His');

        //
        if (file_exists($backup_path)) {
            // Backup already exists as well, script run twice in under a second. Delete the current one as the backup
            // was generated less than a second ago
            $this->delete($path, PATH_ROOT.'data/backups/' . $name . '/');
            return true;
        }

        rename($path, $backup_path);
        return true;
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
        $this->checkRestrictions($this->files, true);
        $this->requireSingleFile();

        foreach ($this->files as $file) {
            // Build up the path
            if (str_starts_with($file, '/')) {
                if ($prefix) {
                    throw new FilesystemException(tr('The specified file ":file" is absolute, which requires $prefix to be null, but it is ":prefix"', [
                        ':file'   => $file,
                        ':prefix' => $prefix
                    ]));
                }

                $location = '/';

            } else {
                // Specified $pattern is relative, so prefix it with $prefix
                if (!str_starts_with($prefix, '/')) {
                    throw new FilesystemException(tr('The specified file ":file" is relative, which requires an absolute $prefix but it is ":prefix"', [
                        ':file'   => $file,
                        ':prefix' => $prefix
                    ]));
                }

                $location = Strings::endsWith($prefix, '/');
            }

            $file = Strings::endsNotWith(Strings::startsNotWith($file, '/'), '/');

            // Check filesystem restrictions
            $this->checkRestrictions($file, false);

            foreach (explode('/', $file) as $section) {
                $location .= $section;

                if (!file_exists($location)) {
                    throw new FilesystemException(tr('The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', [
                        ':path'     => $file,
                        ':prefix'   => $prefix,
                        ':location' => $location
                    ]));
                }

                if (is_link($location)) {
                    return true;
                }

                $location .= '/';
            }
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
     * Search / replace the object files
     *
     * @param array $replace The list of keys that will be replaced by values
     * @return void
     */
    public function replace(array $replace): void
    {
        // Check filesystem restrictions and if file exists
        $this->checkRestrictions($this->files, true);

        foreach ($this->files as $file) {
            $this->fileExists($file);

            $data = file_get_contents($file);
            $data = str_replace(array_keys($replace), $replace, $data);

            file_put_contents($file, $data);
        }
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
        // Check filesystem restrictions
        $this->checkRestrictions($this->files, false);

        $return = [];

        // Validate filters
        foreach (Arrays::force($filters, null) as $filter) {
            if (!is_scalar($filter)) {
                throw new OutOfBoundsException(tr('The filter ":filter" is invalid, only string filters are allowed', [
                    ':filter' => $filter
                ]));
            }

            // Libraries the return array
            $return[$filter] = [];
        }

        // Open the file and start scanning each line
        $count  = 0;

        foreach ($this->files as $file) {
            $handle = $this->open($this->files, 'r');

            while (($line = fgets($handle, 8096)) !== false) {
                foreach ($filters as $filter) {
                    if (str_contains($line, $filter)) {
                        $return[$file][$filter][] = $line;
                    }
                }

                if ($until_line and (++$count >= $until_line)) {
                    // We're done, get out
                    break;
                }
            }

            fclose($handle);
        }

        return $return;
    }



    /**
     * Check the specified $path against this objects' restrictions
     *
     * @param array|string $path
     * @param bool $write
     * @return void
     */
    protected function checkRestrictions(array|string $paths, bool $write)
    {
        $this->restrictions->check($paths, $write);
    }



    /**
     * Checks if the specified file exists
     *
     * @param string|null $file
     * @return void
     */
    protected function fileExists(?string $file): void
    {
        if (!file_exists($file)) {
            throw new FilesystemException(tr('Specified file ":file" does not exist', [':file' => $file]));
        }
    }



    /**
     * Requires that this File object has only one file
     *
     * @return void
     */
    protected function requireSingleFile(): void
    {
        if (count($this->files) > 1) {
            throw new OutOfBoundsException(tr('File object has ":count" files specified while only one file is allowed', [
                'count' => count($this->files)
            ]));
        }
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
        $this->checkRestrictions($this->files, false);
        $this->checkRestrictions($path, true);

        if (is_array($this->files)) {
            // Assume this is a PHP $_FILES array entry
            $upload     = $this->files;
            $this->files = $this->files['name'];
        }

        if (isset($upload) and $copy) {
            throw new FilesystemException(tr('Copy option has been set, but object file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', [':file' => $this->files]));
        }

        $path     = Path::new($path, $this->restrictions)->ensure();
        $filename = basename($this->files);

        if (!$filename) {
            // We always MUST have a filename
            $filename = bin2hex(random_bytes(32));
        }

        // Ensure we have a local copy of the file to work with
        if ($this->files) {
            $this->files = \Phoundation\Web\Http\File::new($this->restrictions)->download($is_downloaded, $context);
        }

        if (!$extension) {
            $extension = Filesystem::getExtension($filename);
        }

        if ($length) {
            $targetpath = Strings::slash(file_create_target_path($path, $singledir, $length));

        } else {
            $targetpath = Strings::slash($path);
        }

        $target = $targetpath . strtolower(Strings::convertAccents(Strings::untilReverse($filename, '.'), '-'));

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
        if ($this->files) {
            if (isset($upload)) {
                // This is an uploaded file
                $this->moveToTarget($upload['tmp_name'], $target);

            } else {
                // This is a normal file
                if ($copy and !$is_downloaded) {
                    copy($this->files, $target);

                } else {
                    rename($target);
                    Path::new(dirname($this->files))->clear(false);
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
        $this->checkRestrictions($source, false);
        $this->checkRestrictions($destination, true);

        // Choose between copy filemode (mode is null), set filemode ($mode is a string or octal number) or preset
        // filemode (take from config, TRUE)
        if (!is_bool($mode) and !is_null($mode)) {
            if (is_string($mode)) {
                $mode = intval($mode, 8);
            }

            $filemode = $mode;
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

            foreach (scandir($source) as $file) {
                if (($file == '.') or ($file == '..')) {
                    // Only replacing down
                    continue;
                }

                if (is_null($mode)) {
                    $filemode = Config::get('filesystem.modes.defaults.directories', 0640, $mode);

                } elseif (is_link($source . $file)) {
                    // No file permissions for symlinks
                    $filemode = false;

                } else {
                    $filemode = fileperms($source . $file);
                }

                if (is_dir($source . $file)) {
                    // Recurse
                    if (file_exists($destination . $file)) {
                        // Destination path already exists. This -by the way- means that the destination tree was not
                        // clean
                        if (!is_dir($destination . $file)) {
                            // Were overwriting here!
                            file_delete($destination . $file, $restrictions);
                        }
                    }

                    $this->path($destination . $file)->ensure($filemode);
                }

                file_copy_tree($source . $this->files, $destination . $file, $search, $replace, $extensions, $mode, true);
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
                $filemode = $_CONFIG['file']['file_mode'];

            } elseif ($mode === true) {
                $filemode = fileperms($source);
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
                    chmod($destination, $filemode);

                }catch(Exception $e) {
                    throw new FilesystemException(tr('Failed to set filemode for ":destination"', array(':destination' => $destination)), $e);
                }
            }
        }

        return $destination;
    }
}