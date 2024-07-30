<?php

/**
 * Class FsPathCore
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
use Phoundation\Filesystem\Exception\FilesystemNoRestrictionsSetExceptions;
use Phoundation\Filesystem\Exception\FileTruncateException;
use Phoundation\Filesystem\Exception\MountLocationNotFoundException;
use Phoundation\Filesystem\Exception\NotASymlinkException;
use Phoundation\Filesystem\Exception\PathNotDomainException;
use Phoundation\Filesystem\Exception\ReadOnlyModeException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Exception\SymlinkBrokenException;
use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Interfaces\FsFilesInterface;
use Phoundation\Filesystem\Interfaces\FsFilesystemInterface;
use Phoundation\Filesystem\Interfaces\FsInfoInterface;
use Phoundation\Filesystem\Interfaces\FsPathInterface;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\Mounts\FsMount;
use Phoundation\Filesystem\Mounts\FsMounts;
use Phoundation\Filesystem\Requirements\Interfaces\RequirementsInterface;
use Phoundation\Filesystem\Requirements\Requirements;
use Phoundation\Filesystem\Traits\TraitDataBufferSize;
use Phoundation\Filesystem\Traits\TraitDataIsRelative;
use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Exception\ProcessesException;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Servers\Traits\TraitDataServer;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;

class FsPathCore implements FsPathInterface
{
    use TraitDataRestrictions;
    use TraitDataBufferSize;
    use TraitDataIsRelative;
    use TraitDataServer;


    public const DIRECTORY_SEPARATOR = '/';


    /**
     * The target file name in case operations creates copies of this file
     *
     * @var string|null $target
     */
    protected ?string $target = null;

    /**
     * The file for this object
     *
     * @var string|null $source
     */
    protected ?string $source = null;

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
     * FsFiles under this path. If the current path is a file, this Iterator will contain only one entry, THIS file.
     *
     * @var FsFilesInterface $files
     */
    protected FsFilesInterface $files;

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
     * Returns true if the path for this object is NULL (not set)
     *
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->source === null;
    }


    /**
     * Returns true if the path for this object is NOT NULL (is set)
     *
     * @return bool
     */
    public function isSet(): bool
    {
        return $this->source !== null;
    }


    /**
     * Returns a new FsFileFileInterface or Directory object with the specified restrictions
     *
     * @param mixed                                     $source
     * @param FsRestrictionsInterface|array|string|null $restrictions
     *
     * @return FsPathInterface
     * @throws FileNotExistException
     */
    public static function newExisting(Stringable|string|null $source = null, FsRestrictionsInterface|array|string|null $restrictions = null): FsPathInterface
    {
        if ($source instanceof FsPathInterface) {
            if ($source->isDirectory()) {
                return FsDirectory::new($source, $restrictions);
            }

            if ($source->exists()) {
                return FsFile::new($source, $restrictions);
            }

        } else {
            $source = (string) $source;

            if (is_dir($source)) {
                return FsDirectory::new($source, $restrictions);
            }

            if (file_exists($source)) {
                return FsFile::new($source, $restrictions);
            }
        }

        throw new FileNotExistException(tr('The specified path ":path" does not exist', [
            ':path' => $source,
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
     * @param FsPathInterface|string $path
     * @param string|null            $extension
     *
     * @return FsPathInterface
     */
    public static function getAvailableVersion(FsPathInterface|string $path, ?string $extension = null): FsPathInterface
    {
        $prefix    = '';
        $version   = 97;
        $extension = Strings::ensureStartsWith($extension, '.');
        $path      = FsPath::new($path)->appendPath($extension);

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

            $path->setSource(Strings::untilReverse($path->getSource(), $extension) . $prefix . chr($version));
        }

        return $path;
    }


    /**
     * Returns a FsPathInterface object with the specified path appended to this path
     *
     * @param FsPathInterface|string      $path
     * @param Stringable|string|bool|null $absolute_prefix
     *
     * @return FsFileInterface
     */
    public function appendPath(FsPathInterface|string $path, Stringable|string|bool|null $absolute_prefix = false): FsPathInterface
    {
        $path = $this->getSource() . Strings::ensureStartsNotWith((string) $path, '/');

        return FsPath::new($path, $this->restrictions, $absolute_prefix);
    }


    /**
     * Path class toString method
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getSource();
    }


    /**
     * Returns the path
     *
     * @param FsPathInterface|string|null $from
     *
     * @return string|null
     */
    public function getSource(FsPathInterface|string|null $from = null): ?string
    {
        if ($this->isDirectory()) {
            $return = Strings::slash($this->source);

        } else {
            $return = $this->source;
        }

        if ($from) {
            if ($from instanceof FsPathInterface) {
                $from = $from->getSource();
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

        return $return;
    }


    /**
     * Sets the file for this Path object
     *
     * @param Stringable|string|null      $path
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     *
     * @return static
     */
    public function setSource(Stringable|string|null $path, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): static
    {
        if ($this->isOpen()) {
            $this->close();
        }

        if ($absolute_prefix === false) {
            // No prefix specified, if the path is relative, leave it. This may be useful, for example, with relative
            // paths that may not even exist
            $this->source = (string) $path;

        } else {
            // Ensure absolute paths are absolute
            $this->source = static::absolutePath($path, $absolute_prefix, $must_exist);
        }

        return $this;
    }


    /**
     * Returns true if the file is a directory
     *
     * @return bool
     */
    public function isDirectory(): bool
    {
        return $this->source and is_dir($this->source);
    }


    /**
     * Make this (relative) path an absolute path
     *
     * Will convert
     *
     * @param string|null $prefix
     * @param bool        $must_exist
     *
     * @return $this
     */
    public function makeAbsolute(?string $prefix = null, bool $must_exist = true): static
    {
        $this->source = static::absolutePath($this->source, $prefix, $must_exist);

        return $this;
    }


    /**
     * Make this objects' path a normalized path
     *
     * Normalized path will have all "~/", "./", "../" resolved, symlinks will NOT be resolved. Target does not need to
     * exist
     *
     * @param Stringable|string|bool|null $absolute_prefix
     *
     * @return $this
     */
    public function makeNormalized(Stringable|string|bool|null $absolute_prefix = null): static
    {
        $this->source = static::normalizePath($this->source, $absolute_prefix);

        return $this;
    }


    /**
     * Make this path a real path
     *
     * Real path will resolve all symlinks, requires that the path exists!
     *
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool $must_exist
     * @return $this
     */
    public function makeRealPath(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): static
    {
        $this->source = static::realpath($this->source, $absolute_prefix, $must_exist);

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
        return static::absolutePath($this->source, $prefix, $must_exist);
    }


    /**
     * Returns a new Directory object with the specified restrictions starting from the specified path, applying a
     * number of defaults
     *
     * . Is DIRECTORY_ROOT
     * ~ is the current shell's user home directory
     *
     * @param Stringable|string|null      $path
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     *
     * @return static
     */
    public static function absolutePath(Stringable|string|null $path = null, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = true): string
    {
        $path = trim((string) $path);

        if (InstanceCache::exists('path::absolutePath', $path)) {
            return InstanceCache::getLastChecked();
        }

        $path = str_replace('//', '/', $path);

        if ($absolute_prefix === false) {
            // Don't make it absolute at all
            return $path;
        }

        if (!$path) {
            // No path specified? Use the project root directory
            return DIRECTORY_ROOT;
        }

        if ($absolute_prefix === true) {
            // Prefix true is considered the same as prefix null
            $absolute_prefix = null;
        }

        if (static::onDomain($path)) {
            // This is a domain:/file URL, it's already absolute
            return $path;
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
                $absolute_prefix = trim((string) $absolute_prefix);

                // Try to apply default prefixes
                switch ($absolute_prefix) {
                    case '':
                        $absolute_prefix = DIRECTORY_ROOT;
                        break;

                    case 'css':
                        $absolute_prefix = DIRECTORY_CDN . LANGUAGE . '/css/';
                        break;

                    case 'js':
                        // no break

                    case 'javascript':
                        $absolute_prefix = DIRECTORY_CDN . LANGUAGE . '/js/';
                        break;

                    case 'img':
                        // no break

                    case 'image':
                        // no break

                    case 'images':
                        $absolute_prefix = DIRECTORY_CDN . LANGUAGE . '/img/';
                        break;

                    case 'font':
                        // no break

                    case 'fonts':
                        $absolute_prefix = DIRECTORY_CDN . LANGUAGE . '/fonts/';
                        break;

                    case 'video':
                        // no break

                    case 'videos':
                        $absolute_prefix = DIRECTORY_CDN . LANGUAGE . '/video/';
                        break;
                }

                // Prefix $path with $prefix
                $return = Strings::slash($absolute_prefix) . Strings::unslash($path);
        }

        // If this is a directory, make sure it has a slash suffix
        if (file_exists($return)) {
            if (is_dir($return)) {
                $return = Strings::slash($return);
            }

        } else {
            if ($must_exist) {
                throw FileNotExistException::new(tr('The resolved path ":resolved" for the specified path ":directory" with prefix ":prefix" does not exist', [
                    ':prefix'    => $absolute_prefix,
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
        return Strings::fromReverse($this->source, '.');
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
        return str_ends_with($this->source, '.' . Strings::ensureStartsNotWith($extension, '.'));
    }


    /**
     * Returns the basename of this path
     *
     * @return string
     */
    public function getBasename(): string
    {
        return basename($this->source);
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
        return Strings::ensureEndsNotWith($this->source, '/') === Strings::ensureEndsNotWith($path, '/');
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
            return $this->source;
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
        $this->target = FsPathCore::absolutePath($target, null, false);

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
                    ':file' => $this->source,
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
        if (!$this->source) {
            // There is no path specified
            return false;
        }

        if (file_exists($this->source)) {
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
        return is_link(Strings::ensureEndsNotWith($this->source, '/'));
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
                    ':path' => $this->source,
                ]));
            }

            $this->source = static::absolutePath($this->getLinkTarget(true)->getSource());

            if ($this->isLink() and $all) {
                // The link target is a link too, and with $all set, we keep following!
                return $this->followLink($force, $all);
            }
        } else {
            if (!$force) {
                throw new NotASymlinkException(tr('Cannot follow file ":path", the file is not a symlink', [
                    ':path' => $this->source,
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

        if (empty($this->restrictions)) {
            Log::warning(tr('Skipping auto mount of path ":path" with class instance ":class" attempt because no filesystem restrictions were specified', [
                ':path'  => $this->source,
                ':class' => get_class($this),
            ]));
        } else {
            try {
                // Check if this path has a mount somewhere. If so, see if it needs auto-mounting
                $mount = FsMount::getForPath($this->source, $this->restrictions->getTheseWritable());

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
     * @see FsRestrictions::check() This function uses file location restrictions
     */
    public function delete(string|bool $clean_path = true, bool $sudo = false, bool $escape = true, bool $use_run_file = true): static
    {
        Log::action(tr('Deleting file ":file"', [':file' => $this->source]), 2);

        // Check filesystem restrictions
        $this->checkRestrictions(true)->checkWriteAccess();

        // Delete all specified patterns
        // Execute the rm command
        Process::new('rm', $this->restrictions)
               ->setSudo($sudo)
               ->setUseRunFile($use_run_file)
               ->setTimeout(10)
               ->addArgument($this->source, $escape)
               ->addArgument('-rf')
               ->executeNoReturn();

        // If specified to do so, clear the path upwards from the specified pattern
        if ($clean_path) {
            if ($clean_path === true) {
                // This will clean path until a non-empty directory is encountered.
                $clean_path = null;
            }

            FsDirectory::new(dirname($this->source), $this->restrictions->getParent())
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
            throw new RestrictionsException(tr('Cannot check restrictions for ":path" as it is a relative path with unknown directory prefix', [
                ':path' => $this->source,
            ]));
        }

        if (empty($this->restrictions)) {
            throw new FilesystemNoRestrictionsSetExceptions(tr('Cannot perform action, no filesystem restrictions have been set for this ":class" object', [
                ':class' => get_class($this)
            ]));
        }

        $this->restrictions->check($this->source, $write);

        if ($write) {
            return $this->checkWriteAccess();
        }

        return $this->checkReadAccess();
    }


    /**
     * Returns true if the path for this Path object is relative (and as such, starts NOT with /)
     *
     * @return bool
     */
    public function isRelative(): bool
    {
        if ($this->isOnDomain()) {
            return false;
        }

        return !str_starts_with($this->source, '/');
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
                ':file' => $this->source,
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

        return is_readable($this->source) and static::$read_enabled;
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

        return is_writable($this->source) and static::$write_enabled;
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

        $perms     = fileperms($this->source);
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

        $perms  = fileperms($this->source);
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
            if (is_dir($this->source)) {
                $mime = 'directory/directory';
            } else {
                try {
                    $r          = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
                    $this->mime = finfo_file($r, $this->source);
                    finfo_close($r);
                } catch (Exception $e) {
                    // We failed to get mimetype data. Find out why and throw exception
                    $this->checkReadable('', new FilesystemException(tr('Failed to get mimetype information for file ":file"', [
                        ':file' => $this->source,
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
            if (!file_exists(dirname($this->source))) {
                // The file doesn't exist and neither does its parent directory
                throw new FileNotExistException(tr('The ":type" type file ":file" cannot be read because the directory ":directory" does not exist', [
                    ':type'      => ($type ?: ''),
                    ':file'      => $this->source,
                    ':directory' => dirname($this->source),
                ]), $previous_e);
            }

            throw new FileNotExistException(tr('The ":type" type file ":file" cannot be read because it does not exist', [
                ':type' => ($type ? ' ' . $type : ''),
                ':file' => $this->source,
            ]), $previous_e);
        }

        if (!is_readable($this->source)) {
            throw new FileNotReadableException(tr('The ":type" type file ":file" cannot be read', [
                ':type' => ($type ? ' ' . $type : ''),
                ':file' => $this->source,
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
               ->addArgument($this->source)
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

            FsDirectory::new(dirname($this->source))->clearDirectory($clean_path, $sudo);
        }

        return $this;
    }


    /**
     * Moves this file to the specified target, will try to ensure target directory exists
     *
     * @param Stringable|string   $target
     * @param FsRestrictions|null $restrictions
     *
     * @return $this
     */
    public function movePath(Stringable|string $target, ?FsRestrictions $restrictions = null): static
    {
        $target = new FsPath($target, $restrictions ?? $this->restrictions);
        $target->makeAbsolute(must_exist: false);

        // Check the target directory exists, if so must be directory
        if ($target->exists()) {
            // Target exists. It has to be a directory where we can move into, or fail!
            if (!$target->isDirectory()) {
                throw FileExistsException::new(tr('The specified target ":target" already exists', [
                    ':target' => $target,
                ]));
            }

            // Target exists and is directory. Rename target to "this file in the target directory"
            $target = $target->appendPath($this->getBasename());

        } else {
            // Target does not exist
            if ($target instanceof FsDirectoryInterface) {
                // If the target is indicated to be a directory (because it ends with a slash) then it should be created
                $target->ensure();

            } else {
                // Ensure that the target parent directory exists
                $target->getParentDirectory()->ensure();
            }
        }

        // Check restrictions and execute move
        $this->checkRestrictions(true);
        $target->checkRestrictions(true);

        rename($this->source, $target->getSource());

        // Update this object path and restrictions to the target and we're done
        return $this->setSource($target->getSource())
                    ->setRestrictions($target->getRestrictions());
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
     * @return int|null
     */
    public function getMode(): int|null
    {
        return $this->getStat()['mode'];
    }


    /**
     * Returns the file mode for the object file in octal mode
     *
     * @return string|null
     */
    public function getOctalMode(): string|null
    {
        return decoct($this->getMode());
    }


    /**
     * Returns the file mode permission for the object file in octal form
     *
     * @return string
     */
    public function getModePermissions(): string
    {
        return substr($this->getOctalMode(), -3, 3);
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
            $stat = stat($this->source);
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

        if (!$this->source) {
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
                       $this->source,
                   ])
                   ->executeReturnArray();
        } else {
            chmod($this->source, $mode);
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
            $number = (int) substr($mode, $i, 1);
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

        foreach ($this->source as $pattern) {
            Process::new('chown', $this->restrictions)
                   ->setSudo(true)
                   ->addArgument($recursive ? '-R' : null)
                   ->addArgument($user . ':' . $group)
                   ->addArguments($this->source)
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
        $this->checkRestrictions(false);

        // If the object file exists and is writable, then we're done.
        if (is_readable($this->source)) {
            return true;
        }

        // From here the file is not writable. It may not exist, or it may simply not be writable. Let's continue...
        if (file_exists($this->source)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it readable.
            try {
                Log::warning(tr('The file ":file" :realis not readable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->source,
                    ':real' => $this->getRealPathLogString(),
                    ':mode' => $mode,
                ]));

                $this->restrictions->makeWritable();

                $this->chmod('u+w');

            } catch (ProcessesException) {
                throw new FileNotWritableException(tr('The file ":file" :realis not writable, and could not be made writable', [
                    ':file' => $this->source,
                    ':real' => $this->getRealPathLogString(),
                ]));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent directory exists.
        FsDirectory::new(dirname($this->source), $this->restrictions)->ensure();

        Log::action(tr('Creating non existing file ":file" with file mode ":mode"', [
            ':mode' => Strings::fromOctal($mode),
            ':file' => $this->source,
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
        if ($this->source === $this->getRealPath()) {
            return null;
        }

        return tr('(Real path ":directory") ', [':directory' => $this->getRealPath()]);
    }


    /**
     * Returns the absolute and real path for the specified path
     *
     * While PHP realpath() call may return false if the specified path does not exist, this method will both ensure the
     * parent directory of the specified path exists and a valid absolute and real path is always returned
     *
     * @param string                      $path
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     * @return string
     */
    public static function realPath(string $path, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): string
    {
        $real = FsPath::new($path, FsRestrictions::getWritable('/'))->makeAbsolute($absolute_prefix, $must_exist);

        if ($real->isOnDomain()) {
            // This is a domain:/file URL, we can't make this real
            return $path;
        }

        $base = $real->getBasename();
        $real = $real->getParentDirectory()->ensure()->getSource();
        $real = realpath($real);

        if (!$real) {
            throw new FilesystemException(tr('Failed to convert path ":path" in a realpath', [
                ':path' => $path,
            ]));
        }

        return Strings::slash($real) . $base;
    }


    /**
     * Wrapper for realpath() that won't crash with an exception if the specified string is not a real directory
     *
     * @return string string The real directory extrapolated from the specified $directory, if exists. False if
     *                whatever was specified does not exist.
     *
     * @example
     * code
     * show(FsFileFileInterface::new()->getRealPath());
     * showdie(FsFileFileInterface::new()->getRealPath());
     * /code
     *
     * This would result in
     * code
     * null
     * /bin
     * /code
     */
    public function getRealPath(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): string
    {
        return static::realPath($this->source, $absolute_prefix, $must_exist);
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
        if (is_writable($this->source)) {
            return true;
        }

        // From here, the file is not writable. It may not exist, or it may simply not be writable. Lets continue...
        if (file_exists($this->source)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The file ":file" :real is not writable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->source,
                    ':real' => $this->getRealPathLogString(),
                    ':mode' => $mode,
                ]));
                $this->chmod('u+w');
            } catch (ProcessesException) {
                throw new FileNotWritableException(tr('The file ":file" :real is not writable, and could not be made writable', [
                    ':file' => $this->source,
                    ':real' => $this->getRealPathLogString(),
                ]));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent directory exists.
        FsDirectory::new(dirname($this->source), $this->restrictions->getParent())
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
        return is_link($this->source);
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
     * Returns true if this file is stored on an encrypted filesystem
     *
     * @return bool
     */
    public function isEncrypted(): bool
    {
        return $this->getFilesystemObject()->isEncrypted();
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
     * @note Will return a NEW Path object (FsFileFileInterface or Directory, basically) for the specified target
     *
     * @param FsPathInterface|string      $target
     * @param FsPathInterface|string|bool $make_relative
     *
     * @return FsPathInterface
     */
    public function symlinkTargetFromThis(FsPathInterface|string $target, FsPathInterface|string|bool $make_relative = true): FsPathInterface
    {
        $target = new FsPath($target, $this->restrictions);

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
            if (Strings::ensureEndsNotWith($target->readLink(true)->getSource(), '/') === Strings::ensureEndsNotWith($this->getSource(), '/')) {
                // Symlink already exists and points to the same file. This is what we wanted to begin with, so all fine
                return $target;
            }

            throw new FileExistsException(tr('Cannot create symlink ":target" with link ":link", the file already exists and points to ":current" instead', [
                ':target'  => $target->getNormalizedPath(),
                ':link'    => Strings::ensureEndsNotWith($calculated_target->getSource(), '/'),
                ':current' => $target->readLink(true)
                                     ->getNormalizedPath(),
            ]));
        }

        // The target exists NOT as a link, but perhaps it might exist as a normal file or directory?
        if ($target->exists()) {
            throw new FileExistsException(tr('Cannot create symlink ":target" with link ":link", the file already exists as a ":type"', [
                ':target' => $target->getSource(),
                ':link'   => Strings::ensureEndsNotWith($calculated_target->getSource(), '/'),
                ':type'   => $target->getTypeName(),
            ]));
        }

        // Ensure that we have restriction access and target parent directory exists
        $target->checkRestrictions(true);
        $target->getParentDirectory()->ensure();

        // Symlink!
        try {
            symlink(Strings::ensureEndsNotWith($calculated_target->getSource(), '/'), $target->getSource());
        } catch (PhpException $e) {
            // Crap, what happened?
            if ($e->messageMatches('symlink(): FsFileFileInterface exists')) {
                throw new FileExistsException(tr('Cannot symlink ":this" to target ":target" because ":e"', [
                    ':this'   => $this->source,
                    ':target' => $target->getSource(),
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
        return str_starts_with($this->source, '/');
    }


    /**
     * Returns the relative path between the specified path and this object's path
     *
     * @param FsPathInterface|string      $target
     * @param FsPathInterface|string|bool $absolute_prefix
     *
     * @return FsPathInterface
     */
    public function getRelativePathTo(FsPathInterface|string $target, FsPathInterface|string|bool $absolute_prefix = null): FsPathInterface
    {
        $target      = static::new($target, $this->restrictions);
        $target_path = Strings::ensureEndsNotWith($target->getNormalizedPath($absolute_prefix), '/');
        $source_path = Strings::ensureEndsNotWith($this->getNormalizedPath($absolute_prefix), '/');
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
                return new FsPath('.');
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

        return FsPath::new(implode('/', $return), $target->getRestrictions());
    }


    /**
     * Returns a normalized path that has all ./ and ../ resolved
     *
     * @param Stringable|string|bool|null $absolute_prefix
     *
     * @return ?string string The real directory extrapolated from the specified $directory, if exists. False if
     *                 whatever was specified does not exist.
     *
     * @example
     * code
     * show(FsFileFileInterface::new()->getRealPath());
     * showdie(FsFileFileInterface::new()->getRealPath());
     * /code
     *
     * This would result in
     * code
     * null
     * /bin
     * /code
     */
    public function getNormalizedPath(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): ?string
    {
        return static::normalizePath($this->source, $absolute_prefix, $must_exist);
    }


    /**
     * Returns a normalized path that has all ./ and ../ resolved
     *
     * @param Stringable|string           $path
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     *
     * @return ?string string The real directory extrapolated from the specified $directory, if exists. False if
     *                 whatever was specified does not exist.
     *
     * @example
     * code
     * show(FsFileFileInterface::new()->getRealPath());
     * showdie(FsFileFileInterface::new()->getRealPath());
     * /code
     *
     * This would result in
     * code
     * null
     * /bin
     * /code
     */
    public static function normalizePath(Stringable|string $path, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): ?string
    {
        $path = trim((string) $path);

        if (InstanceCache::exists('path::normalizePath', $path)) {
            return InstanceCache::getLastChecked();
        }

        if ($path[0] !== '/') {
            // Ensure the path is absolute
            $path = static::absolutePath($path, $absolute_prefix, $must_exist);
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
     * @param FsPathInterface|string|bool $absolute_prefix
     *
     * @return FsPathInterface
     */
    public function readLink(FsPathInterface|string|bool $absolute_prefix = false): FsPathInterface
    {
        if (!$this->isLink()) {
            throw new FilesystemException(tr('Cannot readlink path ":path", it is not a symlink', [
                ':path' => $this->source,
            ]));
        }

        $path = readlink(Strings::ensureEndsNotWith($this->source, '/'));

        if ($absolute_prefix and !str_starts_with($path, '/')) {
            // Links are relative, make them absolute
            if (is_bool($absolute_prefix)) {
                $absolute_prefix = dirname($this->getSource()) . '/';
            }

            $path = Strings::slash($absolute_prefix) . $path;
        }

        // Return (possibly) relative links
        if (is_dir($path)) {
            return new FsDirectory($path, $this->restrictions, $this->getParentDirectory());
        }

        if (file_exists($path)) {
            return new FsFile($path, $this->restrictions, $this->getParentDirectory());
        }

        return new static($path, $this->restrictions, $this->getParentDirectory());
    }


    /**
     * Returns the name of the file type
     *
     * @return string
     */
    public function getTypeName(): string
    {
        if (is_link($this->source)) {
            return 'symlink';
        }

        if (is_dir($this->source)) {
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
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @return FsDirectoryInterface
     */
    public function getParentDirectory(?FsRestrictionsInterface $restrictions = null): FsDirectoryInterface
    {
        return FsDirectory::new(dirname($this->source), $restrictions ?? $this->restrictions?->getParent());
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
                ':file' => $this->source,
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
                ':file'   => $this->source,
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
//     *       If this is required, use FsFileFileInterface::symlink() instead. This is not a limitation of Phoundation, but of
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
                ':file' => $this->source,
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
                ':file' => $this->source,
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
            ':file' => $this->source,
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
                // FsFileFileInterface mode is not seekable
                throw new FileActionFailedException(tr('Failed to seek in file ":file" because file mode ":mode" does not allow seek', [
                    ':mode' => $this->open_mode->value,
                    ':file' => $this->source,
                ]));
            }

            // No idea why
            throw new FileActionFailedException(tr('Failed to seek in file ":file"', [
                ':file' => $this->source,
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
            $stream = fopen($this->source, $mode->value, false, $context);
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

        throw new FilesystemException(tr('Failed to open file ":file"', [':file' => $this->source]));
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
                ':file'   => $this->source,
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
            if (!file_exists(dirname($this->source))) {
                // The file doesn't exist and neither does its parent directory
                throw new FileNotExistException(tr('The:type file ":file" cannot be written because it does not exist and neither does the parent directory ":directory"', [
                    ':type'      => ($type ? '' : ' ' . $type),
                    ':file'      => $this->source,
                    ':directory' => dirname($this->source),
                ]), $previous_e);
            }
            // FsFileFileInterface doesn't exist, check if the parent directory is writable so that the file can be created
            FsDirectory::new(dirname($this->source), $this->restrictions)
                     ->checkWritable($type, $previous_e);
        } elseif (!is_writable($this->source)) {
            throw new FileNotWritableException(tr('The:type file ":file" cannot be written', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->source,
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
        $exists = file_exists($this->source);
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
                    ':file' => $this->source,
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
        // Make sure the file path exists. NOTE: FsRestrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->checkRestrictions(false)
             ->checkClosed('getContents')
             ->mountIfNeeded();

        try {
            $data = file_get_contents($this->source, $use_include_path, $context, $offset, $length);

        } catch (PhpException $e) {
            $this->checkReadable('', new FilesystemException(tr('Failed to get contents of file ":file" as string', [
                ':file' => $this->source,
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
        // Make sure the file path exists. NOTE: FsRestrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->checkRestrictions(false)
             ->checkClosed('getContents')
             ->mountIfNeeded();

        try {
            $data = file($this->source, $flags, $context);
        } catch (PhpException $e) {
            $this->checkReadable('', new FilesystemException(tr('Failed to get contents of file ":file" as array', [
                ':file' => $this->source,
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
        // Make sure the file path exists. NOTE: FsRestrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        $this->checkRestrictions(true)
             ->checkClosed('putContents')
             ->mountIfNeeded();

        FsDirectory::new(dirname($this->source), $this->restrictions->getParent()->getParent())->ensure();

        file_put_contents($this->source, $data, $flags, $context);

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
                    ':file' => $this->source,
                ]));
            }
        }

        if ($this->isOpen()) {
            // Yeah, so it exists anyway because we have it open. Perhaps the file was removed while open, so the inode
            // is still there?
            if (!$force) {
                throw new FileExistsException(tr('Cannot create file ":file", it does not exist, but is open. Perhaps the file was deleted but the open inode is still there?', [
                    ':file' => $this->source,
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
            touch($this->source);
        } elseif ($this instanceof FsDirectoryInterface) {
            // If this is supposed to be a directory, create it
            return $this->ensure();
        } else {
            // Create it by touching it. Or something like that
            touch($this->source);
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
        FsDirectory::new(dirname($this->source), $this->restrictions)->ensure();

        // Open target file
        $this->open(EnumFileOpenMode::writeOnlyAppend);

        // Open each source file
        foreach (Arrays::force($sources, null) as $source) {
            try {
                $source = FsFile::new($source, $this->restrictions)->open(EnumFileOpenMode::readOnly);

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
                ':file' => $this->source,
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
                ':file' => $this->source,
            ]));
        }

        return $this;
    }


    /**
     * Will overwrite the file with random data before deleting it
     *
     * @param int  $passes
     * @param bool $randomized
     * @param int  $block_size
     *
     * @return $this
     */
    public function shred(int $passes = 3, bool $randomized = false, int $block_size = 4096): static
    {
        return $this->doInitialize('random', $block_size, $randomized, true, $passes);
    }


    /**
     * Fills this file, first to last byte, with the specified type of data
     *
     * Current supported types of data are: zero, random
     *
     * @param string $type
     * @param int    $block_size
     * @param bool   $randomized
     *
     * @return static
     */
    public function initialize(string $type, int $block_size = 4096, bool $randomized = false): static
    {
        return $this->doInitialize($type, $block_size, $randomized, true);
    }


    /**
     * Fills this file, first to last byte, with the specified type of data
     *
     * Current supported types of data are: zero, random
     *
     * @param string $type
     * @param int    $block_size
     * @param bool   $randomized
     *
     * @return static
     */
    protected function doInitialize(string $type, int $block_size, bool $randomized, bool $delete, int $passes = 1): static
    {
        if (($passes < 1) or ($passes > 20)) {
            throw new OutOfBoundsException(tr('Invalid number of passes ":passes" specified, must be between 1 and 20', [
                ':passes' => $passes,
            ]));
        }

        if ($this instanceof FsDirectoryInterface) {
            // Recurse into sub directories, initialize all files
            throw new UnderConstructionException();
        }

        // Calculate the number of blocks depending on file size and block size, and get a block range array
        $size   = $this->getSize();
        $count  = (int) floor($size / $block_size);
        $blocks = Arrays::range(0, $count - 1, $block_size); // -1 for 0 start
        $rest   = $size - ($count * $block_size);

        if ($rest) {
            $blocks[] = $rest; // One extra block containing rest 0 - block_size bytes
        }

        // Open and delete, so that the inode will be gone, but the file blocks won't be released just yet
        $this->open(EnumFileOpenMode::writeOnly);

        if ($delete) {
            // Delete the file right after opening, removing the inode already while we shred its contents.
            $this->delete();
        }

        Log::action(tr('Initializing file ":file" with ":type" data', [
            ':file' => $this->source,
            ':type' => $type,
        ]), 4);

        // Always start with overwriting the first block of the file
        $this->seek(0)->write($this->getInitBlock($type, $block_size));
        unset($blocks[0]);

        for ($block = 0; $block < $count; $block++) {
            if ($randomized) {
                $location = array_rand($blocks);

            } else {
                $location = array_key_first($blocks);
            }

            // Write the block
            $this->seek($location * $block_size)
                 ->write($this->getInitBlock($type, $blocks[$location]));

            // Remove this block from the list, continue writing
            unset($blocks[$location]);
        }

        $this->close();
        return $this;
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
        if ($this->isDirectory()) {
            // Return the size of this entire directory
            return FsDirectory::new($this)->getSize($recursive);
        }

        // Return the size of a single file
        return filesize($this->source);
    }


    /**
     * Returns the device path of the filesystem where this file is stored
     *
     * @return string
     */
    public function getMountDevice(): string
    {
        $this->checkExists();
        $mounts = FsMounts::listMountTargets();

        foreach ($mounts as $path => $mount) {
            if (str_starts_with($this->source, $path)) {
                return $mount['source'];
            }
        }

        throw new MountLocationNotFoundException(tr('Failed to find a mount location for the path ":path"', [
            ':path' => $this->source,
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
                    ':file' => $this->source,
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
        return Find::new($this->source);
    }


    /**
     * Replaces the current path by moving it out of the way and moving the target in its place, then deleting the
     * original
     *
     * @param FsPathInterface|string $target
     *
     * @return FsPathInterface
     */
    public function replaceWithPath(FsPathInterface|string $target): FsPathInterface
    {
        $target = FsPath::new($target);

        // Move the old out of the way, push the new in, delete the current
        if ($this->exists()) {
            $new = clone $this;
            $this->rename(FsDirectory::getTemporaryObject());
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
        $result = rename($this->source, $to, $context);

        if (!$result) {
            throw new FileRenameException(tr('Failed to rename file or directory ":file" to ":to"', [
                ':file' => $this->source,
                ':to'   => $to,
            ]));
        }

        $this->source = $to;

        if ($to_filename instanceof FsPathInterface) {
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
                ':path' => $this->source,
                ':type' => $this->getTypeName(),
            ]));
        }

        if ($this->getLinkTarget() != $target) {
            throw new FileNotSymlinkException(tr('The path ":path" must be symlinked to ":target" but is symlinked to ":instead" instead', [
                ':path'    => $this->source,
                ':target'  => $target,
                ':instead' => $this->getLinkTarget(),
            ]));
        }

        return $this;
    }


    /**
     * Wrapper for Path::readlink()
     *
     * @param FsPathInterface|string|bool $absolute
     *
     * @return FsPathInterface
     */
    public function getLinkTarget(FsPathInterface|string|bool $absolute = false): FsPathInterface
    {
        return $this->readLink($absolute);
    }


    /**
     * Returns a FsPathInterface object with the specified path prepended to this path
     *
     * @param FsPathInterface|string      $path
     * @param Stringable|string|bool|null $absolute_prefix
     *
     * @return FsFileInterface
     */
    public function prependPath(FsPathInterface|string $path, Stringable|string|bool|null $absolute_prefix = false): FsPathInterface
    {
        $path = Strings::ensureEndsWith((string) $path, '/') . $this->getSource();

        return FsPath::new($path, $this->restrictions, $absolute_prefix);
    }


    /**
     * Copies all directories as directories and all files as symlinks in the tree starting at this objects path to the
     * specified target,
     *
     * Directories will remain directories, all files will be symlinks
     *
     * @param FsPathInterface|string       $target
     * @param FsPathInterface|string|null  $alternate_path
     * @param FsRestrictionsInterface|null $restrictions
     * @param bool                         $rename
     *
     * @return $this
     */
    public function symlinkTreeToTarget(FsPathInterface|string $target, FsPathInterface|string|null $alternate_path = null, ?FsRestrictionsInterface $restrictions = null, bool $rename = false): FsPathInterface
    {
        // Ensure target is a Path object with restrictions
        $target = FsPath::new($target, $restrictions ?? $this->restrictions);

        if (empty($alternate_path)) {
            // We'll create the symlinks in the same directory as where we're linking from. Use Target object
            $alternate_path = clone $target;

        } else {
            // We'll create the symlink in a different directory than where we're linking from. Ensure alternate path is
            // a Path object
            $alternate_path = FsPath::new($alternate_path, $target->restrictions);
        }

        if ($this->isDirectory()) {
            // Source is a directory, target must be a directory too, process all files in this directory
            $dir_target         = FsDirectory::new($target);
            $dir_alternate_path = FsDirectory::new($alternate_path)->ensure();

            // Go over each file in this directory.
            // If the file is a directory then create it in the target, if it is a normal file, then create a symlink
            foreach ($this->getFilesObject() as $path) {
                // Get the section that we'll be working with
                $section = Strings::ensureStartsNotWith(Strings::from($path->getSource(), $this->source), '/');

                if ($path->isDirectory()) {
                    $path->symlinkTreeToTarget($dir_target->addDirectory($section), $dir_alternate_path->addDirectory($section), rename: $rename);
                } else {
                    $number = null;

                    while (true) {
                        try {
                            // Create symlink for only this file
                            $link = $dir_target->addFile($section)->getRelativePathTo($path);
                            $dir_alternate_path->addFile($section . $number)->symlinkThisToTarget($link);
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
     * Returns a FsFilesInterface object that will contain all the files under this current path
     *
     * @param bool $reload
     *
     * @return FsFilesInterface
     */
    public function getFilesObject(bool $reload = false): FsFilesInterface
    {
        $this->checkRestrictions(false);

        if (empty($this->files) or $reload) {
            $this->checkReadable('directory');

            if ($this->isDirectory()) {
                // Load all files in this directory
                $this->files = FsFiles::new(new FsDirectory($this), scandir($this->source), $this->restrictions)->setParent($this);

            } else {
                // This is a file, so there are no files beyond THIS file.
                $this->files = FsFiles::new($this->getParentDirectory(), [$this->source], $this->restrictions)->setParent($this);
            }
        }

        return $this->files;
    }


    /**
     * Makes this path a symlink that points to the specified target.
     *
     * @note Will return a NEW Path object (FsFileFileInterface or Directory, basically) for the specified target
     *
     * @param FsPathInterface|string      $target
     * @param FsPathInterface|string|bool $make_relative
     *
     * @return FsPathInterface
     */
    public function symlinkThisToTarget(FsPathInterface|string $target, FsPathInterface|string|bool $make_relative = true): FsPathInterface
    {
        $target = new FsPath($target, $this->restrictions);

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
            if (Strings::ensureEndsNotWith($this->readLink(true)->getSource(), '/') === Strings::ensureEndsNotWith($target->getSource(), '/')) {
                // Symlink already exists and points to the same file. This is what we wanted to begin with, so all fine
                return $target;
            }

            throw new FileExistsException(tr('Cannot create symlink ":target" with link ":link", the file already exists and points to ":current" instead', [
                ':target'  => $this->getNormalizedPath(),
                ':link'    => $calculated_target->getSource(),
                ':current' => $this->readLink(true)
                                   ->getNormalizedPath(),
            ]));
        }

        // The target exists NOT as a link, but perhaps it might exist as a normal file or directory?
        if ($this->exists()) {
            throw new FileExistsException(tr('Cannot create symlink ":source" that points to ":target", the file already exists as a ":type"', [
                ':target' => $calculated_target->getSource(),
                ':source' => $this->getNormalizedPath(),
                ':type'   => $this->getTypeName(),
            ]));
        }

        // Ensure that we have restriction access and target parent directory exists
        $this->checkRestrictions(true);
        $this->getParentDirectory()->ensure();

        // Symlink!
        try {
            symlink(Strings::ensureEndsNotWith($calculated_target->getSource(), '/'), $this->getSource());
        } catch (PhpException $e) {
            // Crap, what happened?
            if ($e->messageMatches('symlink(): FsFileFileInterface exists')) {
                throw new FileExistsException(tr('Cannot symlink ":this" to target ":target" because ":e"', [
                    ':this'   => $this->getSource(),
                    ':target' => $target->getSource(),
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
                        ->setExecutionDirectory(new FsDirectory($this))
                        ->setPath($this)
                        ->setType('l')
                        ->setCallback(function ($file) use ($clean) {
                            FsPath::new($file, $this->restrictions)->delete(true);
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

        // Make sure the file path exists. NOTE: FsRestrictions MUST be at least 2 levels above to be able to generate the
        // PARENT directory IN the PARENT directory OF the PARENT!
        FsDirectory::new(dirname($this->source), $this->restrictions->getParent()->getParent())->ensure();

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

        $this->requirements->check($this->source);
    }


    /**
     * Returns the filesystem for the current path
     *
     * @return FsFilesystemInterface
     */
    public function getFilesystemObject(): FsFilesystemInterface
    {
        try {
            $results = Df::new($this->isDirectory() ? new FsDirectory($this) : $this->getParentDirectory())
                         ->executeNoReturn()
                         ->getResults();

        } catch (ProcessFailedException $e) {
            $this->checkReadable('block-file', $e);
            throw $e;
        }

        $filesystem = null;

        foreach ($results as $result) {
            if (str_starts_with($this->source, $result['mountedon'])) {
                if ($filesystem) {
                    if (strlen($filesystem['mountedon']) > strlen($result['mountedon'])) {
                        continue;
                    }
                }

                $filesystem = $result;
            }
        }

        return new FsFilesystem($filesystem['filesystem']);
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
    public static function getReadEnabled(): bool
    {
        return static::$read_enabled;
    }


    /**
     * Returns true if file write access is available
     *
     * @return bool
     * @todo Add Core::fileReadEnabled() checks in here
     */
    public static function getWriteEnabled(): bool
    {
        return static::$write_enabled;
    }


    /**
     * Checks if write access is available
     *
     * @return static
     * @todo Add Core::fileWriteEnabled() checks in here
     */
    public function checkWriteAccess(): static
    {
        if (!static::getWriteEnabled()) {
            throw new FileNotWritableException(tr('The file ":file" cannot be written because all write access has been disabled', [
                ':file' => $this->source
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
        if (!static::getReadEnabled()) {
            throw new FileNotReadableException(tr('The file ":file" cannot be read because all read access has been disabled', [
                ':file' => $this->source
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
        return match ($mode) {
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
    public function ensureParentDirectory(string|int|null $mode = null, ?bool $clear = false, bool $sudo = false): static
    {
        $this->getParentDirectory()->ensure($mode, $clear, $sudo);
        return $this;
    }


    /**
     * Returns a path that is absolute, normalized, and real (if possible).
     *
     * @param string                      $path
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     *
     * @return string
     */
    public static function resolve(string $path, Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): string
    {
        $path = static::normalizePath($path, $absolute_prefix, $must_exist);
        $path = static::realPath($path);

        return $path;
    }


    /**
     * Returns an FsInfoInterface object  for this path, which can be used to gather and or display information about
     * this path
     *
     * @return FsInfoInterface
     */
    public function getInfoObject(): FsInfoInterface
    {
        return new FsInfo($this);
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
     * Return true if the specified mimetype is for a binary file or false if it is for a text file
     *
     * @return bool True if the file is a text file, false if not
     */
    public function isBinary(): bool
    {
        $this->primarySecondaryMimeType($primary, $secondary);

        // Check the mimetype data
        switch ($primary) {
            case 'text':
                // Plain text
                return false;

            default:
                switch ($secondary) {
                    case 'json':
                    case 'ld+json':
                    case 'svg+xml':
                    case 'x-csh':
                    case 'x-sh':
                    case 'xhtml+xml':
                    case 'xml':
                    case 'vnd.mozilla.xul+xml':
                        // This is all text
                        return false;
                }
        }

        // This is binary
        return true;
    }


    /**
     * Returns primary and secondary mime types variables with mime data for this file
     *
     * @param $primary
     * @param $secondary
     *
     * @return void
     * @throws Exception
     */
    protected function primarySecondaryMimeType(&$primary, &$secondary): void
    {
        $mimetype = $this->getMimetype();

        if (!str_contains($mimetype, '/')) {
            throw new FilesystemException(tr('Invalid primary mimetype data ":primary" encountered. It should be in primary/secondary format', [
                ':primary' => $mimetype,
            ]));
        }

        $primary   = Strings::until($mimetype, '/');
        $secondary = Strings::from($mimetype, '/');
    }


    /**
     * Returns the file owner UID
     *
     * @return int
     */
    public function getOwnerUid(): int
    {
        return fileowner($this->source);
    }


    /**
     * Returns the file owner UID
     *
     * @return int
     */
    public function getGroupUid(): int
    {
        return filegroup($this->source);
    }


    /**
     * Returns the file owner UID
     *
     * @return string|null
     */
    public function getOwnerName(): ?string
    {
        $owner = $this->getOwnerUid();
        $owner = posix_getpwuid($owner);

        if ($owner) {
            return $owner['name'];
        }

        return null;
    }


    /**
     * Returns the file owner UID
     *
     * @return string|null
     */
    public function getGroupName(): ?string
    {
        $group = $this->getGroupUid();
        $group = posix_getpwuid($group);

        if ($group) {
            return $group['name'];
        }

        return null;
    }


    /**
     * Returns true if this file has the exact same owner UID as the process UID
     *
     * @return bool
     */
    public function uidMatchesPuid(): bool
    {
        return $this->getOwnerUid() === Core::getProcessUid();
    }


    /**
     * Returns true if this file has the exact same group UID as the process UID
     *
     * @return bool
     */
    public function gidMatchesPuid(): bool
    {
        return $this->getGroupUid() === Core::getProcessUid();
    }


    /**
     * Returns true if this path is in the specified directory
     *
     * To be in the specified directory, this path must start with the directory path.
     *
     * @param FsDirectoryInterface|string $directory
     * @return bool
     */
    public function isInDirectory(FsDirectoryInterface|string $directory): bool
    {
        if ($this->isOnDomain()) {
            return $this->isInDomain($directory);
        }

        if (is_string($directory)) {
            return str_starts_with($this->source, $directory);
        }

        return str_starts_with($this->source, $directory->getSource());
    }


    /**
     * Returns true if this objects domain falls in the specified domain
     *
     * @note: This method supports * domains
     *
     * @param FsDirectoryInterface|string $domain
     * @return bool
     */
    public function isInDomain(FsDirectoryInterface|string $domain): bool
    {
        if ($domain instanceof FsDirectoryInterface){
            $domain = $domain->getSource();
        }

        if (str_starts_with($this->source, $domain)) {
            return true;
        }

        // The exact start of domain didn't match, are all domains allowed?
        $path   = Strings::from($domain, ':');
        $domain = Strings::until($domain, ':');

        if ($domain === '*') {
            // All domains match, now check the rest of the path
            return str_starts_with(Strings::from($this->source, ':'), $path);
        }

        return false;
    }


    /**
     * Returns true if the specified path is on a domain
     *
     * @param string $path
     * @return bool
     */
    public static function onDomain(string $path): bool
    {
        if (str_contains($path, ':')) {
            $host = Strings::until($path, ':');

            if (filter_var($host, FILTER_VALIDATE_DOMAIN) or ($host === '*')) {
                return true;
            }
        }

        return false;
    }


    /**
     * Returns true if this objects' path is on a domain
     *
     * @return bool
     */
    public function isOnDomain(): bool
    {
        return static::onDomain($this->source);
    }


    /**
     * Returns the domain part for this domain path
     *
     * @return string
     */
    public function getDomain(): string
    {
        $domain = Strings::until($this->source, ':', needle_required: true);

        if ($domain) {
            return $domain;
        }

        // No domain?
        throw new PathNotDomainException(tr('Cannot return domain from path ":path", it is not a domain path', [
            ':path' => $this->source
        ]));
    }
}
