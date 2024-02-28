<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\DirectoryException;
use Phoundation\Filesystem\Exception\DirectoryNotDirectoryException;
use Phoundation\Filesystem\Exception\DirectoryNotMountedException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Interfaces\DirectoryInterface;
use Phoundation\Filesystem\Interfaces\ExecuteInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\FilesInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Filesystem\Mounts\Mounts;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Commands\Tar;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * Directory class
 *
 * This class represents a single directory and contains various methods to manipulate directories.
 *
 * It can rename, copy, traverse, mount, and much more
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Directory extends Path implements DirectoryInterface
{
    /**
     * Temporary directory (public data), if set
     *
     * @var DirectoryInterface|null $temp_directory_private
     */
    protected static ?DirectoryInterface $temp_directory_private = null;
    /**
     * Temporary directory (private data), if set
     *
     * @var DirectoryInterface|null $temp_directory_public
     */
    protected static ?DirectoryInterface $temp_directory_public = null;


    /**
     * Returns the path
     *
     * @param bool $remove_terminating_slash
     * @return string|null
     */
    public function getPath(bool $remove_terminating_slash = false): ?string
    {
        if ($remove_terminating_slash) {
            if ($this->path === '/') {
                // Root path is just what it is, it is a slash, don't remove it!
                return '/';
            }

            return Strings::endsNotWith($this->path, '/');
        }

        return $this->path;
    }


    /**
     * @inheritDoc
     */
    public function getRealPath(): ?string
    {
        $path = parent::getRealPath();

        if ($path) {
            return Strings::slash($path);
        }

        return null;
    }


    /**
     * Directory class constructor
     *
     * @param mixed $source
     * @param array|string|Restrictions|null $restrictions
     * @param bool $make_absolute
     */
    public function __construct(mixed $source = null, array|string|Restrictions|null $restrictions = null, bool $make_absolute = false)
    {
        parent::__construct($source, $restrictions, $make_absolute);

        $this->path = Strings::slash($this->path);

        if (file_exists($this->path)) {
            // This exists, it must be a directory!
            if (!is_dir($this->path)) {
                throw new DirectoryNotDirectoryException(tr('The specified path ":path" is not a directory', [
                    ':path' => $source
                ]));
            }
        }
    }


    /**
     * Returns an Execute object to execute callbacks on each file in specified directories
     *
     * @return ExecuteInterface
     */
    public function execute(): ExecuteInterface
    {
        $this->path = Strings::slash($this->path);
        return new Execute($this->path, $this->restrictions);
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
        $this->path = Strings::slash($this->path);
        parent::checkReadable($type, $previous_e);

        if (!is_dir($this->path)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be read because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->path
            ]), $previous_e);
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
        $this->path = Strings::slash($this->path);
        parent::checkWritable($type, $previous_e);

        if (!is_dir($this->path)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be written because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->path
            ]), $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

        return $this;
    }


    /**
     * Ensures existence of the specified directory
     *
     * @param string|null $mode octal $mode If the specified $this->directory does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified directory already exists, it will be deleted and then re-created
     * @param bool $sudo
     * @return static
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package file
     * @version 2.4.16: Added documentation
     *
     */
    public function ensure(?string $mode = null, ?bool $clear = false, bool $sudo = false): static
    {
        $this->path = Strings::slash($this->path);
        static::validateFilename($this->path);

        $mode = Config::get('filesystem.mode.directories', 0750, $mode);

        if ($clear) {
            // Delete the currently existing directory, so we can  be sure we have a clean directory to work with
            File::new($this->path, $this->restrictions)->deletePath(false, $sudo);
        }

        if (!file_exists(Strings::unslash($this->path))) {
            // The complete requested directory doesn't exist. Try to create it, but directory by directory so that we can
            // correct issues as we run in to them
            $dirs       = explode('/', Strings::startsNotWith($this->path, '/'));
            $this->path = '';

            foreach ($dirs as $id => $dir) {
                $this->path .= '/' . $dir;

                if (file_exists($this->path)) {
                    if (!is_dir($this->path)) {
                        // Some normal file is in the way. Delete the file, and retry
                        File::new($this->path, $this->restrictions)->deletePath(false, $sudo);
                        return $this->ensure($mode, $clear, $sudo);
                    }

                    continue;

                } elseif (is_link($this->path)) {
                    // This is a dead symlink, delete it
                    File::new($this->path, $this->restrictions)->deletePath(false, $sudo);
                }

                try {
                    // Make sure that the parent directory is writable when creating the directory
                    Directory::new(dirname($this->path), $this->restrictions->getParent($id + 1))->execute()
                        ->setMode(0770)
                        ->onDirectoryOnly(function() use ($mode) {
                            mkdir($this->path, $mode);
                        });

                } catch(RestrictionsException $e) {
                    throw $e;

                } catch(Throwable $e) {
                    // It sometimes happens that the specified directory was created just in between the file_exists and
                    // mkdir
                    if (!file_exists($this->path)) {
                        throw DirectoryException::new(tr('Failed to create directory ":directory"', [
                            ':directory' => $this->path
                        ]), $e)->addData(['directory' => $this->path]);
                    }

                    // We're okay, the directory already exists
                }
            }

        } elseif (!is_dir($this->path)) {
            // Some other file is in the way. Delete the file, and retry.
            // Ensure that the "file" is not accidentally specified as a directory ending in a /
            File::new(Strings::endsNotWith($this->path, '/'), $this->restrictions)->deletePath(false, $sudo);
            return $this->ensure($mode, $clear, $sudo);
        }

        return $this;
    }


    /**
     * Returns true if the object directories are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        $this->path = Strings::slash($this->path);
        $this->exists();

        if (!is_dir($this->path)) {
            $this->checkReadable();

            throw new DirectoryNotDirectoryException(tr('The specified directory ":directory" is not a directory', [
                ':directory' => $this->path
            ]));
        }

        // Start reading the directory.
        $handle = opendir($this->path);

        while (($file = readdir($handle)) !== false) {
            // Skip . and ..
            if (($file == '.') or ($file == '..')) {
                continue;
            }

            // Yeah, this has files
            closedir($handle);
            return false;
        }

        // Yay, no files encountered!
        closedir($handle);
        return true;
    }


    /**
     * Delete the directory, and each parent directory until a non-empty directory is encountered
     *
     * @param string|null $until_directory If specified as a directory, the method will stop deleting upwards when the specified
     *                                directory is encountered as well. If specified as true, the method will continue
     *                                deleting until either Restrictions stops it, or a non empty directory has been
     *                                encountered
     * @param bool $sudo
     * @param bool $use_run_file
     * @return void
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
     *
     */
    public function clearDirectory(?string $until_directory = null, bool $sudo = false, bool $use_run_file = true): void
    {
        $this->path = Strings::slash($this->path);

        try {
            while ($this->path) {
                // Restrict location access
                $this->restrictions->check($this->path, true);

                if (!file_exists($this->path)) {
                    // This section does not exist, jump up to the next section above
                    $this->path = dirname($this->path);
                    continue;
                }

                if (!is_dir($this->path)) {
                    // This is a normal file, we only delete directories here!
                    throw new OutOfBoundsException(tr('Not clearing directory ":directory", it is not a directory', [
                        ':directory' => $this->path
                    ]));
                }

                if ($until_directory and ($this->path === $until_directory)){
                    // We've cleaned until the requested directory, so we're good!
                    break;
                }

                if (!Directory::new($this->path, $this->restrictions)->isEmpty()) {
                    // Do not remove anything more, there is contents here!
                    break;
                }

                // Remove this entry and continue;
                try {
                    $this->deletePath(false, $sudo, use_run_file: $use_run_file);

                }catch(Exception $e) {
                    // The directory WAS empty, but cannot be removed

                    // In all probability, a parallel process added a new content in this directory, so it's no longer empty.
                    // Just register the event and leave it be.
                    Log::warning(tr('Failed to remove empty pattern ":pattern" with exception ":e"', [
                        ':pattern' => $this->path,
                        ':e'       => $e
                    ]));

                    break;
                }

                // Go one entry up, check if we're still within restrictions, and continue deleting
                $this->path = dirname($this->path) . '/';
            }
        } catch (RestrictionsException) {
            // We're out of our territory, stop scanning!
        }
    }


    /**
     * Creates a random directory in specified base directory (If it does not exist yet), and returns that directory
     *
     * @param bool $single
     * @param int $length
     * @return string
     */
    public function createTarget(?bool $single = null, int $length = 0): string
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, true);
        $this->exists();

        // Check configuration
        if (!$length) {
            $length = Config::getInteger('filesystem.target-directory.size', 8);
        }

        if ($single === null) {
            $single = Config::getBoolean('filesystem.target-directory.single', false);
        }

        $this->path = Strings::unslash(Directory::new($this->path, $this->restrictions)->ensure()->getPath());

        if ($single) {
            // Assign directory in one dir, like abcde/
            $this->path = Strings::slash($this->path) . substr(uniqid(), -$length, $length);

        } else {
            // Assign directory in multiple dirs, like a/b/c/d/e/
            foreach (str_split(substr(uniqid(), -$length, $length)) as $char) {
                $this->path .= DIRECTORY_SEPARATOR . $char;
            }
        }

        // Ensure again to be sure the target directories too have been created
        return Strings::slash(Directory::new($this->path, $this->restrictions)->ensure()->getPath());
    }


    /**
     * Return all files in this directory
     *
     * @return FilesInterface The files
     */
    public function list(): FilesInterface
    {
        $return = [];
        $list   = Arrays::removeValues(scandir($this->path), ['.', '..']);

        foreach ($list as $value) {
            $return[$value] = $this->path . $value;
        }

        return Files::new($return, $this->restrictions);
    }


    /**
     * Return all files in a directory that match the specified pattern with optional recursion.
     *
     * @param array|string|null $filters One or multiple regex filters
     * @param boolean $recursive If set to true, return all files below the specified directory, including in sub-directories
     * @return array The matched files
     */
    public function listTree(array|string|null $filters = null, bool $recursive = true): array
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, false);
        $this->exists();

        $return = [];
        $fh     = opendir($this->path);

        // Go over all files
        while (($filename = readdir($fh)) !== false) {
            // Loop through the files, skipping . and .. and recursing if necessary
            if (($filename == '.') or ($filename == '..')) {
                continue;
            }

            // Does the file match the specified pattern?
            if ($filters) {
                foreach (Arrays::force($filters, null) as $filter) {
                    $match = preg_match($filter, $filename);

                    if (!$match) {
                        // File did NOT match this filter
                        continue 2;
                    }
                }
            }

            // Get the complete file directory
            $file = Strings::slash($this->path) . $filename;

            // Add the file to the list. If the file is a directory, then recurse instead. Do NOT add the directory
            // itself, only files!
            if (is_dir($file) and $recursive) {
                $return = array_merge($return, Directory::new($file, $this->restrictions)->listTree());

            } else {
                $return[] = $file;
            }
        }

        closedir($fh);
        return $return;
    }


    /**
     * Pick and return a random file name from the specified directory
     *
     * @note This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
     *
     * @return string A random file from a random directory from the object directories
     */
    public function random(): string
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, false);
        $this->exists();

        $this->path = Arrays::getRandomValue($this->path);
        $files      = scandir($this->path);

        Arrays::unsetValue($files, '.');
        Arrays::unsetValue($files, '..');

        if (!$files) {
            throw new FilesystemException(tr('The specified directory ":directory" contains no files', [
                ':directory' => $this->path
            ]));
        }

        return Strings::slash($this->path) . Arrays::getRandomValue($files);
    }


    /**
     * Scan the entire object directory STRING upward for the specified file.
     *
     * If the object file doesn't exist in the specified directory, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     * @return string|null
     */
    public function scanUpwardsForFile(string $filename): ?string
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, false);
        $this->exists();

        while (strlen($this->path) > 1) {
            $this->path = Strings::slash($this->path);

            if (file_exists($this->path . $filename)) {
                // The requested file is found! Return the directory where it was found
                return $this->path;
            }

            $this->path = dirname($this->path);
        }

        return null;
    }


    /**
     * Returns true if the specified file exists in this directory
     *
     * If the object file doesn't exist in the specified directory, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     * @return bool
     */
    public function hasFile(string $filename): bool
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, false);
        $this->exists();

        return file_exists($this->path . Strings::startsNotWith($filename, '/'));
    }


    /**
     * Returns the total size in bytes of the tree under the specified directory
     *
     * @return int The number of bytes this tree takes
     */
    public function treeFileSize(): int
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, false);
        $this->exists();

        $return = 0;

        foreach (scandir($this->path) as $file) {
            if (($file == '.') or ($file == '..')) continue;

            if (is_dir($this->path . $file)) {
                // Recurse
                $return += Directory::new($this->path . $file, $this->restrictions)->treeFileSize();

            } else {
                $return += filesize($this->path . $file);
            }
        }

        return $return;
    }


    /**
     * Returns the number of files under the object directory (directories not included in count)
     *
     * @return int The number of files
     */
    public function treeFileCount(): int
    {
        // Check filesystem restrictions
        $this->path = Strings::slash($this->path);
        $this->restrictions->check($this->path, false);
        $this->exists();

        $return = 0;

        foreach (scandir($this->path) as $file) {
            if (($file == '.') or ($file == '..')) continue;

            if (is_dir($this->path . $file)) {
                $return += Directory::new($this->path . $file, $this->restrictions)->treeFileCount();

            } else {
                $return++;
            }
        }

        return $return;
    }


    /**
     * Returns PHP code statistics for this directory
     *
     * @param bool $recurse
     * @return array
     */
    public function getPhpStatistics(bool $recurse = false): array
    {
        $return = [
            'files_statistics' => [],
            'total_statistics' => [],
            'file_types'   => [
                'css'      => 0,
                'ini'      => 0,
                'js'       => 0,
                'html'     => 0,
                'php'      => 0,
                'xml'      => 0,
                'yaml'     => 0,
                'unknown'  => 0
            ],
            'file_extensions' => [
                'css'     => 0,
                'scss'    => 0,
                'ini'     => 0,
                'js'      => 0,
                'json'    => 0,
                'html'    => 0,
                'htm'     => 0,
                'php'     => 0,
                'phps'    => 0,
                'phtml'   => 0,
                'xml'     => 0,
                'yaml'    => 0,
                'yml'     => 0,
                'unknown' => 0
            ]
        ];

        $this->execute()
            ->setRecurse($recurse)
            ->setWhitelistExtensions(array_keys($return['file_extensions']))
            ->onFiles(function(string $file) use (&$return) {
                try {
                    $extension = File::new($file)->getExtension();

                    // Add file type and extension statistics
                    switch ($extension) {
                        case 'css':
                            // no-break
                        case 'scss':
                            $return['file_types']['css']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'ini':
                            $return['file_types']['ini']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'js':
                            // no-break
                        case 'json':
                            $return['file_types']['js']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'html':
                            // no-break
                        case 'htm':
                            $return['file_types']['html']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'php':
                            // no-break
                        case 'phps':
                            // no-break
                        case 'phtml':
                            $return['file_types']['php']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'xml':
                            $return['file_types']['xml']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'yaml':
                            // no-break
                        case 'yml':
                            $return['file_types']['yaml']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        default:
                            $return['file_extensions']['unknown']++;
                    }

                    // Add file statistics
                    $return['files_statistics'][$file] = File::new($file, $this->restrictions)->getPhpStatistics();
                    $return['total_statistics'] = Arrays::addValues($return['total_statistics'], $return['files_statistics'][$file]);

                } catch (FilesystemException $e) {
                    Log::warning(tr('Ignoring file ":file" due to exception ":e"', [
                        ':file' => $file,
                        ':e'    => $e,
                    ]), 2);
                }
            });

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
    public function ensureWritable(?int $mode = null): static
    {
        // Get configuration. We need file and directory default modes
        $mode = Config::get('filesystem.mode.default.directory', 0750, $mode);

        if (!$this->ensureFileWritable($mode)) {
            Log::action(tr('Creating non existing directory ":file" with file mode ":mode"', [
                ':mode' => Strings::fromOctal($mode),
                ':file' => $this->path
            ]), 3);

            mkdir($this->path, $mode);
        }

        return $this;
    }


    /**
     * Returns a temporary directory specific for this process that will be removed once the process terminates
     *
     * The temporary directory returned will always be the same within one process, if per
     *
     * @param bool $public
     * @return DirectoryInterface
     */
    public static function getSessionTemporaryPath(bool $public = false): DirectoryInterface
    {
        if ($public) {
            // Return public temp directory
            if (empty(static::$temp_directory_public)) {
                // Initialize public temp directory first
                $path = DIRECTORY_PUBTMP . Session::getUUID();
                static::$temp_directory_public = static::new($path, Restrictions::writable($path, 'public temporary directory'))
                    ->deletePath()
                    ->ensure();

                // Put lock file to avoid delete directory auto cleanup removing this session directory
                touch(static::$temp_directory_public->getPath() . '.lock');
            }

            return static::$temp_directory_public;
        }

        // Return private temp directory
        if (empty(static::$temp_directory_private)) {
            // Initialize private temp directory first
            $path = DIRECTORY_TMP . Session::getUUID();
            static::$temp_directory_private = static::new($path, Restrictions::writable($path, 'private temporary directory'))
                ->deletePath()
                ->ensure();

            // Put lock file to avoid delete directory auto cleanup removing this session directory
            touch(static::$temp_directory_private->getPath() . '.lock');
        }

        return static::$temp_directory_private;
    }


    /**
     * Returns a temporary directory specific for this process that will be removed once the process terminates
     *
     * The temporary directory returned will always be the same within one process, if per
     *
     * @param bool $public
     * @param bool $persist If specified, the temporary directory will persist and not be removed once the process
     *                      terminates
     * @return DirectoryInterface
     */
    public static function getTemporary(bool $public = false, bool $persist = false): DirectoryInterface
    {
        if (!$persist) {
            // Return a non-persistent temporary directory that will be deleted once this process terminates
            $path = static::getSessionTemporaryPath($public) . Strings::generateUuid();
            return static::new($path, Restrictions::writable($path, tr('persistent temporary directory')))->ensure();
        }

        $directory    = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $restrictions = Restrictions::writable($directory, tr('persistent temporary directory'));

        return static::new($directory . Strings::generateUuid(), $restrictions)->ensure();
    }


    /**
     * Removes the temporary directory specific for this process
     *
     * @note Will not delete temporary directories in debug mode as these directories may be required for debugging purposes
     * @return void
     */
    public static function removeTemporary(): void
    {
        Core::ExecuteNotInTestMode(function() {
            $action = false;

            if (static::$temp_directory_private) {
                File::new(static::$temp_directory_private, Restrictions::new(DIRECTORY_TMP, true))->deletePath();
                $action = true;
            }

            if (static::$temp_directory_public) {
                File::new(static::$temp_directory_public, Restrictions::new(DIRECTORY_PUBTMP, true))->deletePath();
                $action = true;
            }

            return $action;

        }, tr('Cleaned up temporary directories ":private, :public"', [
            ':private' => Strings::from(static::$temp_directory_private, DIRECTORY_ROOT),
            ':public'  => Strings::from(static::$temp_directory_public, DIRECTORY_ROOT)
        ]));
    }


    /**
     * Return a system directory for the specified type
     *
     * @param string $type
     * @param string $directory
     * @return string
     */
    public static function getSystem(string $type, string $directory = ''): string
    {
        switch ($type) {
            case 'img':
                // no-break
            case 'image':
                return '/pub/img/' . $directory;

            case 'css':
                // no-break
            case 'style':
                return '/pub/css/' . $directory;

            default:
                throw new OutOfBoundsException(tr('Unknown system directory type ":type" specified', [':type' => $type]));
        }
    }


    /**
     * Tars this directory and returns a file object for the tar file
     *
     * @return FileInterface
     */
    public function tar(): FileInterface
    {
        return File::new(Tar::new($this->restrictions)->tar($this->path), $this->restrictions);
    }


    /**
     * Returns the single one file in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return FileInterface
     */
    public function getSingleFile(?string $regex = null, bool $allow_multiple = false): FileInterface
    {
        return File::new($this->path . $this->getSingle($regex, false, $allow_multiple), $this->restrictions);
    }


    /**
     * Returns the single one directory in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return DirectoryInterface
     */
    public function getSingleDirectory(?string $regex = null, bool $allow_multiple = false): DirectoryInterface
    {
        return Directory::new($this->path . $this->getSingle($regex, true, $allow_multiple), $this->restrictions);
    }


    /**
     * Returns the single one file in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool|null $directory
     * @param bool $allow_multiple
     * @return string
     */
    protected function getSingle(?string $regex = null, ?bool $directory = null, bool $allow_multiple = false): string
    {
        $files = scandir($this->path);

        if (!$files) {
            throw new FilesystemException(tr('Cannot get single file from directory ":directory", scandir failed', [
                ':directory' => $this->path
            ]));
        }

        // Get rid of . and ..
        array_shift($files);
        array_shift($files);

        foreach ($files as $id => $file) {
            if (is_bool($directory)) {
                // Filter on directories or non directories
                if (is_dir($this->path . $file)) {
                    // This is a directory
                    if (!$directory) {
                        // But we're looking for non directories
                        unset($files[$id]);
                        continue;
                    }
                } else {
                    // This is a non directory file
                    if ($directory) {
                        // But we're looking for directories
                        unset($files[$id]);
                        continue;
                    }
                }
            }

            if ($regex) {
                // Filter on regex too
                if (!preg_match($regex, $file)) {
                    // This file doesn't match the regex
                    unset($files[$id]);
                    continue;
                }
            }
        }

        // Ensure we have only 1 file. Zero is less than one and shall not be accepted, as is two, which is more than
        // one and as such not equal or the same as one, and therefore shall not be accepted.
        switch (count($files)) {
            case 0:
                throw new FilesystemException(tr('Cannot return a single file, the directory ":directory" matches no files', [
                    ':directory'  => $this->path
                ]));

            case 1:
                break;

            default:
                if (!$allow_multiple) {
                    throw new FilesystemException(tr('Cannot return a single file, the directory ":directory" matches ":count" files', [
                        ':directory'  => $this->path,
                        ':count' => count($files)
                    ]));

                }
        }

        return array_shift($files);
    }


    /**
     * Returns the number of available files in the current file directory
     *
     * @param bool $recursive
     * @return int
     */
    public function getCount(bool $recursive = true): int
    {
        if ($this instanceof FileInterface) {
            if ($this->exists()) {
                // This is a single file!
                return 1;
            }

            return 0;
        }

        // Return the number of all files in this directory
        $files = scandir($this->path);
        $count = count($files);

        // Recurse?
        if ($recursive) {
            // Recurse!
            foreach ($files as $file) {
                if (($file === '.') or ($file === '..')) {
                    // Skip crap
                    continue;
                }

                // Filename must have complete absolute directory
                $file = $this->path . $file;

                if (is_dir($file)) {
                    // Count all files in this sub directory, minus the directory itself
                    $count += static::new($file, $this->restrictions)->getCount($recursive) - 1;
                }
            }
        }

        return $count;
    }


    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_patterns The single or multiple pattern(s) that should be matched
     * @param int $glob_flags            Flags for the internal glob() call
     * @param int $match_flags           Flags for the internal fnmatch() call
     * @return array                     The resulting file directories
     */
    public function scan(?string $file_patterns = null, int $glob_flags = GLOB_MARK, int $match_flags = FNM_PERIOD|FNM_CASEFOLD): array
    {
        $this->restrictions->check($this->path, false);

        $return = [];

        // Get directory pattern part and file pattern part
        if ($file_patterns) {
            $directory_pattern = dirname($file_patterns);
            $file_patterns     = basename($file_patterns);

            // Parse file patterns
            switch (substr_count($file_patterns, '{')) {
                case 0:
                    $base_pattern  = '';
                    $file_patterns = [$file_patterns];
                    break;

                case 1:
                    switch (substr_count($file_patterns, '}')) {
                        case 0:
                            throw new OutOfBoundsException(tr('Invalid file patterns ":patterns" specified, the pattern should contain either one set of matching { and } or none', [
                                ':patterns' => $file_patterns
                            ]));

                        case 1:
                            // Remove the {} and explode on ,
                            $base_pattern  = Strings::until($file_patterns, '{');
                            $file_patterns = Strings::cut($file_patterns, '{', '}');
                            $file_patterns = explode(',', $file_patterns);
                            break;

                        default:
                            throw new OutOfBoundsException(tr('Invalid file patterns ":patterns" specified, the pattern should contain either one set of matching { and } or none', [
                                ':patterns' => $file_patterns
                            ]));
                    }

                    break;

                default:
                    throw new OutOfBoundsException(tr('Invalid file patterns ":patterns" specified, the pattern should contain either one set of matching { and } or none', [
                        ':patterns' => $file_patterns
                    ]));
            }

            // Fix directory pattern
            if ($directory_pattern === '.') {
                $directory_pattern  = '';

            } else {
                $directory_pattern .= '/';
            }

        } else {
            // All
            $directory_pattern  =  '';
            $base_pattern  =  '';
            $file_patterns = [''];
        }

        // Get files
         $glob = glob($this->path . $directory_pattern . '*', $glob_flags);

        if (empty($glob)) {
            // This directory pattern search had no results
            return [];
        }

        // Check file patterns
        foreach ($glob as $file) {
            foreach ($file_patterns as $file_pattern) {
                $file_pattern = $base_pattern . $file_pattern;

                $file = Strings::from($file, $this->getRealPath());
                $test = Strings::fromReverse(Strings::endsNotWith($file, '/'), '/');

                if ($file_pattern){
                    if (is_dir($this->path . $file)) {
                        $directory_pattern = Strings::until($file_pattern, '.');

                        if (!fnmatch($directory_pattern, $test, $match_flags)) {
                            // This directory doesn't match the test pattern
                            continue;
                        }

                    } elseif (!fnmatch($file_pattern, $test, $match_flags)) {
                        // This file doesn't match the test pattern
                        continue;
                    }
                }

                // Add the file for the found match and continue to the next file
                $return[] = $file;
                break;
            }
        }

        return $return;
    }


    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_pattern The single or multiple pattern(s) that should be matched
     * @param int $glob_flags           Flags for the internal glob() call
     * @return array                    The resulting file directories
     */
    public function scanRegex(?string $file_pattern = null, int $glob_flags = GLOB_MARK): array
    {
        $this->restrictions->check($this->path, false);

        // Get files
        $return = [];
        $glob   = glob($this->path . '*', $glob_flags);

        if (empty($glob)) {
            // This directory pattern search had no results
            return [];
        }

        // Check file patterns
        foreach ($glob as $file) {
            $file = Strings::from($file, $this->path);
            $test = Strings::fromReverse(Strings::endsNotWith($file, '/'), '/');

            if ($file_pattern){
                if (!preg_match($file_pattern, $test)) {
                    // This file doesn't match the test pattern
                    continue;
                }
            }

            // Add the file for the found match and continue to the next file
            $return[] = $file;
            break;
        }

        return $return;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false if not mounted, NULL if mounted, but
     * with issues
     *
     * Issues can be either that the .isnotmounted file is visible (which it should NOT be if mounted) or (if specified)
     * $source does not match the mounted source
     *
     * @param array|Stringable|string|null $sources
     * @return bool|null
     */
    public function isMounted(array|Stringable|string|null $sources): ?bool
    {
        $mounted     = $this->hasFile('.ismounted');
        $not_mounted = $this->hasFile('.isnotmounted');

        if ($mounted and !$not_mounted) {
            // This directory is mounted, yay!
            if ($sources) {
                // But is it mounted at the right place?
                $mount = Mounts::getDirectoryMountInformation($this);

                foreach ($sources as $source) {
                    if ($mount['source'] == Directory::new($source)->getPath()) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        }

        if (!$mounted and $not_mounted) {
            return false;
        }

        // Either none of the files are available, or both are. Either case is an "unknown" state
        return null;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param array|Stringable|string|null $sources
     * @return static
     * @throws DirectoryNotMountedException
     */
    public function checkMounted(array|Stringable|string|null $sources): static
    {
        $status = $this->isMounted($sources);

        if ($status === false) {
            throw new DirectoryNotMountedException(tr('The directory ":directory" should be mounted from any of the sources ":source" but it is not mounted', [
                ':directory' => $this->getPath(),
                ':source'    => $sources,
            ]));
        }

        if (!$status) {
            throw new DirectoryNotMountedException(tr('The directory ":directory" should be mounted from ":source" but has an unknown mount state', [
                ':directory' => $this->getPath(),
                ':source'    => $sources,
            ]));
        }

        // We're mounted and from the right source, yay!
        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param array|Stringable|string|null $sources
     * @param array|null $options
     * @param string|null $filesystem
     * @return static
     */
    public function ensureMounted(array|Stringable|string|null $sources, ?array $options = null, ?string $filesystem = null): static
    {
        if (!$this->isMounted($source)) {
            $this->mount($source, $options, $filesystem);
        }

        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param Stringable|string|null $source
     * @param string|null $filesystem
     * @param array|null $options
     * @return static
     */
    public function mount(Stringable|string|null $source, ?string $filesystem = null, ?array $options = null): static
    {
        Mounts::mount(File::new($source), $this, $filesystem, $options);
        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @return static
     */
    public function unmount(): static
    {
        Mounts::unmount($this);
        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param Stringable|string|null $source
     * @param array|null $options
     * @return static
     */
    public function bind(Stringable|string|null $source, ?array $options = null): static
    {
        // Add the required bind option
        $options[] = '--bind';

        // Source must be a directory
        return $this->mount(Directory::new($source), $options);
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @return static
     */
    public function unbind(): static
    {
        return $this->unmount();
    }


    /**
     * Copy this directory with progress notification
     *
     * @param Stringable|string $target
     * @param callable $callback
     * @param RestrictionsInterface $restrictions
     * @return static
     * @example:
     * File::new($source)->copy($target, function ($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
     *      if ($notification_code == STREAM_Notification_PROGRESS) {
     *          // save $bytes_transferred and $bytes_max to file or database
     *      }
     *  });
     */
    public function copy(Stringable|string $target, callable $callback, RestrictionsInterface $restrictions): static
    {
        throw new UnderConstructionException();

        $context      = stream_context_create();
        $restrictions = $this->ensureRestrictions($restrictions);

        $this->restrictions->check($this->path, true);
        $restrictions->check($target, false);

        stream_context_set_params($context, [
            'notification' => $callback
        ]);

        copy($this->path, $target, $context);
        return new static($target, $this->restrictions);
    }


    /**
     * Returns a new Find object
     *
     * @return FindInterface
     */
    public function find(): FindInterface
    {
        return Find::new($this->restrictions)->setFindPath($this);
    }


    /**
     * Returns the specified directory added to this directory
     *
     * @param PathInterface|string $directory
     * @return DirectoryInterface
     */
    public function addDirectory(PathInterface|string $directory): DirectoryInterface
    {
        $directory = $this->getPath() . Strings::startsNotWith((string) $directory, '/');
        return Directory::new($directory, $this->restrictions);
    }


    /**
     * Returns true if this path contains any files
     *
     * @return bool
     */
    public function containFiles(): bool
    {
        return Find::new()
            ->setFindPath($this->path)
            ->setType('f')
            ->executeReturnIterator()
            ->isNotEmpty();
    }
}
