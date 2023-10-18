<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\Enums\Interfaces\EnumFileOpenModeInterface;
use Phoundation\Filesystem\Exception\FileActionFailedException;
use Phoundation\Filesystem\Exception\FileExistsException;
use Phoundation\Filesystem\Exception\FileNotOpenException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FileOpenException;
use Phoundation\Filesystem\Exception\FileReadException;
use Phoundation\Filesystem\Exception\FileRenameException;
use Phoundation\Filesystem\Exception\FileSyncException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\FileTruncateException;
use Phoundation\Filesystem\Exception\PathNotExistsException;
use Phoundation\Filesystem\Exception\ReadOnlyModeException;
use Phoundation\Filesystem\Interfaces\FileBasicsInterface;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Traits\UsesRestrictions;
use Stringable;
use Throwable;


/**
 * FileBasics class
 *
 * This library contains the variables used in the File class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class FileBasics implements Stringable, FileBasicsInterface
{
    use UsesRestrictions;


    /**
     * The real path to this file
     *
     * @var string|false|null $real_file
     */
    protected string|false|null $real_file = null;

    /**
     * The target file name in case operations create copies of this file
     *
     * @var string|null $target
     */
    protected ?string $target = null;

    /**
     * The file for this object
     *
     * @var string|null $file
     */
    protected ?string $file = null;

    /**
     * The stream, if this file is opened
     *
     * @var mixed $stream
     */
    protected mixed $stream = null;

    /**
     * The type for this file
     *
     * @var int $type
     */
    protected int $type;


    /**
     * If the file is opened, specifies how it was opened
     *
     * @var EnumFileOpenModeInterface|null $open_mode
     */
    protected ?EnumFileOpenModeInterface $open_mode = null;


    /**
     * File class constructor
     *
     * @param mixed $file
     * @param RestrictionsInterface|array|string|null $restrictions
     */
    public function __construct(mixed $file = null, RestrictionsInterface|array|string|null $restrictions = null)
    {
        if (is_null($file) or is_string($file) or ($file instanceof Stringable)) {
            // Specified file was actually a File or Path object, get the file from there
            if ($file instanceof FileBasics) {
                $this->setFile($file->getFile());
                $this->setTarget($file->getTarget());
                $this->setRestrictions($restrictions ?? $file->getRestrictions());

            } else {
                $this->setFile((string)$file);
                $this->setRestrictions($restrictions);
            }
        } elseif (is_resource($file)) {
            // This is an input stream resource
            $this->stream = $file;
            $this->file = '?';

        } else {
            throw new OutOfBoundsException(tr('Invalid file ":file" specified. Must be one if FileBasicsInterface, Stringable, string, null, or resource', [
                ':file' => $file
            ]));
        }
    }


    /**
     * FileBasics class toString method
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getFile();
    }


    /**
     * Returns a new Path object with the specified restrictions
     *
     * @param mixed $file
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public static function new(mixed $file = null, RestrictionsInterface|array|string|null $restrictions = null): static
    {
        return new static($file, $restrictions);
    }


    /**
     * Returns a new Path object with the specified restrictions starting from the specified path, applying a number of
     * defaults
     *
     * . is PATH_ROOT
     * ~ is the current shell's user home directory
     *
     * @param FileBasics|Stringable|string|null $file
     * @param RestrictionsInterface|array|string|null $restrictions
     * @return static
     */
    public static function default(FileBasics|Stringable|string|null $file = null, RestrictionsInterface|array|string|null $restrictions = null): static
    {
        // Determine what path to choose from the specified file
        if ($file) {
            $file = trim((string) $file);

            switch ($file[0]) {
                case '/':
                    // This is an absolute path already
                    break;

                case '.':
                    // This is a path starting at PATH_ROOT
                    $file = PATH_ROOT . Strings::startsNotWith(substr($file, 1), '/');
                    break;

                case '~':
                    // This starts at the users home directory
                    if (empty($_SERVER['HOME'])) {
                        throw new OutOfBoundsException(tr('Cannot determine this users home directory'));
                    }

                    $file = Strings::endsWith($_SERVER['HOME'], '/') . Strings::startsNotWith(substr($file, 1), '/');
                    break;
            }
        }

        Log::warning($file, echo_screen: false);
        return new static($file, $restrictions);
    }


    /**
     * Returns the configured file buffer size
     *
     * @param int|null $requested_buffer_size
     * @return int
     */
    public function getBufferSize(?int $requested_buffer_size = null): int
    {
        $required  = $requested_buffer_size ?? Config::get('filesystem.buffer.size', $this->buffer_size ?? 4096);
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
     * Returns the stream for this file if its opened. Will return NULL if closed
     *
     * @return mixed
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }


    /**
     * Returns the file
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }


    /**
     * Sets the file for this File object
     *
     * @param Stringable|string|null $file
     * @param string|null $prefix
     * @param bool $must_exist
     * @return static
     */
    public function setFile(Stringable|string|null $file, string $prefix = null, bool $must_exist = false): static
    {
        if ($this->isOpen()) {
            $this->close();
        }

        $this->file = Filesystem::absolute($file, $prefix, $must_exist);
        $this->real_file = realpath($this->file);

        return $this;
    }


    /**
     * Sets the target file name in case operations create copies of this file
     *
     * @param Stringable|string $target
     * @return static
     */
    public function setTarget(Stringable|string $target): static
    {
        $this->target = Filesystem::absolute($target, null, false);
        return $this;
    }


    /**
     * Returns the target file name in case operations create copies of this file
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        if ($this->target === null) {
            // By default, assume target is the same as the source file
            return $this->file;
        }

        return $this->target;
    }


    /**
     * Checks if the specified file exists
     *
     * @return static
     */
    protected function exists(): static
    {
        if (!file_exists($this->file)) {
            throw new FilesystemException(tr('Specified file ":file" does not exist', [':file' => $this->file]));
        }

        return $this;
    }


    /**
     * Renames a file or directory
     *
     * @param string $to_filename
     * @param $context
     * @return $this
     */
    public function rename(string $to_filename, $context = null): static
    {
        $result = rename($this->file, $to_filename, $context);

        if (!$result) {
            throw new FileRenameException(tr('Failed to rename file or directory ":file" to ":to"', [
                ':file' => $this->file,
                ':to'   => $to_filename
            ]));
        }

        $this->file = $to_filename;
        return $this;
    }


    /**
     * Truncates a file to a given length
     *
     * @param int $size
     * @return $this
     */
    public function truncate(int $size): static
    {
        $result = ftruncate($this->stream, $size);

        if (!$result) {
            throw new FileTruncateException(tr('Failed to truncate file ":file" to ":size" bytes', [
                ':file' => $this->file,
                ':size' => $size
            ]));
        }

        return $this;
    }


    /**
     * Output all remaining data on a file pointer to the output buffer
     *
     * @return int The amount of bytes
     */
    public function fpassthru(): int
    {
        $size = fpassthru($this->stream);

        return $size;
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
     * @param string|null $type This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     * @return static
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, false);

        if (!file_exists($this->file)) {
            if (!file_exists(dirname($this->file))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be read because the directory ":path" does not exist', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $this->file,
                    ':path' => dirname($this->file)
                ]), $previous_e);
            }

            throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), $previous_e);
        }

        if (!is_readable($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;

//            // This method was called because a read action failed, throw an exception for it
//            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [
//                ':type' => ($type ? '' : ' ' . $type),
//                ':file' => $this->file
//            ]), $previous_e);
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
     * @param string|null $type This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return static
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null): static
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        if (!file_exists($this->file)) {
            if (!file_exists(dirname($this->file))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist and neither does the parent path ":path"', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $this->file,
                    ':path' => dirname($this->file)
                ]), $previous_e);
            }

            // File doesn't exist, check if the parent directory is writable so that the file can be created
            Path::new(dirname($this->file), $this->restrictions)->checkWritable($type, $previous_e);

        } elseif (!is_writable($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be written', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), $previous_e);
        }

        return $this;
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
        $this->restrictions->check($this->file, true);
        $this->exists();

        $return = [];
        $perms = fileperms($this->file);

        $socket = (($perms & 0xC000) == 0xC000);
        $symlink = (($perms & 0xA000) == 0xA000);
        $regular = (($perms & 0x8000) == 0x8000);
        $bdevice = (($perms & 0x6000) == 0x6000);
        $cdevice = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe = (($perms & 0x1000) == 0x1000);

        if ($socket) {
            // This file is a socket
            $return = 'socket';

        } elseif ($symlink) {
            // This file is a symbolic link
            $return = 'symbolic link';

        } elseif ($regular) {
            // This file is a regular file
            $return = 'regular file';

        } elseif ($bdevice) {
            // This file is a block device
            $return = 'block device';

        } elseif ($directory) {
            // This file is a directory
            $return = 'directory';

        } elseif ($cdevice) {
            // This file is a character device
            $return = 'character device';

        } elseif ($fifopipe) {
            // This file is a FIFO pipe
            $return = 'fifo pipe';
        } else {
            // This file is an unknown type
            $return = 'unknown';
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
        $this->restrictions->check($this->file, false);
        $this->exists();

        $perms = fileperms($this->file);
        $return = [];

        $return['socket'] = (($perms & 0xC000) == 0xC000);
        $return['symlink'] = (($perms & 0xA000) == 0xA000);
        $return['regular'] = (($perms & 0x8000) == 0x8000);
        $return['bdevice'] = (($perms & 0x6000) == 0x6000);
        $return['cdevice'] = (($perms & 0x2000) == 0x2000);
        $return['directory'] = (($perms & 0x4000) == 0x4000);
        $return['fifopipe'] = (($perms & 0x1000) == 0x1000);
        $return['perms'] = $perms;
        $return['unknown'] = false;

        if ($return['socket']) {
            // This file is a socket
            $return['mode'] = 's';
            $return['type'] = 'socket';

        } elseif ($return['symlink']) {
            // This file is a symbolic link
            $return['mode'] = 'l';
            $return['type'] = 'symbolic link';

        } elseif ($return['regular']) {
            // This file is a regular file
            $return['mode'] = '-';
            $return['type'] = 'regular file';

        } elseif ($return['bdevice']) {
            // This file is a block device
            $return['mode'] = 'b';
            $return['type'] = 'block device';

        } elseif ($return['directory']) {
            // This file is a directory
            $return['mode'] = 'd';
            $return['type'] = 'directory';

        } elseif ($return['cdevice']) {
            // This file is a character device
            $return['mode'] = 'c';
            $return['type'] = 'character device';

        } elseif ($return['fifopipe']) {
            // This file is a FIFO pipe
            $return['mode'] = 'p';
            $return['type'] = 'fifo pipe';

        } else {
            // This file is an unknown type
            $return['mode'] = 'u';
            $return['type'] = 'unknown';
            $return['unknown'] = true;
        }

        $return['owner'] = [
            'r' => ($perms & 0x0100),
            'w' => ($perms & 0x0080),
            'x' => (($perms & 0x0040) and !($perms & 0x0800)),
            's' => (($perms & 0x0040) and ($perms & 0x0800)),
            'S' => ($perms & 0x0800)
        ];

        $return['group'] = [
            'r' => ($perms & 0x0020),
            'w' => ($perms & 0x0010),
            'x' => (($perms & 0x0008) and !($perms & 0x0400)),
            's' => (($perms & 0x0008) and ($perms & 0x0400)),
            'S' => ($perms & 0x0400)
        ];

        $return['other'] = [
            'r' => ($perms & 0x0004),
            'w' => ($perms & 0x0002),
            'x' => (($perms & 0x0001) and !($perms & 0x0200)),
            't' => (($perms & 0x0001) and ($perms & 0x0200)),
            'T' => ($perms & 0x0200)
        ];

        // Owner
        $return['mode'] .= (($perms & 0x0100) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0080) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x') :
            (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $return['mode'] .= (($perms & 0x0020) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0010) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x') :
            (($perms & 0x0400) ? 'S' : '-'));

        // Other
        $return['mode'] .= (($perms & 0x0004) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0002) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x') :
            (($perms & 0x0200) ? 'T' : '-'));

        return $return;
    }


    /**
     * Returns the mimetype data for the object file
     *
     * @return string The mimetype data for the object file
     * @version 2.4: Added documentation
     */
    public function getMimetype(): string
    {
        // TODO Make this an object property
        static $finfo = null;

        // Check filesystem restrictions
        $this->restrictions->check($this->file, false);

        try {
            if (is_dir($this->file)) {
                return 'directory';
            }

            if (!$finfo) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            }

            $mimetype = finfo_file($finfo, $this->file);
            return $mimetype;
        } catch (Exception $e) {
            // We failed to get mimetype data. Find out why and throw exception
            $this->checkReadable('', new FilesystemException(tr('Failed to get mimetype information for file ":file"', [
                ':file' => $this->file
            ]), previous: $e));
        }
    }


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
    public function secureDelete(string|bool $clean_path = true, bool $sudo = false): static
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        // Delete all specified patterns
        // Execute the rm command
        Process::new('find', $this->restrictions)
            ->setSudo($sudo)
            ->setTimeout(60)
            ->addArgument($this->file)
            ->addArgument('-exec')
            ->addArgument('shred')
            ->addArgument('--remove=wipe')
            ->addArgument('-f')
            ->addArgument('-n')
            ->addArgument('3')
            ->addArgument('-z')
            ->addArgument('{}')
            ->addArgument('\;')
            ->executeReturnArray();

        // If specified to do so, clear the path upwards from the specified pattern
        if ($clean_path) {
            if ($clean_path === true) {
                // This will clean path until a non-empty directory is encountered.
                $clean_path = null;
            }

            Path::new(dirname($this->file))->clear($clean_path, $sudo);
        }

        return $this;
    }


    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @param string|bool $clean_path If specified true, all directories above each specified pattern will be deleted as
     *                                well as long as they are empty. This way, no empty directories will be left lying
     *                                around
     * @param boolean $sudo If specified true, the rm command will be executed using sudo
     * @param bool $escape If true, will escape the filename. This may cause issues when using wildcards, for
     *                                example
     * @return static
     * @see Restrictions::check() This function uses file location restrictions
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        // Delete all specified patterns
        // Execute the rm command
        Process::new('rm', $this->restrictions)
            ->setSudo($sudo)
            ->setUseRunFile($use_run_file)
            ->setTimeout(10)
            ->addArgument($this->file, $escape)
            ->addArgument('-rf')
            ->executeNoReturn();

        // If specified to do so, clear the path upwards from the specified pattern
        if ($clean_path) {
            if ($clean_path === true) {
                // This will clean path until a non-empty directory is encountered.
                $clean_path = null;
            }

            Path::new(dirname($this->file), $this->restrictions->getParent())->clear($clean_path, $sudo, use_run_file: $use_run_file);
        }

        return $this;
    }


    /**
     * Moves this file to the specified target, will try to ensure target path exists
     *
     * @param Stringable|string $target
     * @param bool $ensure_path
     * @return $this
     */
    public function move(Stringable|string $target, bool $ensure_path = true): static
    {
        // Ensure target is absolute
        $target = Filesystem::absolute($target, must_exist: false);

        // Ensure the target directory exists
        if (file_exists($target)) {
            // Target exists. It has to be a directory where we can move into, or fail!
            if (!is_dir($target)) {
                throw FileExistsException::new(tr('The specified target ":target" already exists', [
                    ':target' => $target
                ]));
            }

            // Target exists and is directory. Rename target to "this file in the target directory"
            $target = Strings::slash($target) . basename($this->file);

        } else {
            // Target does not exist
            if (str_ends_with($target, '/')) {
                // If the target is indicated to be a directory (because it ends with a slash) then it should be created
                $create = $target;
                $target = Strings::slash($target) . basename($this->file);

            } elseif (!file_exists(dirname($target))) {
                // The target parent directory does not exist. It must be created or fail
                $create = dirname($target);
                $target = Strings::slash(dirname($target)) . basename($this->file);
            }

            if (isset($create)) {
                // Target directory does not exist can we create it?
                if (!$ensure_path) {
                    throw new PathNotExistsException(tr('The specified target parent directory does not exist', [
                        ':path' => $create
                    ]));
                }

                // Ensure the target directory exist
                Path::new(dirname($target), $this->restrictions)->ensure();
            }
        }

        // Check restrictions and execute move
        $this->restrictions->check($target, true);
        rename($this->file, $target);

        // Update this file to the new location, and done
        $this->file = $target;
        return $this;
    }


    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
     * @return string|int
     */
    public function switchMode(string|int $mode): string|int
    {
        $old_mode = $this->getMode();

        $this->chmod($mode);

        return $old_mode;
    }


    /**
     * Returns the file mode for the object file
     *
     * @return string|int|null
     */
    public function getMode(): string|int|null
    {
        return $this->getStat()['mode'];
    }


    /**
     * Returns the file type
     *
     * @return string|int|null
     */
    public function getType(): string|int|null
    {
        if (!$this->type) {
            $this->type = $this->getStat()['mode'] & 0170000;
        }

        return $this->type;
    }


    /**
     * Returns the stat data for the object file
     *
     * @return array
     */
    public function getStat(): array
    {
        if ($this->str)

        // Check filesystem restrictions
        $this->restrictions->check($this->file, false);

        try {
            $stat = stat($this->file);

            if ($stat) {
                return $stat;
            }

        } catch (Throwable $e) {
            $this->checkReadable(null, $e);
        }
    }


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
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false): static
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        if (!$user) {
            $user = posix_getpwuid(posix_getuid());
            $user = $user['name'];
        }

        if (!$group) {
            $group = posix_getpwuid(posix_getuid());
            $group = $group['name'];
        }

        foreach ($this->file as $pattern) {
            Process::new('chown', $this->restrictions)
                ->setSudo(true)
                ->addArgument($recursive ? '-R' : null)
                ->addArgument($user . ':' . $group)
                ->addArguments($this->file)
                ->executeReturnArray();
        }

        return $this;
    }


    /**
     * Change file mode, optionally recursively
     *
     * @param string|int $mode The mode to apply to the specified path (and all files below if recursive is specified)
     * @param boolean $recursive If set to true, apply specified mode to the specified path and all files below by
     *                           recursion
     * @param bool $sudo
     * @return static
     * @see $this->chown()
     */
    public function chmod(string|int $mode, bool $recursive = false, bool $sudo = false): static
    {
        if (!($mode)) {
            throw new OutOfBoundsException(tr('No file mode specified'));
        }

        if (!$this->file) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        if ($recursive) {
            Process::new('chmod', $this->restrictions)
                ->setSudo($sudo)
                ->addArguments(['-R', '0' . decoct($mode), $this->file])
                ->executeReturnArray();
        } else {
            chmod($this->file, $mode);
        }

        return $this;
    }


    /**
     * Ensure that the object file is readable
     *
     * This method will ensure that the object file will exist and is readable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return bool
     */
    public function ensureFileReadable(?int $mode = null): bool
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        // If the object file exists and is writable, then we're done.
        if (is_writable($this->file)) {
            return true;
        }

        // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...

        if (file_exists($this->file)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The file ":file" (Realpath ":path") is not readable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->file,
                    ':path' => $this->real_file,
                    ':mode' => $mode
                ]));

                $this->chmod('u+w');

            } catch (ProcessesException) {
                throw new FileNotWritableException(tr('The file ":file" (Realpath ":path") is not writable, and could not be made writable', [
                    ':file' => $this->file,
                    ':path' => $this->real_file
                ]));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
        Path::new(dirname($this->file), $this->restrictions)->ensure();

        Log::action(tr('Creating non existing file ":file" with file mode ":mode"', [
            ':mode' => Strings::fromOctal($mode),
            ':file' => $this->file
        ]));

        return false;
    }


    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return bool
     */
    public function ensureFileWritable(?int $mode = null): bool
    {
        // Check filesystem restrictions
        $this->restrictions->check($this->file, true);

        // If the object file exists and is writable, then we're done.
        if (is_writable($this->file)) {
            return true;
        }

        // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...

        if (file_exists($this->file)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The file ":file" (Realpath ":path") is not writable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->file,
                    ':path' => $this->real_file,
                    ':mode' => $mode
                ]));

                $this->chmod('u+w');

            } catch (ProcessesException) {
                throw new FileNotWritableException(tr('The file ":file" (Realpath ":path") is not writable, and could not be made writable', [
                    ':file' => $this->file,
                    ':path' => $this->real_file
                ]));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
        Path::new(dirname($this->file), $this->restrictions->getParent())->ensure();

        return false;
    }


    /**
     * Returns the size in bytes of this file or path
     *
     * @param bool $recursive
     * @return int
     */
    public function getSize(bool $recursive = true): int
    {
        if ($this instanceof FileInterface) {
            if ($this->exists()) {
                // This is a single file!
                return filesize($this->file);
            }

            return 0;
        }

        // Return the amount of all files in this directory
        $files = scandir($this->file);
        $size = 0;

        foreach ($files as $file) {
            if (($file === '.') or ($file === '..')) {
                // Skip crap
                continue;
            }

            // Filename must have complete absolute path
            $file = $this->file . $file;

            if (is_dir($file)) {
                if ($recursive) {
                    // Get file size of this entire directory
                    $size += Filesystem::get($file, $this->restrictions)->getSize($recursive);
                }
            } else {
                // Get file size of this file
                try {
                    $size += filesize($file);
                } catch (Throwable $e) {
                    if (file_exists($file)) {
                        throw $e;
                    }

                    // This is likely a dead soft symlink, we can simply ignore it.
                }
            }
        }

        return $size;
    }


    /**
     * Returns the parent directory for this file
     *
     * @param RestrictionsInterface $restrictions
     * @return PathInterface
     */
    public function getDirectory(RestrictionsInterface $restrictions): PathInterface
    {
        return Path::new(dirname($this->file), $restrictions);
    }


    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param EnumFileOpenModeInterface $mode
     * @param resource $context
     * @return static
     */
    public function open(EnumFileOpenModeInterface $mode, $context = null): static
    {
        // Check filesystem restrictions and open the file
        $this
            ->ensureClosed('open')
            ->restrictions
                ->check($this->file, ($mode !== EnumFileOpenMode::readOnly));

        try {
            $stream = fopen($this->file, $mode->value, false, $context);

        } catch (Throwable $e) {
            // Failed to open the target file
            $this->checkReadable('target', $e);
        }

        if ($stream) {
            // All okay!
            $this->stream = $stream;
            $this->open_mode = $mode;
            return $this;
        }

        // File couldn't be opened. check if file is accessible.
        switch ($mode) {
            case EnumFileOpenMode::readOnly:
                $this->checkReadable();
                break;

            default:
                $this->checkWritable();
                break;
        }

        throw new FilesystemException(tr('Failed to open file ":file"', [':file' => $this->file]));
    }


    /**
     * Returns true if the file is a symlink, whether its target exists or not
     *
     * @return bool
     */
    public function isLink(): bool
    {
        $link = linkinfo($this->file);

        if (!$link) {
            return false;
        }

        // Whether the target exists or not, this IS a link
        return true;
    }


    /**
     * Returns true if the file is a symlink AND its target exists
     *
     * @return bool
     */
    public function isLinkAndTargetExists(): bool
    {
        return is_link($this->file);
    }


    /**
     * Returns true if the file is a directory
     *
     * @return bool
     */
    public function isDir(): bool
    {
        return is_dir($this->file);
    }


    /**
     * Returns true if this file is a FIFO
     *
     * @return bool
     */
    public function isFifo(): bool
    {
        if (!$this->type) {
            $this->getType();
        }

        return $this->type == 0010000; // S_IFIFO
    }


    /**
     * Returns true if this file is a Character device
     *
     * @return bool
     */
    public function isChr(): bool
    {
        if (!$this->type) {
            $this->getType();
        }

        return $this->type == 0020000; // S_IFCHR
    }


    /**
     * Returns true if this file is a block device
     *
     * @return bool
     */
    public function isBlk(): bool
    {
        if (!$this->type) {
            $this->getType();
        }

        return $this->type == 0060000; // S_IFBLK
    }


    /**
     * Returns true if this file is ???
     *
     * @return bool
     */
    public function isReg(): bool
    {
        if (!$this->type) {
            $this->getType();
        }

        return $this->type == 0100000; // S_IFREG
    }


    /**
     * Returns true if this file is a socket device
     *
     * @return bool
     */
    public function isSock(): bool
    {
        if (!$this->type) {
            $this->getType();
        }

        return $this->type == 0140000; // S_IFSOCK
    }


    /**
     * Returns true if the file is opened
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return $this->stream !== null;
    }


//    /**
//     * Will create a symbolic link to the specified target
//     *
//     * @param string $target
//     * @return static
//     */
//    public function symlink(string $target): static
//    {
//        symlink($this->file, $target);
//        return $this;
//    }


    /**
     * Creates a symlink $target that points to this file.
     *
     * @param Stringable|string $source
     * @param Restrictions|null $restrictions
     * @return $this
     */
    public function symlink(Stringable|string $source, ?Restrictions $restrictions = null): static
    {
        $source = (string)$source;

        if (file_exists($source)) {
            if (readlink($source) === $this->file) {
                // Symlink already exists and points to the same file, all fine
                return $this;
            }

            throw new FileExistsException(tr('Cannot create symlink ":target" that points to ":source", the file already exists and points to ":current" instead', [
                ':target' => $source,
                ':source' => $this->file,
                ':current' => readlink($source)
            ]));
        }

        // Ensure target is absolute
        $source = Filesystem::absolute($source, must_exist: false);

        // Ensure that we have restrictions access and target path exists
        Restrictions::default($restrictions, $this->restrictions)->check($source, true);
        Path::new(dirname($source), $this->restrictions->getParent())->ensure();

        // Symlink
        symlink($this->file, $source);
        return $this;
    }


//    /**
//     * Will create a hard link to the specified target
//     *
//     * @note The target may NOT cross filesystem boundaries (that is, source is on one filesystem, target on another).
//     *       If this is required, use File::symlink() instead. This is not a limitation of Phoundation, but of
//     *       filesystems in general. See
//     * @param string $target
//     * @return static
//     */
//    public function link(string $target): static
//    {
//        link($this->file, $target);
//        return $this;
//    }


    /**
     * Returns true if the file pointer is at EOF
     *
     * @return bool
     */
    public function isEof(): bool
    {
        $this->ensureOpen('getEof');
        return feof($this->stream);
    }


    /**
     * Returns how the file was opened, NULL if the file is not open
     *
     * @return EnumFileOpenModeInterface|null
     */
    public function getOpenMode(): ?EnumFileOpenModeInterface
    {
        return $this->open_mode;
    }


    /**
     * Sets the internal file pointer to the specified offset
     *
     * @param int $offset
     * @param int $whence
     * @return static
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function seek(int $offset, int $whence = SEEK_SET): static
    {
        $this->ensureOpen('seek');

        $result = fseek($this->stream, $offset, $whence);

        if ($result) {
            // The file seek failed
            if (empty(stream_get_meta_data($this->stream)['seekable'])) {
                // File mode is not seekable
                throw new FileActionFailedException(tr('Failed to seek in file ":file" because file mode ":mode" does not allow seek', [
                    ':mode' => $this->open_mode->value,
                    ':file' => $this->file
                ]));
            }

            // No idea why
            throw new FileActionFailedException(tr('Failed to seek in file ":file"', [
                ':file' => $this->file
            ]));

        }

        return $this;
    }


    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function tell(): int
    {
        $this->ensureOpen('tell');

        $result = ftell($this->stream);

        if ($result === false) {
            // ftell() failed
            throw new FileActionFailedException(tr('Failed to tell file pointer for file ":file"', [
                ':file' => $this->file
            ]));

        }

        return $result;
    }


    /**
     * Rewinds the position of the file pointer
     *
     * @return static
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function rewind(): static
    {
        $this->ensureOpen('rewind');

        $result = rewind($this->stream);

        if ($result === false) {
            // ftell() failed
            throw new FileActionFailedException(tr('Failed to rewind file ":file"', [
                ':file' => $this->file
            ]));

        }

        return $this;
    }


    /**
     * Reads and returns the specified amount of bytes from the current pointer location
     *
     * @param int|null $buffer
     * @param int|null $seek
     * @return string|false
     */
    public function read(?int $buffer = null, ?int $seek = null): string|false
    {
        $this->ensureOpen('read');

        if ($seek) {
            $this->seek($seek);
        }

        $buffer = $this->getBufferSize($buffer);
        $data = fread($this->stream, $buffer);

        if ($data === false) {
            return $this->processReadFailure('data', $data);
        }

        return $data;
    }


    /**
     * Reads and returns the next text line in this file
     *
     * @param int|null $buffer
     * @return string|false
     */
    public function readLine(?int $buffer = null): string|false
    {
        $this->ensureOpen('read');

        if (!$buffer) {
            $buffer = $this->getBufferSize();
        }

        $data = fgets($this->stream, $buffer);

        if ($data === false) {
            return $this->processReadFailure('line', $data);
        }

        return $data;
    }


    /**
     * Reads line from file pointer and parse for CSV fields
     *
     * @param int|null $max_length
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @return array|false
     */
    public function readCsv(?int $max_length = null, string $separator = ",", string $enclosure = "\"", string $escape = "\\"): array|false
    {
        $this->ensureOpen('read');

        $data = fgetcsv($this->stream, $max_length, $separator, $enclosure, $escape);

        if ($data === false) {
            return $this->processReadFailure('CSV', $data);
        }

        return $data;
    }


    /**
     * Reads and returns a single character from the current file pointer
     *
     * @return string|false
     */
    public function readCharacter(): string|false
    {
        $this->ensureOpen('read');

        $data = fgetc($this->stream);

        if ($data === false) {
            return $this->processReadFailure('character', $data);
        }

        return $data;
    }


    /**
     * Reads and returns the specified amount of bytes at the specified location from this CLOSED file
     *
     * @note Will throw an exception if the file is already open
     * @param int $length
     * @param int $start
     * @return string
     */
    public function readBytes(int $length, int $start = 0): string
    {
        $data = $this
            ->ensureClosed('readBytes')
            ->open(EnumFileOpenMode::readOnly)
            ->read($start + $length);

        $data = substr($data, $start);
        $this->close();

        return $data;
    }


    /**
     * Write the specified data to this file with the requested file mode
     *
     * @param string $data
     * @param EnumFileOpenModeInterface $write_mode
     * @return $this
     */
    protected function save(string $data, EnumFileOpenModeInterface $write_mode = EnumFileOpenMode::writeOnly): static
    {
        $this->restrictions->check($this->file, true);
        $this->ensureWriteMode($write_mode);

        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        Path::new(dirname($this->file), $this->restrictions->getParent()->getParent())->ensure();
        return $this->open($write_mode)->write($data)->close();
    }


    /**
     * Binary-safe write the specified data to this file
     *
     * @param string $data
     * @param int|null $length
     * @return $this
     */
    public function write(string $data, ?int $length = null): static
    {
        $this->ensureOpen('write');

        fwrite($this->stream, $data, $length);

        return $this;
    }


    /**
     * Write the specified data to this
     *
     * @param bool $use_include_path
     * @param resource|null $context
     * @param int $offset
     * @param int|null $length
     * @return $this
     */
    protected function getContents(bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string
    {
        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->ensureClosed('getContents');

        $data = file_get_contents($this->file, $use_include_path, $context, $offset, $length);

        if ($data === false) {
            return $this->processReadFailure('contents', $data, false);
        }

        return $data;
    }


    /**
     * Write the specified data to this
     *
     * @param string $data
     * @param int $flags
     * @param null $context
     * @return $this
     */
    protected function putContents(string $data, int $flags = 0, $context = null): static
    {
        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->ensureClosed('putContents');
        Path::new(dirname($this->file), $this->restrictions->getParent()->getParent())->ensure();

        file_put_contents($this->file, $data, $flags, $context);

        return $this;
    }


    /**
     * Append specified data string to the end of the object file
     *
     * @param string $data
     * @param int|null $length
     * @return static
     */
    public function append(string $data, ?int $length = null): static
    {
        if ($this->isOpen()) {
            return $this->write($data, $length);
        }

        return $this->open(EnumFileOpenMode::writeOnlyAppend)->write($data)->close();
    }


    /**
     * Create the specified file
     *
     * @param string $data
     * @param bool $force
     * @return static
     */
    public function create(string $data, bool $force = false): static
    {
        if ($this->exists()) {
            if (!$force) {
                throw new FileExistsException(tr('Cannot create file ":file", it already exists', [
                    ':file' => $this->file
                ]));
            }
        }

        if ($this->isOpen()) {
            // Yeah, so it exists anyway because we have it open. Perhaps the file was removed while open, so the inode
            // is still there?
            if (!$force) {
                throw new FileExistsException(tr('Cannot create file ":file", it does not exist, but is open. Perhaps the file was deleted but the open inode is still there?', [
                    ':file' => $this->file
                ]));
            }

            $this->close();
        }

        return $this->open(EnumFileOpenMode::writeOnlyCreateOnly)->write($data)->close();
    }


    /**
     * Concatenates a list of files to a target file
     *
     * @param string|array $sources The source files
     * @return static
     */
    public function appendFiles(string|array $sources): static
    {
        // Check filesystem restrictions
        $this
            ->ensureClosed('appendFiles')
            ->restrictions->check($this->file, true);

        // Ensure the target path exists
        Path::new(dirname($this->file), $this->restrictions)->ensure();

        // Open target file
        $this->open(EnumFileOpenMode::writeOnlyAppend);

        // Open each source file
        foreach (Arrays::force($sources, null) as $source) {
            try {
                $source = File::new($source, $this->restrictions)->open(EnumFileOpenMode::readOnly);

                while (!$source->isEof()) {
                    $this->write($source->read(1048576));
                }

                $source->close();

            } catch (Throwable $e) {
                // Failed to open one of the sources, get rid of the partial target file
                $this->close()->delete();
                $source->checkReadable('source', $e);
            }
        }

        return $this;
    }


    /**
     * Closes this file
     *
     * @param bool $force
     * @return static
     */
    public function close(bool $force = false): static
    {
        if (!$this->stream) {
            if ($force) {
                throw new FileNotOpenException(tr('The file ":file" cannot be closed, it is not open', [
                    ':file' => $this->file
                ]));
            }
        }

        fclose($this->stream);

        $this->stream = null;
        $this->open_mode = null;

        return $this;
    }


    /**
     * Synchronizes changes to the file (including meta-data)
     *
     * @return $this
     */
    public function sync(): static
    {
        $this->ensureOpen('sync');

        if (!fsync($this->stream)) {
            throw new FileSyncException(tr('Failed to sync file ":file"', [
                ':file' => $this->file
            ]));
        }

        return $this;
    }


    /**
     * Synchronizes data (but not meta-data) to the file
     *
     * @return $this
     */
    public function syncData(): static
    {
        $this->ensureOpen('syncData');

        if (!fdatasync($this->stream)) {
            throw new FileSyncException(tr('Failed to data sync file ":file"', [
                ':file' => $this->file
            ]));
        }

        return $this;
    }


    /**
     * Throws an exception if the file is not closed
     *
     * @param string $method
     * @return $this
     * @throws FileOpenException
     */
    protected function ensureClosed(string $method): static
    {
        if ($this->isOpen()) {
            throw new FileOpenException(tr('Cannot execute method ":method()" on file ":file", it is already open', [
                ':file'   => $this->file,
                ':method' => $method
            ]));
        }

        return $this;
    }


    /**
     * Throws an exception if the file is not open
     *
     * @param string $method
     * @param EnumFileOpenModeInterface|null $mode
     * @return $this
     */
    protected function ensureOpen(string $method, ?EnumFileOpenModeInterface $mode = null): static
    {
        if (!$this->isOpen()) {
            throw new FileOpenException(tr('Cannot execute method ":method()" on file ":file", it is closed', [
                ':file'   => $this->file,
                ':method' => $method
            ]));
        }

        if ($mode) {
            return $this->ensureWriteMode($this->open_mode);
        }

        return $this;
    }


    /**
     * Ensure that the specified mode allows writing
     *
     * @param EnumFileOpenModeInterface $mode
     * @return $this
     */
    protected function ensureWriteMode(EnumFileOpenModeInterface $mode): static
    {
        switch ($mode) {
            case EnumFileOpenMode::readOnly:
                throw new ReadOnlyModeException(tr('Cannot write to file ":file", the file is opened in readonly mode', [
                    ':file' => $this->file
                ]));
        }

        return $this;
    }


    /**
     * Determines what exception to throw for a read failure
     *
     * @param string $type
     * @param string|false|null $data
     * @param bool $test_feof If false will skip FEOF test
     * @return string|false|null
     */
    protected function processReadFailure(string $type, string|false|null $data, bool $test_feof = true): string|false|null
    {
        // FEOF errors are only checked if we didn't try to read full file contents
        if ($test_feof and $this->isEof()) {
            return $data;
        }

        throw new FileReadException(tr('Cannot read :type from file ":file", the file pointer is at the end of the file', [
            ':type' => $type,
            ':file' => $this->file
        ]));
    }
}
