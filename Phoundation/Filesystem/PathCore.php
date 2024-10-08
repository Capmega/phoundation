<?php

/**
 * Class PathCore
 *
 * This library contains the basic functionalities to manage filesystem paths
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Cache\InstanceCache;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhpException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Commands\Df;
use Phoundation\Filesystem\Enums\EnumFileOpenMode;
use Phoundation\Filesystem\Exception\FileActionFailedException;
use Phoundation\Filesystem\Exception\FileExistsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FileNotOpenException;
use Phoundation\Filesystem\Exception\FileNotReadableException;
use Phoundation\Filesystem\Exception\FileNotSymlinkException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FileOpenException;
use Phoundation\Filesystem\Exception\FileReadException;
use Phoundation\Filesystem\Exception\FileRenameException;
use Phoundation\Filesystem\Exception\FileSyncException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\FileTruncateException;
use Phoundation\Filesystem\Exception\MountLocationNotFoundException;
use Phoundation\Filesystem\Exception\NotASymlinkException;
use Phoundation\Filesystem\Exception\ReadOnlyModeException;
use Phoundation\Filesystem\Exception\SymlinkBrokenException;
use Phoundation\Filesystem\Interfaces\DirectoryInterface;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\FilesystemInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Mounts\Mount;
use Phoundation\Filesystem\Mounts\Mounts;
use Phoundation\Filesystem\Requirements\Interfaces\RequirementsInterface;
use Phoundation\Filesystem\Requirements\Requirements;
use Phoundation\Filesystem\Traits\TraitDataBufferSize;
use Phoundation\Filesystem\Traits\TraitDataIsRelative;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Traits\TraitDataServer;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;
class PathCore implements Stringable, PathInterface
{
    use TraitDataRestrictions;
    use TraitDataBufferSize;
    use TraitDataIsRelative;
    use TraitDataServer;

    const DIRECTORY_SEPARATOR = '/';


    /**
     * The target file name in case operations creates copies of this file
     *
     * @var string|null $target
     */
    protected ?string $target = null;

    /**
     * The file for this object
     *
     * @var string|null $path
     */
    protected ?string $path = null;

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
     * @var EnumFileOpenMode|null $open_mode
     */
    protected ?EnumFileOpenMode $open_mode = null;

    /**
     * The path requirements system
     *
     * @var RequirementsInterface $requirements
     */
    protected RequirementsInterface $requirements;

    /**
     * Files under this path. If the current path is a file, this Iterator will contain only one entry, THIS file.
     *
     * @var FilesInterface $files
     */
    protected FilesInterface $files;

    /**
     * Cache for the mime type of this file
     *
     * @var string $mime
     */
    protected string $mime;

    /**
     * Tracks if any file access at all is enabled
     *
     * @var bool $read_enabled
     */
    protected static bool $read_enabled = true;

    /**
     * Tracks if any file access at all is enabled
     *
     * @var bool $write_enabled
     */
    protected static bool $write_enabled = true;


    /**
     * Returns a new File or Directory object with the specified restrictions
     *
     * @param mixed                                   $path
     * @param RestrictionsInterface|array|string|null $restrictions
     *
     * @return PathInterface
     * @throws FileNotExistException
     */
    public static function newExisting(mixed $path = null, RestrictionsInterface|array|string|null $restrictions = null): PathInterface
    {
        if (is_dir($path)) {
            return Directory::new($path, $restrictions);
        }

        if (file_exists($path)) {
            return File::new($path, $restrictions);
        }

        throw new FileNotExistException(tr('The specified path ":path" does not exist', [
            ':path' => $path,
        ]));
    }


    /**
     * Returns the number of directories counted in the specified path
     *
     * @param mixed $path
     *
     * @return int
     */
    public static function countDirectories(mixed $path): int
    {
        // Remove any file that might contain / in the path name
        $path  = str_replace('\\/', '_', $path);
        $count = substr_count($path, '/');

        if (!$count and $path) {
            return 1;
        }

        return $count;
    }


    /**
     * Returns a version of the specified path that does not yet exist
     *
     * @param PathInterface|string $path
     * @param string|null          $extension
     *
     * @return PathInterface
     */
    public static function getAvailableVersion(PathInterface|string $path, ?string $extension = null): PathInterface
    {
        $prefix    = '';
        $version   = 97;
        $extension = Strings::ensureStartsWith($extension, '.');
        $path      = Path::new($path)->appendPath($extension);

        $path->getParentDirectory()->ensure();

        while ($path->exists()) {
            if (++$version >= 123) {
                $prefix .= 'z';
                $version = 97;

                if (strlen($prefix) > 3) {
                    // WTF? Seriously? 26^3 versions available? Something is funky here...
                    throw new OutOfBoundsException(tr('Failed to find available version for file ":path"', [
                        ':path' => $path,
                    ]));
                }
            }

            $path->setPath(Strings::untilReverse($path->getPath(), $extension) . $prefix . chr($version));
        }

        return $path;
    }


    /**
     * Returns a PathInterface object with the specified path appended to this path
     *
     * @param PathInterface|string $path
     * @param bool                 $make_absolute
     *
     * @return FileInterface
     */
    public function appendPath(PathInterface|string $path, bool $make_absolute = false): PathInterface
    {
        $path = $this->getPath() . Strings::ensureStartsNotWith((string) $path, '/');

        return Path::new($path, $this->restrictions, $make_absolute);
    }


    /**
     * Path class toString method
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getPath();
    }


    /**
     * Returns the path
     *
     * @param string|null $from
     *
     * @return string|null
     */
    public function getPath(?string $from = null): ?string
    {
        if ($this->isDir()) {
            $return = Strings::slash($this->path);

        } else {
            $return = $this->path;
        }

        return match ($from) {
            null       => $return,
            'commands' => Strings::from($return, DIRECTORY_COMMANDS),
            'data'     => Strings::from($return, DIRECTORY_DATA),
            'root'     => Strings::from($return, DIRECTORY_ROOT),
            'web'      => Strings::from($return, DIRECTORY_WEB),
            default    => Strings::from($return, $from),
        };
    }


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
    public function setPath(Stringable|string|null $path, string $prefix = null, bool $must_exist = false, bool $make_absolute = false): static
    {
        if ($this->isOpen()) {
            $this->close();
        }

        if ($make_absolute) {
            // Ensure absolute paths are absolute
            $this->path = static::absolutePath($path, $prefix, $must_exist);

        } else {
            // Realpath does not make sense with relative paths that may not even exist
            $this->path = (string) $path;
        }

        return $this;
    }


    /**
     * Returns true if the file is a directory
     *
     * @return bool
     */
    public function isDir(): bool
    {
        return is_dir($this->path);
    }


    /**
     * Make this (relative) path an absolute path
     *
     * @param string|null $prefix
     * @param bool        $must_exist
     *
     * @return $this
     */
    public function makeAbsolute(?string $prefix = null, bool $must_exist = true): static
    {
        $this->path = static::absolutePath($this->path, $prefix, $must_exist);

        return $this;
    }


    /**
     * Returns the absolute version of this path
     *
     * @param string|null $prefix
     * @param bool        $must_exist
     *
     * @return string|null
     */
    public function getAbsolutePath(?string $prefix = null, bool $must_exist = true): ?string
    {
        return static::absolutePath($this->path, $prefix, $must_exist);
    }


    /**
     * Returns a new Directory object with the specified restrictions starting from the specified path, applying a
     * number of defaults
     *
     * . Is DIRECTORY_ROOT
     * ~ is the current shell's user home directory
     *
     * @param Stringable|string|null      $path
     * @param Stringable|string|bool|null $prefix
     * @param bool                        $must_exist
     *
     * @return static
     */
    public static function absolutePath(Stringable|string|null $path = null, Stringable|string|bool|null $prefix = null, bool $must_exist = true): string
    {
        $path = trim((string) $path);

        if (InstanceCache::exists('path::absolutePath', $path)) {
            return InstanceCache::getLastChecked();
        }

        $path = str_replace('//', '/', $path);

        if ($prefix === false) {
            // Don't make it absolute at all
            return $path;
        }

        if (!$path) {
            // No path specified? Use the project root directory
            return DIRECTORY_ROOT;
        }

        if ($prefix === true) {
            // Prefix true is considered the same as prefix null
            $prefix = null;
        }

        // Validate the specified path, it must be an actual path
        static::validateFilename($path);

        switch ($path[0]) {
            case '/':
                // This is already an absolute directory
                $return = static::normalizePath($path);
                break;

            case '~':
                // This starts at the process users home directory
                if (empty($_SERVER['HOME'])) {
                    throw new OutOfBoundsException(tr('Cannot use "~" paths, cannot determine this users home directory'));
                }

                $return = Strings::slash($_SERVER['HOME'], '/') . Strings::ensureStartsNotWith(substr($path, 1), '/');
                break;

            case '.':
                if (str_starts_with($path, './')) {
                    // This is the CWD (Take from DIRECTORY_START as getcwd() output might change during processing)
                    $return = DIRECTORY_START . substr($path, 2);
                    break;
                }

            // no break
            default:
                // This is not an absolute directory, make it an absolute directory
                $prefix = trim((string) $prefix);
                switch ($prefix) {
                    case '':
                        $prefix = DIRECTORY_ROOT;
                        break;

                    case 'css':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/css/';
                        break;

                    case 'js':
                        // no break

                    case 'javascript':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/js/';
                        break;

                    case 'img':
                        // no break

                    case 'image':
                        // no break

                    case 'images':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/img/';
                        break;

                    case 'font':
                        // no break

                    case 'fonts':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/fonts/';
                        break;

                    case 'video':
                        // no break

                    case 'videos':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/video/';
                        break;
                }

                // Prefix $path with $prefix
                $return = Strings::slash($prefix) . Strings::unslash($path);
        }

        // If this is a directory, make sure it has a slash suffix
        if (file_exists($return)) {
            if (is_dir($return)) {
                $return = Strings::slash($return);
            }

        } else {
            if ($must_exist) {
                throw FileNotExistException::new(tr('The resolved path ":resolved" for the specified path ":directory" with prefix ":prefix" does not exist', [
                    ':prefix'    => $prefix,
                    ':directory' => $path,
                    ':resolved'  => $return,
                ]))->addData([
                   'path' => $return,
                ]);
            }

            // The path doesn't exist, but apparently that's okay! Continue!
        }

        return InstanceCache::set($return, 'path::absolutePath', $path);
    }


    /**
     * Ensures that the object file name is valid
     *
     * @param string|null $file
     *
     * @return void
     */
    public static function validateFilename(?string $file = null): void
    {
        if ($file === null) {
            return;
        }

        $file = trim($file);

        if (!$file) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        if (strlen($file) > 4096) {
            throw new OutOfBoundsException(tr('The object filename is too large with ":size" bytes', [
                ':size' => strlen($file),
            ]));
        }
    }


    /**
     * Returns the extension of the objects path
     *
     * @return string
     */
    public function getExtension(): string
    {
        return Strings::fromReverse($this->path, '.');
    }


    /**
     * Returns true if this Path object has the specified extension
     *
     * @param string $extension
     *
     * @return bool
     */
    public function hasExtension(string $extension): bool
    {
        return str_ends_with($this->path, '.' . Strings::ensureStartsNotWith($extension, '.'));
    }


    /**
     * Returns the basename of this path
     *
     * @return string
     */
    public function getBasename(): string
    {
        return basename($this->path);
    }


    /**
     * Returns the stream for this file if it's opened. Will return NULL if closed
     *
     * @return mixed
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }


    /**
     * Returns true if this object is the specified path
     *
     * @param string $path
     *
     * @return bool
     */
    public function isPath(string $path): bool
    {
        return Strings::ensureEndsNotWith($this->path, '/') === Strings::ensureEndsNotWith($path, '/');
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
            return $this->path;
        }

        return $this->target;
    }


    /**
     * Sets the target file name in case operations create copies of this file
     *
     * @param Stringable|string $target
     *
     * @return static
     */
    public function setTarget(Stringable|string $target): static
    {
        $this->target = PathCore::absolutePath($target, null, false);

        return $this;
    }


    /**
     * Checks if the specified file does not exist, throws exception if it does
     *
     * @param bool $force
     * @param bool $check_dead_symlink
     * @param bool $auto_mount
     *
     * @return static
     */
    public function checkNotExists(bool $force = false, bool $check_dead_symlink = false, bool $auto_mount = true): static
    {
        if ($this->exists($check_dead_symlink, $auto_mount)) {
            if (!$force) {
                throw new FileExistsException(tr('Specified file ":file" already exist', [
                    ':file' => $this->path,
                ]));
            }

            // Delete the file
            $this->delete();
        }

        return $this;
    }


    /**
     * Checks if the specified file exists
     *
     * @param bool $auto_mount
     * @param bool $check_dead_symlink
     *
     * @return bool
     */
    public function exists(bool $check_dead_symlink = false, bool $auto_mount = true): bool
    {
        if (file_exists($this->path)) {
            return true;
        }

        // Oh noes! This path doesn't exist!
        // Maybe the basename of the path is a dead symlink?
        if ($check_dead_symlink) {
            if ($this->isLink()) {
                // The basename of this path DOES exist as a dead symlink
                return true;
            }

            // Nope, the path basename really does not exist!
        }

        // Maybe a section of the path isn't mounted?
        if ($auto_mount) {
            if ($this->attemptAutoMount()) {
                // The path was auto mounted, so try again!
                return $this->exists($check_dead_symlink, false);
            }
        }

        return false;
    }


    /**
     * Returns true if the file is a symlink, whether its target exists or not
     *
     * @return bool
     */
    public function isLink(): bool
    {
        return is_link(Strings::ensureEndsNotWith($this->path, '/'));
    }


    /**
     * Follows the symlink and updates this object's path to be the target of the symlink
     *
     * @param bool $force
     * @param bool $all
     *
     * @return $this
     */
    public function followLink(bool $force = false, bool $all = false): static
    {
        if ($this->isLink()) {
            if (!$this->isLinkAndTargetExists()) {
                throw new SymlinkBrokenException(tr('Cannot follow symlink ":path", the target does not exist', [
                    ':path' => $this->path,
                ]));
            }

            $this->path = static::absolutePath($this->getLinkTarget(true)->getPath());

            if ($this->isLink() and $all) {
                // The link target is a link too, and with $all set, we keep following!
                return $this->followLink($force, $all);
            }

        } else {
            if (!$force) {
                throw new NotASymlinkException(tr('Cannot follow file ":path", the file is not a symlink', [
                    ':path' => $this->path,
                ]));
            }
        }

        return $this;
    }


    /**
     * Ensures that the path is completely mounted and executes the callback if a mount was made
     *
     * @return bool
     * @todo Add support for recursive auto mounting
     */
    public function attemptAutoMount(): bool
    {
        if (Config::getBoolean('filesystem.automounts.enabled', false)) {
            return false;
        }

        try {
            // Check if this path has a mount somewhere. If so, see if it needs auto-mounting
            $mount = Mount::getForPath($this->path, $this->restrictions->getWritable());

            if ($mount) {
                if ($mount->autoMount()) {
                    return true;
                }
            }

        } catch (SqlException $e) {
            if (!Core::inInitState()) {
                Log::warning(tr('Failed to search for filesystem mounts in database because ":e", ignoring these possible mount requirements', [
                    ':e' => $e->getMessage(),
                ]), 3);
            }
        }

        return false;
    }


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
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static
    {
        Log::action(tr('Deleting file ":file"', [':file' => $this->path]), 2);

        // Check filesystem restrictions
        $this->checkRestrictions(true)->checkWriteAccess();

        // Delete all specified patterns
        // Execute the rm command
        Process::new('rm', $this->restrictions)
               ->setSudo($sudo)
               ->setUseRunFile($use_run_file)
               ->setTimeout(10)
               ->addArgument($this->path, $escape)
               ->addArgument('-rf')
               ->executeNoReturn();

        // If specified to do so, clear the path upwards from the specified pattern
        if ($clean_path) {
            if ($clean_path === true) {
                // This will clean path until a non-empty directory is encountered.
                $clean_path = null;
            }

            Directory::new(dirname($this->path), $this->restrictions->getParent())
                     ->clearDirectory($clean_path, $sudo, use_run_file: $use_run_file);
        }

        return $this;
    }


    /**
     * Checks restrictions
     *
     * @param bool $write
     *
     * @return $this
     */
    public function checkRestrictions(bool $write): static
    {
        if ($this->isRelative()) {
            // TODO Find a way to check restrictions anyway
            Log::warning(tr('Not checking restrictions for ":path" as it is a relative path with unknown directory prefix', [
                ':path' => $this->path,
            ]), 4);
            showbacktrace();
            showdie($this->path);

        } else {
            $this->restrictions->check($this->path, $write);

            if ($write) {
                return $this->checkWriteAccess();
            }

            return $this->checkReadAccess();
        }

        return $this;
    }


    /**
     * Returns true if the path for this Path object is relative (and as such, starts NOT with /)
     *
     * @return bool
     */
    public function isRelative(): bool
    {
        return !str_starts_with($this->path, '/');
    }


    /**
     * Truncates a file to a given length
     *
     * @param int $size
     *
     * @return $this
     */
    public function truncate(int $size): static
    {
        $result = ftruncate($this->stream, $size);

        if (!$result) {
            throw new FileTruncateException(tr('Failed to truncate file ":file" to ":size" bytes', [
                ':file' => $this->path,
                ':size' => $size,
            ]));
        }

        return $this;
    }


    /**
     * Output all remaining data on a file pointer to the output buffer
     *
     * @return int The number of bytes
     */
    public function fpassthru(): int
    {
        $size = fpassthru($this->stream);

        return $size;
    }


    /**
     * Returns true if this path can be read
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        // Check filesystem restrictions
        $this->checkRestrictions(false);

        return is_readable($this->path) and static::$read_enabled;
    }


    /**
     * Returns true if this path can be written
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        // Check filesystem restrictions
        $this->checkRestrictions(false);

        return is_writable($this->path) and static::$write_enabled;
    }


    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return string
     */
    public function getHumanReadableFileType(): string
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true);
        $this->exists();

        $perms     = fileperms($this->path);
        $socket    = (($perms & 0xC000) == 0xC000);
        $symlink   = (($perms & 0xA000) == 0xA000);
        $regular   = (($perms & 0x8000) == 0x8000);
        $bdevice   = (($perms & 0x6000) == 0x6000);
        $cdevice   = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe  = (($perms & 0x1000) == 0x1000);

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
        $this->checkRestrictions(false);
        $this->exists();
        $perms  = fileperms($this->path);
        $return = [];
        $return['socket']    = (($perms & 0xC000) == 0xC000);
        $return['symlink']   = (($perms & 0xA000) == 0xA000);
        $return['regular']   = (($perms & 0x8000) == 0x8000);
        $return['bdevice']   = (($perms & 0x6000) == 0x6000);
        $return['cdevice']   = (($perms & 0x2000) == 0x2000);
        $return['directory'] = (($perms & 0x4000) == 0x4000);
        $return['fifopipe']  = (($perms & 0x1000) == 0x1000);
        $return['perms']     = $perms;
        $return['unknown']   = false;
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
            $return['mode']    = 'u';
            $return['type']    = 'unknown';
            $return['unknown'] = true;
        }
        $return['owner'] = [
            'r' => ($perms & 0x0100),
            'w' => ($perms & 0x0080),
            'x' => (($perms & 0x0040) and !($perms & 0x0800)),
            's' => (($perms & 0x0040) and ($perms & 0x0800)),
            'S' => ($perms & 0x0800),
        ];
        $return['group'] = [
            'r' => ($perms & 0x0020),
            'w' => ($perms & 0x0010),
            'x' => (($perms & 0x0008) and !($perms & 0x0400)),
            's' => (($perms & 0x0008) and ($perms & 0x0400)),
            'S' => ($perms & 0x0400),
        ];
        $return['other'] = [
            'r' => ($perms & 0x0004),
            'w' => ($perms & 0x0002),
            'x' => (($perms & 0x0001) and !($perms & 0x0200)),
            't' => (($perms & 0x0001) and ($perms & 0x0200)),
            'T' => ($perms & 0x0200),
        ];
        // Owner
        $return['mode'] .= (($perms & 0x0100) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0080) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
        // Group
        $return['mode'] .= (($perms & 0x0020) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0010) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
        // Other
        $return['mode'] .= (($perms & 0x0004) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0002) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

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
        // Check filesystem restrictions
        $this->checkRestrictions(false);
        if (empty($this->mime)) {
            if (is_dir($this->path)) {
                $mime = 'directory/directory';

            } else {
                try {
                    $r          = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                    $this->mime = finfo_file($r, $this->path);
                    finfo_close($r);

                } catch (Exception $e) {
                    // We failed to get mimetype data. Find out why and throw exception
                    $this->checkReadable('', new FilesystemException(tr('Failed to get mimetype information for file ":file"', [
                        ':file' => $this->path,
                    ]), previous: $e));
                    // static::checkReadable() will have thrown an exception, but throw this anyway just to be sure
                    throw $e;
                }
            }
        }

        return $this->mime;
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
     * @param string|null    $type          This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e    If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     *
     * @return static
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static
    {
        // Check filesystem restrictions
        $this->checkRestrictions(false);

        if (!$this->exists()) {
            if (!file_exists(dirname($this->path))) {
                // The file doesn't exist and neither does its parent directory
                throw new FileNotExistException(tr('The ":type" type file ":file" cannot be read because the directory ":directory" does not exist', [
                    ':type'      => ($type ?: ''),
                    ':file'      => $this->path,
                    ':directory' => dirname($this->path),
                ]), $previous_e);
            }

            throw new FileNotExistException(tr('The ":type" type file ":file" cannot be read because it does not exist', [
                ':type' => ($type ? ' ' . $type : ''),
                ':file' => $this->path,
            ]), $previous_e);
        }

        if (!is_readable($this->path)) {
            throw new FileNotReadableException(tr('The ":type" type file ":file" cannot be read', [
                ':type' => ($type ? ' ' . $type : ''),
                ':file' => $this->path,
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
    public function secureDelete(string|bool $clean_path = true, bool $sudo = false): static
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true);
        // Delete all specified patterns
        // Execute the rm command
        Process::new('find', $this->restrictions)
               ->setSudo($sudo)
               ->setTimeout(60)
               ->addArgument($this->path)
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
            Directory::new(dirname($this->path))
                     ->clearDirectory($clean_path, $sudo);
        }

        return $this;
    }


    /**
     * Moves this file to the specified target, will try to ensure target directory exists
     *
     * @param Stringable|string $target
     * @param Restrictions|null $restrictions
     *
     * @return $this
     */
    public function movePath(Stringable|string $target, ?Restrictions $restrictions = null): static
    {
        // Ensure restrictions and ensure target is absolute
        // Restrictions are either specified, included in the target, or this object's restrictions
        $restrictions = Restrictions::default($restrictions, ($target instanceof PathInterface ? $target->getRestrictions() : null), $this->getRestrictions());
        $target       = PathCore::absolutePath($target, must_exist: false);
        // Ensure the target directory exists
        if (file_exists($target)) {
            // Target exists. It has to be a directory where we can move into, or fail!
            if (!is_dir($target)) {
                throw FileExistsException::new(tr('The specified target ":target" already exists', [
                    ':target' => $target,
                ]));
            }
            // Target exists and is directory. Rename target to "this file in the target directory"
            $target = Strings::slash($target) . basename($this->path);

        } else {
            // Target does not exist
            if (str_ends_with($target, '/')) {
                // If the target is indicated to be a directory (because it ends with a slash) then it should be created
                $create = $target;
                $target = Strings::slash($target) . basename($this->path);

            } elseif (!file_exists(dirname($target))) {
                // The target parent directory does not exist. It must be created or fail
                $create = dirname($target);
                $target = Strings::slash(dirname($target)) . basename($this->path);
            }
            if (isset($create)) {
                // Ensure the target directory exist
                Directory::new(dirname($target), $this->restrictions)
                         ->ensure();
            }
        }
        // Check restrictions and execute move
        $this->restrictions->check($target, true);
        rename($this->path, $target);
        // Update this file to the new location, and done
        $this->path = $target;
        $this->setRestrictions($restrictions);

        return $this;
    }


    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
     *
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
     * Returns the stat data for the object file
     *
     * @return array
     */
    public function getStat(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions(false);
        try {
            $stat = stat($this->path);
            if ($stat) {
                return $stat;
            }

            return [];

        } catch (Throwable $e) {
            $this->checkReadable(null, $e);
            // static::checkReadable() will have thrown an exception, but throw this anyway just to be sure
            throw $e;
        }
    }


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
    public function chmod(string|int $mode, bool $recursive = false, bool $sudo = false): static
    {
        if (!($mode)) {
            throw new OutOfBoundsException(tr('No file mode specified'));
        }
        if (!$this->path) {
            throw new OutOfBoundsException(tr('No file specified'));
        }
        // Check filesystem restrictions
        $this->checkRestrictions(true);
        if ($recursive or is_string($mode)) {
            // Use operating system chmod command as PHP chmod does not support these functions
            Process::new('chmod', $this->restrictions)
                   ->setSudo($sudo)
                   ->addArguments([
                       ($recursive ? '-R' : null),
                       '0' . decoct($mode),
                       $this->path,
                   ])
                   ->executeReturnArray();
        } else {
            chmod($this->path, $mode);
        }

        return $this;
    }


    /**
     * Returns the path octal filemode into a text readable filemode (rwxrwxrwx)
     *
     * @return string
     */
    public function getModeHumanReadable(): string
    {
        $return = '';
        $mode   = $this->getmode();
        $mode   = substr(decoct($mode), -3, 3);
        for ($i = 0; $i < 3; $i++) {
            $number = (integer) substr($mode, $i, 1);
            if (($number - 4) >= 0) {
                $return .= 'r';
                $number -= 4;

            } else {
                $return .= '-';
            }
            if (($number - 2) >= 0) {
                $return .= 'w';
                $number -= 2;

            } else {
                $return .= '-';
            }
            if (($number - 1) >= 0) {
                $return .= 'x';

            } else {
                $return .= '-';
            }
        }

        return $return;
    }


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
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false): static
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true);
        if (!$user) {
            $user = posix_getpwuid(posix_getuid());
            $user = $user['name'];
        }
        if (!$group) {
            $group = posix_getpwuid(posix_getuid());
            $group = $group['name'];
        }
        foreach ($this->path as $pattern) {
            Process::new('chown', $this->restrictions)
                   ->setSudo(true)
                   ->addArgument($recursive ? '-R' : null)
                   ->addArgument($user . ':' . $group)
                   ->addArguments($this->path)
                   ->executeReturnArray();
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
     *
     * @return bool
     */
    public function ensureFileReadable(?int $mode = null): bool
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true);
        // If the object file exists and is writable, then we're done.
        if (is_writable($this->path)) {
            return true;
        }
        // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...
        if (file_exists($this->path)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The file ":file" :realis not readable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->path,
                    ':real' => $this->getRealPathLogString(),
                    ':mode' => $mode,
                ]));
                $this->chmod('u+w');

            } catch (ProcessesException) {
                throw new FileNotWritableException(tr('The file ":file" :realis not writable, and could not be made writable', [
                    ':file' => $this->path,
                    ':real' => $this->getRealPathLogString(),
                ]));
            }
        }
        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent directory exists.
        Directory::new(dirname($this->path), $this->restrictions)
                 ->ensure();
        Log::action(tr('Creating non existing file ":file" with file mode ":mode"', [
            ':mode' => Strings::fromOctal($mode),
            ':file' => $this->path,
        ]));

        return false;
    }


    /**
     * Returns a "Real directory ":directory" string if the internal path does not match the internal real_path
     *
     * @return string|null
     */
    protected function getRealPathLogString(): ?string
    {
        if ($this->path === $this->getRealPath()) {
            return null;
        }

        return tr('(Real path ":directory") ', [':directory' => $this->getRealPath()]);
    }


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
    public function getRealPath(): ?string
    {
        return get_null(realpath($this->path));
    }


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
    public function ensureFileWritable(?int $mode = null): bool
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true);

        // If the object file exists and is writable, then we're done.
        if (is_writable($this->path)) {
            return true;
        }

        // From here, the file is not writable. It may not exist, or it may simply not be writable. Lets continue...
        if (file_exists($this->path)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The file ":file" :real is not writable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->path,
                    ':real' => $this->getRealPathLogString(),
                    ':mode' => $mode,
                ]));
                $this->chmod('u+w');

            } catch (ProcessesException) {
                throw new FileNotWritableException(tr('The file ":file" :real is not writable, and could not be made writable', [
                    ':file' => $this->path,
                    ':real' => $this->getRealPathLogString(),
                ]));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent directory exists.
        Directory::new(dirname($this->path), $this->restrictions->getParent())
                 ->ensure();

        return false;
    }


    /**
     * Returns true if the file is a symlink AND its target exists
     *
     * @return bool
     */
    public function isLinkAndTargetExists(): bool
    {
        return is_link($this->path);
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
     * Returns the file type
     *
     * @return string|int|null
     */
    public function getType(): string|int|null
    {
        if (empty($this->type)) {
            $this->type = $this->getStat()['mode'] & 0170000;
        }

        return $this->type;
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
     * Creates a symlink $target that points to this file.
     *
     * @note Will return a NEW Path object (File or Directory, basically) for the specified target
     *
     * @param PathInterface|string      $target
     * @param PathInterface|string|bool $make_relative
     *
     * @return PathInterface
     */
    public function symlinkTargetFromThis(PathInterface|string $target, PathInterface|string|bool $make_relative = true): PathInterface
    {
        $target = new Path($target, $this->restrictions);
        // Calculate absolute or relative path
        if ($make_relative and $this->isAbsolute()) {
            // Convert this symlink in a relative link
            $calculated_target = $target->getRelativePathTo($this, $make_relative);

        } else {
            $calculated_target = $this;
        }
        // Check if target exists as a link
        if ($target->isLink()) {
            // The target itself exists and is a link. Whether that link target exists or not does not matter here, just
            // that its target matches our target
            if (
                Strings::ensureEndsNotWith($target->readLink(true)
                                                  ->getPath(), '/') === Strings::ensureEndsNotWith($this->getPath(), '/')
            ) {
                // Symlink already exists and points to the same file. This is what we wanted to begin with, so all fine
                return $target;
            }
            throw new FileExistsException(tr('Cannot create symlink ":target" with link ":link", the file already exists and points to ":current" instead', [
                ':target'  => $target->getNormalizedPath(),
                ':link'    => Strings::ensureEndsNotWith($calculated_target->getPath(), '/'),
                ':current' => $target->readLink(true)
                                     ->getNormalizedPath(),
            ]));
        }
        // The target exists NOT as a link, but perhaps it might exist as a normal file or directory?
        if ($target->exists()) {
            throw new FileExistsException(tr('Cannot create symlink ":target" with link ":link", the file already exists as a ":type"', [
                ':target' => $target->getPath(),
                ':link'   => Strings::ensureEndsNotWith($calculated_target->getPath(), '/'),
                ':type'   => $target->getTypeName(),
            ]));
        }
        // Ensure that we have restriction access and target parent directory exists
        $target->checkRestrictions(true);
        $target->getParentDirectory()
               ->ensure();
        // Symlink!
        try {
            symlink(Strings::ensureEndsNotWith($calculated_target->getPath(), '/'), $target->getPath());

        } catch (PhpException $e) {
            // Crap, what happened?
            if ($e->messageMatches('symlink(): File exists')) {

                throw new FileExistsException(tr('Cannot symlink ":this" to target ":target" because ":e"', [
                    ':this'   => $this->path,
                    ':target' => $target->getPath(),
                    ':e'      => $e->getMessage(),
                ]));
            }
            // Something else happened.
            throw $e;
        }

        return static::new($target, $this->restrictions);
    }


    /**
     * Returns true if the path for this Path object is absolute (and as such, starts with /)
     *
     * @return bool
     */
    public function isAbsolute(): bool
    {
        return str_starts_with($this->path, '/');
    }


    /**
     * Returns the relative path between the specified path and this object's path
     *
     * @param PathInterface|string      $target
     * @param PathInterface|string|bool $make_absolute
     *
     * @return PathInterface
     */
    public function getRelativePathTo(PathInterface|string $target, PathInterface|string|bool $make_absolute = null): PathInterface
    {
        $target      = static::new($target, $this->restrictions);
        $target_path = Strings::ensureEndsNotWith($target->getNormalizedPath($make_absolute), '/');
        $source_path = Strings::ensureEndsNotWith($this->getNormalizedPath($make_absolute), '/');
        $source_path = explode('/', $source_path);
        $target_path = explode('/', $target_path);
        $return      = [];
        // Compare each directory, as long as its matches the path is the same and we can drop it
        foreach ($source_path as $id => $section) {
            if (!array_key_exists($id, $target_path)) {
                // Target path has nothing more!
                $return[] = '..';
                break;
            }
            if ($section !== $target_path[$id]) {
                // From this part source and target start differing
                break;
            }
            unset($source_path[$id]);
            unset($target_path[$id]);
        }
        if (empty($source_path)) {
            if (empty($target_path)) {
                // The specified target is the same as this path
                return new Path('.');
            }

        } else {
            array_pop($source_path);
            foreach ($source_path as $section) {
                $return[] = '..';
            }
        }
        foreach ($target_path as $section) {
            $return[] = $section;
        }

        return Path::new(implode('/', $return), $target->getRestrictions());
    }


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
    public function getNormalizedPath(Stringable|string|bool|null $make_absolute = null): ?string
    {
        return static::normalizePath($this->path, $make_absolute);
    }


    /**
     * Returns a normalized path that has all ./ and ../ resolved
     *
     * @param Stringable|string           $path
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
    public static function normalizePath(Stringable|string $path, Stringable|string|bool|null $make_absolute = null): ?string
    {
        $path = trim((string) $path);

        if (InstanceCache::exists('path::normalizePath', $path)) {
            return InstanceCache::getLastChecked();
        }

        if ($path[0] !== '/') {
            // Ensure the path is absolute
            $path = static::absolutePath($path, $make_absolute, false);
        }

        // Get the absolute path if requested (default yes, NULL will make an absolute path, only FALSE will skip that)
        // Then resolve all path parts that have ../ or ./
        // Reverse parts to first add to return before removing to avoid immediately passing through root with
        // paths like ../../../this/is/a/test
        // \ and / will both be single slashes
        $path   = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $path   = Strings::replaceDouble($path, DIRECTORY_SEPARATOR, '/');
        $parts  = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $return = [];
        $root   = (str_starts_with($path, '/') ? '/' : '');
        $parts  = array_reverse($parts);
        $count  = count($parts);
        $skip   = 0;

        foreach ($parts as $part_id => $part) {
            if ($part === '.') {
                continue;
            }

            if ($part === '..') {
                if (($skip + $part_id + 1) < $count) {
                    // Skip this entry and the next one
                    $skip++;
                    continue;

                } else {
                    if (!$return) {
                        // There are no more parts to remove either from return or parts, so we passed beyond root
                        throw new OutOfBoundsException(tr('Cannot normalize path ":path", it passes beyond the root directory', [
                            ':path' => $path,
                        ]));
                    }

                    array_pop($return);
                }

            } else {
                if ($skip) {
                    // Skip this entry
                    $skip--;
                    continue;
                }

                $return[] = $part;
            }
        }

        $return = array_reverse($return);
        $return = implode(DIRECTORY_SEPARATOR, $return);

        if (!$return) {
            // There is no path, this must be the root directory
            return '/';
        }

        // Put all the processed path parts back together again, normalized never ends with a / though!
        return InstanceCache::set(Strings::ensureEndsNotWith($root . $return, '/'), 'path::normalizePath', $path);
    }


    /**
     * Returns the path that this link points to
     *
     * @param PathInterface|string|bool $make_absolute
     *
     * @return PathInterface
     */
    public function readLink(PathInterface|string|bool $make_absolute = false): PathInterface
    {
        if (!$this->isLink()) {
            throw new FilesystemException(tr('Cannot readlink path ":path", it is not a symlink', [
                ':path' => $this->path,
            ]));
        }

        $path = readlink(Strings::ensureEndsNotWith($this->path, '/'));

        if ($make_absolute and !str_starts_with($path, '/')) {
            // Links are relative, make them absolute
            if (is_bool($make_absolute)) {
                $make_absolute = dirname($this->getPath()) . '/';
            }

            $path = Strings::slash($make_absolute) . $path;
        }

        // Return (possibly) relative links
        if (is_dir($path)) {
            return new Directory($path, $this->restrictions, false);
        }

        if (file_exists($path)) {
            return new File($path, $this->restrictions, false);
        }

        return new static($path, $this->restrictions, false);
    }


    /**
     * Returns the name of the file type
     *
     * @return string
     */
    public function getTypeName(): string
    {
        if (is_link($this->path)) {
            return 'symlink';
        }
        if (is_dir($this->path)) {
            return 'directory';
        }
        $this->getType();
        if ($this->type == 0x0010000) {
            return 'fifo device';
        }
        if ($this->type == 0x0020000) {
            return 'character device';
        }
        if ($this->type == 0x0060000) {
            return 'block device';
        }
        if ($this->type == 0x0008000) {
            return 'regular file';
        }
        if ($this->type == 0x0140000) {
            return 'socket file';
        }

        return 'unknown';
    }


    /**
     * Returns the parent directory for this file
     *
     * @param RestrictionsInterface|null $restrictions
     *
     * @return DirectoryInterface
     */
    public function getParentDirectory(?RestrictionsInterface $restrictions = null): DirectoryInterface
    {
        return Directory::new(dirname($this->path), $restrictions ?? $this->restrictions->getParent());
    }


    /**
     * Returns how the file was opened, NULL if the file is not open
     *
     * @return EnumFileOpenMode|null
     */
    public function getOpenMode(): ?EnumFileOpenMode
    {
        return $this->open_mode;
    }


    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function tell(): int
    {
        $this->checkOpen('tell');

        $result = ftell($this->stream);

        if ($result === false) {
            // ftell() failed
            throw new FileActionFailedException(tr('Failed to tell file pointer for file ":file"', [
                ':file' => $this->path,
            ]));
        }

        return $result;
    }


    /**
     * Throws an exception if the file is not open
     *
     * @param string                $method
     * @param EnumFileOpenMode|null $mode
     *
     * @return $this
     */
    protected function checkOpen(string $method, ?EnumFileOpenMode $mode = null): static
    {
        if (!$this->isOpen()) {
            throw new FileOpenException(tr('Cannot execute method ":method()" on file ":file", it is closed', [
                ':file'   => $this->path,
                ':method' => $method,
            ]));
        }

        if ($mode) {
            return $this->checkWriteMode($this->open_mode);
        }

        return $this;
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
     * Ensure that the specified mode allows writing
     *
     * @param EnumFileOpenMode $mode
     *
     * @return $this
     */
    protected function checkWriteMode(EnumFileOpenMode $mode): static
    {
        if ($mode == EnumFileOpenMode::readOnly) {
            throw new ReadOnlyModeException(tr('Cannot write to file ":file", the file is opened in readonly mode', [
                ':file' => $this->path,
            ]));
        }

        return $this;
    }


    /**
     * Rewinds the position of the file pointer
     *
     * @return static
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function rewind(): static
    {
        $this->checkOpen('rewind');

        $result = rewind($this->stream);

        if ($result === false) {
            // rewind() failed
            throw new FileActionFailedException(tr('Failed to rewind file ":file"', [
                ':file' => $this->path,
            ]));

        }

        return $this;
    }


    /**
     * Reads and returns the next text line in this file
     *
     * @param int|null $buffer
     *
     * @return string|false
     */
    public function readLine(?int $buffer = null): string|false
    {
        $this->checkOpen('readLine');

        if (!$buffer) {
            $buffer = $this->getBufferSize();
        }

        $data = fgets($this->stream, $buffer);

        if ($data === false) {
            return $this->processReadFailure('line', false);
        }

        return $data;
    }


    /**
     * Determines what exception to throw for a read failure
     *
     * @param string                  $type
     * @param array|string|false|null $data
     * @param bool                    $test_feof If false will skip FEOF test
     *
     * @return array|string|false|null
     */
    protected function processReadFailure(string $type, array|string|false|null $data, bool $test_feof = true): array|string|false|null
    {
        // FEOF errors are only checked if we didn't try to read full file contents
        if ($test_feof and $this->isEof()) {
            return $data;
        }

        throw new FileReadException(tr('Cannot read ":type" from file ":file", the file pointer is at the end of the file', [
            ':type' => $type,
            ':file' => $this->path,
        ]));
    }


    /**
     * Returns true if the file pointer is at EOF
     *
     * @return bool
     */
    public function isEof(): bool
    {
        $this->checkOpen('getEof');

        return feof($this->stream);
    }


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
    public function readCsv(?int $max_length = null, string $separator = ",", string $enclosure = "\"", string $escape = "\\"): array|false
    {
        $this->checkOpen('readCsv');

        $data = fgetcsv($this->stream, $max_length, $separator, $enclosure, $escape);

        if ($data === false) {
            return $this->processReadFailure('CSV', false);
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
        $this->checkOpen('readCharacter');

        $data = fgetc($this->stream);

        if ($data === false) {
            return $this->processReadFailure('character', false);
        }

        return $data;
    }


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
    public function readBytes(int $length, int $start = 0): string|false
    {
        $data = $this->checkRestrictions(false)
                     ->checkClosed('readBytes')
                     ->mountIfNeeded()
                     ->open(EnumFileOpenMode::readOnly)
                     ->read($start + $length);

        if ($data === false) {
            return $this->processReadFailure('character', false);
        }

        $data = substr($data, $start);
        $this->close();

        return $data;
    }


    /**
     * Reads and returns the specified number of bytes from the current pointer location
     *
     * @param int|null $buffer
     * @param int|null $seek
     *
     * @return string|false
     */
    public function read(?int $buffer = null, ?int $seek = null): string|false
    {
        $this->checkOpen('read');

        if ($seek) {
            $this->seek($seek);
        }

        $buffer = $this->getBufferSize($buffer);
        $data   = fread($this->stream, $buffer);

        if ($data === false) {
            return $this->processReadFailure('data', false);
        }

        return $data;
    }


    /**
     * Sets the internal file pointer to the specified offset
     *
     * @param int $offset
     * @param int $whence
     *
     * @return static
     * @throws FileNotOpenException|FileActionFailedException
     */
    public function seek(int $offset, int $whence = SEEK_SET): static
    {
        $this->checkOpen('seek');

        $result = fseek($this->stream, $offset, $whence);

        if ($result) {
            // The file seek failed
            if (empty(stream_get_meta_data($this->stream)['seekable'])) {
                // File mode is not seekable
                throw new FileActionFailedException(tr('Failed to seek in file ":file" because file mode ":mode" does not allow seek', [
                    ':mode' => $this->open_mode->value,
                    ':file' => $this->path,
                ]));
            }

            // No idea why
            throw new FileActionFailedException(tr('Failed to seek in file ":file"', [
                ':file' => $this->path,
            ]));

        }

        return $this;
    }


    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param EnumFileOpenMode $mode
     * @param resource         $context
     *
     * @return static
     */
    public function open(EnumFileOpenMode $mode, $context = null): static
    {
        // Check filesystem restrictions and open the file
        $this->checkRestrictions(($mode !== EnumFileOpenMode::readOnly))
             ->checkClosed('open')
             ->mountIfNeeded();

        try {
            $stream = fopen($this->path, $mode->value, false, $context);

        } catch (Throwable $e) {
            // Failed to open the target file
            $this->checkReadable('target', $e);
        }

        if ($stream) {
            // All okay!
            $this->stream    = $stream;
            $this->open_mode = $mode;

            return $this;
        }

        // The file couldn't be opened. Check if the file is accessible.
        switch ($mode) {
            case EnumFileOpenMode::readOnly:
                $this->checkReadable();
                break;

            default:
                $this->checkWritable();
                break;
        }

        throw new FilesystemException(tr('Failed to open file ":file"', [':file' => $this->path]));
    }


    /**
     * Throws an exception if the file is not closed
     *
     * @param string $method
     *
     * @return $this
     * @throws FileOpenException
     */
    protected function checkClosed(string $method): static
    {
        if ($this->isOpen()) {
            throw new FileOpenException(tr('Cannot execute method ":method()" on file ":file", it is already open', [
                ':file'   => $this->path,
                ':method' => $method,
            ]));
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
     * @param string|null    $type       This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     *
     * @return static
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null): static
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true);
        if (!$this->exists()) {
            if (!file_exists(dirname($this->path))) {
                // The file doesn't exist and neither does its parent directory
                throw new FileNotExistException(tr('The:type file ":file" cannot be written because it does not exist and neither does the parent directory ":directory"', [
                    ':type'      => ($type ? '' : ' ' . $type),
                    ':file'      => $this->path,
                    ':directory' => dirname($this->path),
                ]), $previous_e);
            }
            // File doesn't exist, check if the parent directory is writable so that the file can be created
            Directory::new(dirname($this->path), $this->restrictions)
                     ->checkWritable($type, $previous_e);

        } elseif (!is_writable($this->path)) {
            throw new FileNotWritableException(tr('The:type file ":file" cannot be written', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->path,
            ]), $previous_e);
        }

        return $this;
    }


    /**
     * Checks if the specified file exists
     *
     * @param bool $auto_mount
     *
     * @return static
     */
    protected function mountIfNeeded(bool $auto_mount = true): static
    {
        $exists = file_exists($this->path);
        if (!$exists and $auto_mount) {
            // Oh noes! This path doesn't exist! Maybe a path isn't mounted?
            $this->attemptAutoMount();
        }

        return $this;
    }


    /**
     * Closes this file
     *
     * @param bool $force
     *
     * @return static
     */
    public function close(bool $force = false): static
    {
        if (!$this->stream) {
            if ($force) {
                throw new FileNotOpenException(tr('The file ":file" cannot be closed, it is not open', [
                    ':file' => $this->path,
                ]));
            }
        }
        fclose($this->stream);
        $this->stream    = null;
        $this->open_mode = null;

        return $this;
    }


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
    public function getContentsAsString(bool $use_include_path = false, $context = null, int $offset = 0, ?int $length = null): string
    {
        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->checkRestrictions(false)
             ->checkClosed('getContents')
            ->mountIfNeeded();

        try {
            $data = file_get_contents($this->path, $use_include_path, $context, $offset, $length);

        } catch (PhpException $e) {
            $this->checkReadable('', new FilesystemException(tr('Failed to get contents of file ":file" as string', [
                ':file' => $this->path,
            ]), previous: $e));
        }

        if ($data === false) {
            return $this->processReadFailure('contents', '', false);
        }

        return $data;
    }


    /**
     * Returns the contents of this file as an Iterator object
     *
     * @param int $flags
     * @param     $context
     *
     * @return IteratorInterface
     */
    public function getContentsAsIterator(int $flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context = null): IteratorInterface
    {
        return Iterator::new($this->getContentsAsArray($flags, $context));
    }


    /**
     * Returns the contents of this file as an array
     *
     * @param int $flags
     * @param     $context
     *
     * @return array
     */
    public function getContentsAsArray(int $flags = FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES, $context = null): array
    {
        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->checkRestrictions(false)
             ->checkClosed('getContents')
             ->mountIfNeeded();

        try {
            $data = file($this->path, $flags, $context);

        } catch (PhpException $e) {
            $this->checkReadable('', new FilesystemException(tr('Failed to get contents of file ":file" as array', [
                ':file' => $this->path,
            ]), previous: $e));
        }

        if ($data === false) {
            return $this->processReadFailure('contents', [], false);
        }

        return $data;
    }


    /**
     * Write the specified data to this file
     *
     * @param string $data
     * @param int    $flags
     * @param null   $context
     *
     * @return $this
     */
    public function putContents(string $data, int $flags = 0, $context = null): static
    {
        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->checkRestrictions(true)
             ->checkClosed('putContents')
             ->mountIfNeeded();

        Directory::new(dirname($this->path), $this->restrictions->getParent()->getParent())->ensure();

        file_put_contents($this->path, $data, $flags, $context);

        return $this;
    }


    /**
     * Append specified data string to the end of the object file
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return static
     */
    public function appendData(string $data, ?int $length = null): static
    {
        if ($this->isOpen()) {
            return $this->write($data, $length);
        }

        return $this->open(EnumFileOpenMode::writeOnlyAppend)
                    ->write($data)
                    ->close();
    }


    /**
     * Create the specified file
     *
     * @param bool $force
     *
     * @return static
     */
    public function create(bool $force = false): static
    {
        if ($this->exists()) {
            if (!$force) {
                throw new FileExistsException(tr('Cannot create file ":file", it already exists', [
                    ':file' => $this->path,
                ]));
            }
        }

        if ($this->isOpen()) {
            // Yeah, so it exists anyway because we have it open. Perhaps the file was removed while open, so the inode
            // is still there?
            if (!$force) {
                throw new FileExistsException(tr('Cannot create file ":file", it does not exist, but is open. Perhaps the file was deleted but the open inode is still there?', [
                    ':file' => $this->path,
                ]));
            }

            $this->close();
        }

        return $this->touch();
    }


    /**
     * Sets access and modification time of file
     *
     * @return $this
     */
    public function touch(): static
    {
        if ($this->exists()) {
            // Just touch it, I dare you.
            touch($this->path);

        } elseif ($this instanceof DirectoryInterface) {
            // If this is supposed to be a directory, create it
            return $this->ensure();

        } else {
            // Create it by touching it. Or something like that
            touch($this->path);
        }

        return $this;
    }


    /**
     * Concatenates a list of files to a target file
     *
     * @param string|array $sources The source files
     *
     * @return static
     */
    public function appendFiles(string|array $sources): static
    {
        // Check filesystem restrictions
        $this->checkRestrictions(true)
             ->checkClosed('appendFiles')
             ->mountIfNeeded();

        // Ensure the target path exists
        Directory::new(dirname($this->path), $this->restrictions)->ensure();

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
     * Synchronizes changes to the file (including meta-data)
     *
     * @return $this
     */
    public function sync(): static
    {
        $this->checkOpen('sync');

        if (!fsync($this->stream)) {
            throw new FileSyncException(tr('Failed to sync file ":file"', [
                ':file' => $this->path,
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
        $this->checkOpen('syncData');

        if (!fdatasync($this->stream)) {
            throw new FileSyncException(tr('Failed to data sync file ":file"', [
                ':file' => $this->path,
            ]));
        }

        return $this;
    }


    /**
     * Will overwrite the file with random data before deleting it
     *
     * @param int $passes
     *
     * @return $this
     */
    public function shred(int $passes = 3): static
    {
        if (($passes < 1) or ($passes > 20)) {
            throw new OutOfBoundsException(tr('Invalid number of passes ":passes" specified, must be between 1 and 20', [
                ':passes' => $passes,
            ]));
        }

        if ($this instanceof DirectoryInterface) {
            throw new UnderConstructionException();
        }

        $count = (int) ceil($this->getSize() / 4096);

        for ($pass = 1; $pass <= $passes; $pass++) {
            Log::action(tr('Shredding file ":file" with pass ":pass"', [
                ':file' => $this->path,
                ':pass' => $pass,
            ]), 4);

            Process::new('dd', $this->restrictions)
                   ->setSudo(true)
                   ->setAcceptedExitCodes([
                       0,
                       1,
                   ]) // Accept 1 if the DD process stopped due to disk full, which is expected
                   ->setTimeout(0)
                   ->addArguments([
                       'if=/dev/urandom',
                       'of=' . $this->path,
                       'bs=4096',
                       'count=' . $count,
                   ])
                   ->execute(EnumExecuteMethod::noReturn);
        }

        return $this->delete();
    }


    /**
     * Returns the size in bytes of this file or directory
     *
     * @param bool $recursive
     *
     * @return int
     */
    public function getSize(bool $recursive = true): int
    {
        if ($this instanceof FileInterface) {
            if ($this->exists()) {
                // This is a single file!
                return filesize($this->path);
            }

            return 0;
        }

        // Return the number of all files in this directory
        $files = scandir($this->path);
        $size  = 0;

        foreach ($files as $file) {
            if (($file === '.') or ($file === '..')) {
                // Skip crap
                continue;
            }

            // Filename must have the complete absolute path
            $file = $this->path . $file;

            if (is_dir($file)) {
                if ($recursive) {
                    // Get filesize of this entire directory
                    $size += Path::new($file, $this->restrictions)
                                 ->getSize($recursive);
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
     * Returns the device path of the filesystem where this file is stored
     *
     * @return string
     */
    public function getMountDevice(): string
    {
        $this->checkExists();
        $mounts = Mounts::listMountTargets();

        foreach ($mounts as $path => $mount) {
            if (str_starts_with($this->path, $path)) {
                return $mount['source'];
            }
        }

        throw new MountLocationNotFoundException(tr('Failed to find a mount location for the path ":path"', [
            ':path' => $this->path,
        ]));
    }


    /**
     * Checks if the specified file exists, throws exception if it doesn't
     *
     * @param bool $force
     * @param bool $check_dead_symlink
     * @param bool $auto_mount
     *
     * @return static
     */
    public function checkExists(bool $force = false, bool $check_dead_symlink = false, bool $auto_mount = true): static
    {
        if (!$this->exists($check_dead_symlink, $auto_mount)) {
            if (!$force) {
                throw new FileNotExistException(tr('Specified file ":file" does not exist', [
                    ':file' => $this->path,
                ]));
            }

            // Force the file to exist
            $this->getParentDirectory()->ensure();
            $this->touch();
        }

        return $this;
    }


    /**
     * Returns a find object that will search for files in the specified path and upon execution returns a files-object
     * that can execute callbacks on said files
     *
     * @return FindInterface
     */
    public function find(): FindInterface
    {
        return Find::new($this->restrictions)->setPath($this->path);
    }


    /**
     * Replaces the current path by moving it out of the way and moving the target in its place, then deleting the
     * original
     *
     * @param PathInterface|string $target
     *
     * @return PathInterface
     */
    public function replaceWithPath(PathInterface|string $target): PathInterface
    {
        $target = Path::new($target);

        // Move the old out of the way, push the new in, delete the current
        if ($this->exists()) {
            $new = clone $this;
            $this->rename(Directory::getTemporary());
            $target->rename($new);
            $this->delete();

        } else {
            // The source doesn't exist, so we don't have to move anything out of place or delete afterwards
            $this->getParentDirectory()
                 ->ensure();
            $target->rename($this);
        }

        return $target;
    }


    /**
     * Renames a file or directory
     *
     * @param Stringable|string $to_filename
     * @param null              $context
     *
     * @return static
     */
    public function rename(Stringable|string $to_filename, $context = null): static
    {
        $to     = (string) $to_filename;
        $result = rename($this->path, $to, $context);

        if (!$result) {
            throw new FileRenameException(tr('Failed to rename file or directory ":file" to ":to"', [
                ':file' => $this->path,
                ':to'   => $to,
            ]));
        }

        $this->path = $to;

        if ($to_filename instanceof PathInterface) {
            $this->setRestrictions($to_filename->getRestrictions());
        }

        return $this;
    }


    /**
     * Ensures that this path is a symlink
     *
     * @return $this
     */
    public function checkSymlink(Stringable|string $target): static
    {
        if (!$this->isLink()) {
            throw new FileNotSymlinkException(tr('The path ":path" must be a symlink but instead is a ":type" file', [
                ':path' => $this->path,
                ':type' => $this->getTypeName(),
            ]));
        }

        if ($this->getLinkTarget() != $target) {
            throw new FileNotSymlinkException(tr('The path ":path" must be symlinked to ":target" but is symlinked to ":instead" instead', [
                ':path'    => $this->path,
                ':target'  => $target,
                ':instead' => $this->getLinkTarget(),
            ]));
        }

        return $this;
    }


    /**
     * Wrapper for Path::readlink()
     *
     * @param PathInterface|string|bool $absolute
     *
     * @return PathInterface
     */
    public function getLinkTarget(PathInterface|string|bool $absolute = false): PathInterface
    {
        return $this->readLink($absolute);
    }


    /**
     * Returns a PathInterface object with the specified path prepended to this path
     *
     * @param PathInterface|string $path
     * @param bool                 $make_absolute
     *
     * @return FileInterface
     */
    public function prependPath(PathInterface|string $path, bool $make_absolute = false): PathInterface
    {
        $path = Strings::ensureEndsWith((string) $path, '/') . $this->getPath();

        return Path::new($path, $this->restrictions, $make_absolute);
    }


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
    public function symlinkTreeToTarget(PathInterface|string $target, PathInterface|string|null $alternate_path = null, ?RestrictionsInterface $restrictions = null, bool $rename = false): PathInterface
    {
        // Ensure target is a Path object with restrictions
        $target = Path::new($target, $restrictions ?? $this->restrictions);

        if (empty($alternate_path)) {
            // We'll create the symlinks in the same directory as where we're linking from. Use Target object
            $alternate_path = clone $target;

        } else {
            // We'll create the symlink in a different directory than where we're linking from. Ensure alternate path is
            // a Path object
            $alternate_path = Path::new($alternate_path, $target->restrictions);
        }

        if ($this->isDir()) {
            // Source is a directory, target must be a directory too, process all files in this directory
            $dir_target         = Directory::new($target);
            $dir_alternate_path = Directory::new($alternate_path)->ensure();

            // Go over each file in this directory.
            // If the file is a directory then create it in the target, if it is a normal file, then create a symlink
            foreach ($this->getFilesObject() as $path) {
                // Get the section that we'll be working with
                $section = Strings::ensureStartsNotWith(Strings::from($path->getPath(), $this->path), '/');

                if ($path->isDir()) {
                    $path->symlinkTreeToTarget($dir_target->addDirectory($section), $dir_alternate_path->addDirectory($section), rename: $rename);

                } else {
                    $number = null;

                    while (true) {
                        try {
                            // Create symlink for only this file
                            $link = $dir_target->addFile($section)
                                               ->getRelativePathTo($path);
                            $dir_alternate_path->addFile($section . $number)
                                               ->symlinkThisToTarget($link);
                            break;

                        } catch (FileExistsException $e) {
                            if (!$rename) {
                                throw $e;
                            }

                            if (!$dir_alternate_path->addFile($section . $number)->isLink()) {
                                // Only retry if the existing target is a symlink too. If the existing target is a
                                // normal file, then assume that this normal file was there to replace this link
                                break;
                            }
                            // The target already exists, rename and retry!
                            $number++;
                        }
                    }
                }
            }

            return $alternate_path;
        }

        // Source is a file, create symlink for only this file
        $link = $target->getRelativePathTo($this);

        return $alternate_path->symlinkThisToTarget($link);
    }


    /**
     * Returns a FilesInterface object that will contain all the files under this current path
     *
     * @param bool $reload
     *
     * @return FilesInterface
     */
    public function getFilesObject(bool $reload = false): FilesInterface
    {
        $this->checkRestrictions(false);

        if (empty($this->source) or $reload) {
            $this->checkReadable('directory');

            if ($this->isDir()) {
                // Load all files in this directory
                $this->files = Files::new(scandir($this->path), $this->restrictions)->setParent($this);

            } else {
                // This is a file, so there are no files beyond THIS file.
                $this->files = Files::new([$this->path], $this->restrictions)->setParent($this);
            }
        }

        return $this->files;
    }


    /**
     * Makes this path a symlink that points to the specified target.
     *
     * @note Will return a NEW Path object (File or Directory, basically) for the specified target
     *
     * @param PathInterface|string      $target
     * @param PathInterface|string|bool $make_relative
     *
     * @return PathInterface
     */
    public function symlinkThisToTarget(PathInterface|string $target, PathInterface|string|bool $make_relative = true): PathInterface
    {
        $target = new Path($target, $this->restrictions);

        // Calculate absolute or relative path
        if ($make_relative and $target->isAbsolute()) {
            // Convert this symlink in a relative link
            $calculated_target = $this->getRelativePathTo($target, $make_relative);

        } else {
            $calculated_target = $target;
        }

        // Check if target exists as a link
        if ($this->isLink()) {
            // The target itself exists and is a link. Whether that link target exists or not does not matter here, just
            // that its target matches our target
            if (Strings::ensureEndsNotWith($this->readLink(true)->getPath(), '/') === Strings::ensureEndsNotWith($target->getPath(), '/')) {
                // Symlink already exists and points to the same file. This is what we wanted to begin with, so all fine
                return $target;
            }

            throw new FileExistsException(tr('Cannot create symlink ":target" with link ":link", the file already exists and points to ":current" instead', [
                ':target'  => $this->getNormalizedPath(),
                ':link'    => $calculated_target->getPath(),
                ':current' => $this->readLink(true)
                                   ->getNormalizedPath(),
            ]));
        }

        // The target exists NOT as a link, but perhaps it might exist as a normal file or directory?
        if ($this->exists()) {
            throw new FileExistsException(tr('Cannot create symlink ":source" that points to ":target", the file already exists as a ":type"', [
                ':target' => $calculated_target->getPath(),
                ':source' => $this->getNormalizedPath(),
                ':type'   => $this->getTypeName(),
            ]));
        }

        // Ensure that we have restriction access and target parent directory exists
        $this->checkRestrictions(true);
        $this->getParentDirectory()->ensure();

        // Symlink!
        try {
            symlink(Strings::ensureEndsNotWith($calculated_target->getPath(), '/'), $this->getPath());

        } catch (PhpException $e) {
            // Crap, what happened?
            if ($e->messageMatches('symlink(): File exists')) {
                throw new FileExistsException(tr('Cannot symlink ":this" to target ":target" because ":e"', [
                    ':this'   => $this->getPath(),
                    ':target' => $target->getPath(),
                    ':e'      => $e->getMessage(),
                ]));
            }

            // Something else happened.
            throw $e;
        }

        return $target;
    }


    /**
     * Will scan this path for symlinks and delete all of them one by one
     *
     * @return $this
     */
    public function clearTreeSymlinks(bool $clean = false): static
    {
        if ($this->exists()) {
            $list = Find::new($this->restrictions)
                        ->setExecutionDirectory($this)
                        ->setPath($this)
                        ->setType('l')
                        ->setCallback(function ($file) use ($clean) {
                            Path::new($file, $this->restrictions)
                                ->delete(true);
                        })
                        ->getFiles();

            foreach ($list as $file) {
                $file->delete(true);
            }
        }

        return $this;
    }


    /**
     * Write the specified data to this file with the requested file mode
     *
     * @param string           $data
     * @param EnumFileOpenMode $write_mode
     *
     * @return $this
     */
    protected function save(string $data, EnumFileOpenMode $write_mode = EnumFileOpenMode::writeOnly): static
    {
        $this->checkRestrictions(true);

        // Make sure the file path exists. NOTE: Restrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        Directory::new(dirname($this->path), $this->restrictions->getParent()->getParent())->ensure();

        return $this->open($write_mode)
                    ->write($data)
                    ->close();
    }


    /**
     * Binary-safe write the specified data to this file
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return $this
     */
    public function write(string $data, ?int $length = null): static
    {
        $this->checkOpen('write');
        fwrite($this->stream, $data, $length);

        return $this;
    }


    /**
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback): static
    {
        foreach ($this->scan() as $file) {
            $callback($file);
        }

        return $this;
    }


    /**
     * Checks if the current path obeys the requirements
     *
     * @return void
     */
    protected function checkRequirements(): void
    {
        if (empty($this->requirements)) {
            $this->requirements = Requirements::new()->load();
        }

        $this->requirements->check($this->path);
    }


    /**
     * Returns the filesystem for the current path
     *
     * @return FilesystemInterface
     */
    public function getFilesystem(): FilesystemInterface
    {
        try {
            $results = Df::new()
                         ->setPath($this->path)
                         ->executeNoReturn()
                         ->getResults();

        } catch (ProcessFailedException $e) {
            $this->checkReadable('block-file', $e);
            throw $e;
        }

        $results = $results->getFirstValue();
        $results = $results['filesystem'];

        return new Filesystem($results);
    }


    /**
     * Enables file access
     *
     * @return void
     */
    public static function enable(): void
    {
        static::$read_enabled  = true;
        static::$write_enabled = true;
    }


    /**
     * Disables file access
     *
     * @return void
     */
    public static function disable(): void
    {
        static::$read_enabled  = false;
        static::$write_enabled = false;
    }


    /**
     * Enables file read access
     *
     * @return void
     */
    public static function enableRead(): void
    {
        static::$read_enabled = true;
    }


    /**
     * Disables file read access
     *
     * @return void
     */
    public static function disableRead(): void
    {
        static::$read_enabled = false;
    }


    /**
     * Enables file write access
     *
     * @return void
     */
    public static function enableWrite(): void
    {
        static::$write_enabled = true;
    }


    /**
     * Disables file write access
     *
     * @return void
     */
    public static function disableWrite(): void
    {
        static::$write_enabled = false;
    }


    /**
     * Returns true if file read access is available
     *
     * @return bool
     */
    public static function readIsEnabled(): bool
    {
        return static::$read_enabled;
    }


    /**
     * Returns true if file write access is available
     *
     * @return bool
     */
    public static function writeIsEnabled(): bool
    {
        return static::$write_enabled;
    }


    /**
     * Checks if write access is available
     *
     * @return static
     */
    public function checkWriteAccess(): static
    {
        if (!static::$write_enabled) {
            throw new FileNotWritableException(tr('The file ":file" cannot be written because all write access has been disabled', [
                ':file' => $this->path
            ]));
        }

        return $this;
    }


    /**
     * Checks if read access is available
     *
     * @return static
     */
    public function checkReadAccess(): static
    {
        if (!static::$read_enabled) {
            throw new FileNotReadableException(tr('The file ":file" cannot be read because all read access has been disabled', [
                ':file' => $this->path
            ]));
        }

        return $this;
    }


    /**
     * Returns true if the specified file open mode is a write mode
     *
     * @param EnumFileOpenMode $mode
     *
     * @return bool
     */
    public static function isWriteMode(EnumFileOpenMode $mode): bool
    {
        return match($mode) {
            EnumFileOpenMode::closeOnExec,
            EnumFileOpenMode::readOnly            => false,
            EnumFileOpenMode::readWriteExisting,
            EnumFileOpenMode::writeOnlyTruncate,
            EnumFileOpenMode::readWriteTruncate,
            EnumFileOpenMode::writeOnlyAppend,
            EnumFileOpenMode::readWriteAppend,
            EnumFileOpenMode::writeOnlyCreateOnly,
            EnumFileOpenMode::readWriteCreateOnly,
            EnumFileOpenMode::writeOnly,
            EnumFileOpenMode::readWrite           => true
        };
    }
}
