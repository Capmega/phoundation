<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\PathNotDirectoryException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Processes\Exception\ProcessesException;
use Throwable;



/**
 * Path class
 *
 * This library contains various filesystem path related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Path
{
    /**
     * The file access permissions
     *
     * @var Restrictions
     */
    protected Restrictions $restrictions;

    /**
     * The $path for this Path object
     *
     * @var array|null $paths
     */
    protected array|null $paths = null;



    /**
     * Path class constructor
     *
     * @param array|string|null $path
     * @param Restrictions|array|string|null $restrictions
     */
    public function __construct(array|string|null $path = null, Restrictions|array|string|null $restrictions = null)
    {
        $this->paths = Arrays::force($path, null);
        $this->setRestrictions($restrictions);
    }



    /**
     * Returns a new File object with the specified restrictions
     *
     * @param array|string|null $path
     * @param Restrictions|array|string|null $restrictions
     * @return Path
     */
    public static function new(array|string|null $path = null, Restrictions|array|string|null $restrictions = null): Path
    {
        return new Path($path, $restrictions);
    }



    /**
     * Returns an Each object to execute callbacks on each file in specified paths
     *
     * @return Each
     */
    public function each(): Each
    {
        return new Each($this->paths, $this->restrictions);
    }



    /**
     * Returns the paths for this Path object
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }



    /**
     * Returns the Restriction object for this Path object
     *
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Returns the paths for this Path object
     *
     * @param Restrictions|array|string|null $restrictions
     * @return Path
     */
    public function setRestrictions(Restrictions|array|string|null $restrictions = null): Path
    {
        $this->restrictions = Core::ensureRestrictions($restrictions);
        return $this;
    }



    /**
     * Ensures existence of the specified path
     *
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package file
     * @version 2.4.16: Added documentation
     *
     * @param string|null $mode octal $mode If the specified $path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
     * @return string The specified file
     */
    public function ensure(?string $mode = null, ?bool $clear = false, bool $sudo = false): string
    {
        foreach ($this->paths as $path) {
            Filesystem::validateFilename($path);

            $mode = Config::get('filesystem.mode.directories', 0750, $mode);

            if ($clear) {
                // Delete the currently existing path, so we can  be sure we have a clean path to work with
                File::new($this->paths, $this->restrictions)->delete(false, $sudo);
            }

            if (!file_exists(Strings::unslash($path))) {
                // The complete requested path doesn't exist. Try to create it, but directory by directory so that we can
                // correct issues as we run in to them
                $dirs = explode('/', Strings::startsNotWith($path, '/'));
                $path = '';

                foreach ($dirs as $dir) {
                    $path .= '/' . $dir;

                    if (file_exists($path)) {
                        if (!is_dir($path)) {
                            // Some normal file is in the way. Delete the file, and retry
                            File::new($path, $this->restrictions)->delete(false, $sudo);
                            return $this->ensure($mode, $clear, $sudo);
                        }

                        continue;

                    } elseif (is_link($path)) {
                        // This is a dead symlink, delete it
                        File::new($path, $this->restrictions)->delete(false, $sudo);
                    }

                    try {
                        // Make sure that the parent path is writable when creating the directory
                        Path::new(dirname($path), $this->restrictions)->each()
                            ->setPathMode(0770)
                            ->executePath(function() use ($path, $mode) {
                                mkdir($path, $mode);
                            });

                    }catch(Exception $e) {
                        // It sometimes happens that the specified path was created just in between the file_exists and
                        // mkdir
                        if (!file_exists($path)) {
                            throw $e;
                        }
                    }
                }

            } elseif (!is_dir($path)) {
                // Some other file is in the way. Delete the file, and retry.
                // Ensure that the "file" is not accidentally specified as a directory ending in a /
                File::new(Strings::endsNotWith($path, '/'), $this->restrictions)->delete(false, $sudo);
                return $this->ensure($mode, $clear, $sudo);
            }

            return Strings::slash(realpath($this->paths));
        }
    }



    /**
     * Check if the specified directory exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type
     * @param Throwable|null $previous_e
     * @return void
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): void
    {
        Filesystem::validateFilename($this->paths);

        if (!file_exists($this->paths)) {
            if (!file_exists(dirname($this->paths))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist and neither does the parent path ":path"', [':type' => ($type ? '' : ' ' . $type), ':file' => $this->paths, ':path' => dirname($this->paths)]), previous: $previous_e);
            }

            throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist', [':type' => ($type ? '' : ' ' . $type), ':file' => $this->paths]), previous: $previous_e);
        }

        if (!is_readable($this->paths)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read', [':type' => ($type ? '' : ' ' . $type), ':file' => $this->paths]), previous: $previous_e);
        }

        if ($previous_e) {
            // This method was called because a read action failed, throw an exception for it
            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [':type' => ($type ? '' : ' ' . $type), ':file' => $this->paths]), previous: $previous_e);
        }
    }



    /**
     * Returns true if the object paths are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        foreach ($this->paths as $path) {
            $this->fileExists($path);

            if (!is_dir($path)) {
                $this->checkReadable();

                throw new PathNotDirectoryException(tr('The specified path ":path" is not a directory', [
                    ':path' => $this->paths
                ]));
            }

            // Start reading the directory.
            $handle = opendir($path);

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
        }

        return true;
    }



    /**
     * Delete the path, and each parent directory until a non empty directory is encountered
     *
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
     *
     * @param bool $sudo
     * @return void
     */
    public function clear(bool $sudo = false): void
    {
        $this->checkRestrictions($this->paths, true);

        foreach ($this->paths as $path) {
            while ($path) {
                // Restrict location access
                try {
                    $this->checkRestrictions($this->paths, true);
                } catch (RestrictionsException) {
                    // We're out of our territory, stop scanning!
                    break;
                }

                if (!file_exists($path)) {
                    // This section does not exist, jump up to the next section above
                    $path = dirname($path);
                    continue;
                }

                if (!is_dir($path)) {
                    // This is a normal file, we only delete directories here!
                    throw new OutOfBoundsException(tr('Not clearing path ":path", it is not a directory', [
                        ':path' => $path
                    ]));
                }

                if (!Path::new($path, $this->restrictions)->isEmpty()) {
                    // Do not remove anything more, there is contents here!
                    break;
                }

                // Remove this entry and continue;
                try {
                    File::new($path, $this->restrictions)->delete(false, $sudo);

                }catch(Exception $e) {
                    /*
                     * The directory WAS empty, but cannot be removed
                     *
                     * In all probability, a parrallel process added a new content in this directory, so it's no longer empty.
                     * Just register the event and leave it be.
                     */
                    Log::warning(tr('Failed to remove empty pattern ":pattern" with exception ":e"', [
                        ':pattern' => $path,
                        ':e'       => $e
                    ]));

                    break;
                }

                // Go one entry up, check if we're still within restrictions, and continue deleting
                $path = dirname($path);
            }
        }
    }



    /**
     * Creates a random path in specified base path (If it does not exist yet), and returns that path
     *
     * @param bool $single
     * @param int $length
     * @return string
     */
    public function createTarget(?bool $single = null, int $length = 0): string
    {
        // Check filesystem restrictions 
        $this->checkRestrictions($this->paths, true);
        $this->fileExists($this->paths);

        // Check configuration
        if (!$length) {
            $length = Config::getInteger('filesystem.target-path.size', 8);
        }

        if ($single === null) {
            $single = Config::getBoolean('filesystem.target-path.single', false);
        }

        $this->requireSinglePath();

        $path = Arrays::firstValue($this->paths);
        $path = Strings::unslash(Path::new($path, $this->restrictions)->ensure());

        if ($single) {
            // Assign path in one dir, like abcde/
            $path = Strings::slash($path) . substr(uniqid(), -$length, $length);

        } else {
            // Assign path in multiple dirs, like a/b/c/d/e/
            foreach (str_split(substr(uniqid(), -$length, $length)) as $char) {
                $path .= DIRECTORY_SEPARATOR . $char;
            }
        }

        // Ensure again to be sure the target directories too have been created
        return Strings::slash(Path::new($path, $this->restrictions)->ensure());
    }



    /**
     * Return all files in a directory that match the specified pattern with optional recursion.
     *
     * @param array|string|null $filters One or multiple regex filters
     * @param boolean $recursive If set to true, return all files below the specified path, including in sub-directories
     * @return array The matched files
     */
    public function listTree(array|string|null $filters = null, bool $recursive = true): array
    {
        // Check filesystem restrictions 
        $this->checkRestrictions($this->paths, false);

        $return = [];

        foreach ($this->paths as $path) {
            $this->fileExists($path);
            $fh = opendir($path);

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

                // Get the complete file path
                $file = Strings::slash($path) . $filename;

                // Add the file to the list. If the file is a directory, then recurse instead. Do NOT add the directory
                // itself, only files!
                if (is_dir($file) and $recursive) {
                    $return = array_merge($return, Path::new($file, $this->restrictions)->listTree());

                } else {
                    $return[] = $file;
                }
            }

            closedir($fh);
        }

        return $return;
    }



    /**
     * Pick and return a random file name from the specified path
     *
     * @note This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
     *
     * @return string A random file from a random path from the object paths
     */
    public function random(): string
    {
        // Check filesystem restrictions 
        $this->checkRestrictions($this->paths, false);

        $path = Arrays::getRandomValue($this->paths);
        $this->fileExists($path);

        $files = scandir($path);

        Arrays::unsetValue($files, '.');
        Arrays::unsetValue($files, '..');

        if (!$files) {
            throw new FilesystemException(tr('The specified path ":path" contains no files', [
                ':path' => $path
            ]));
        }

        return Strings::slash($path) . Arrays::getRandomValue($files);
    }



    /**
     * Scan the entire object path STRING upward for the specified file.
     *
     * If the object file doesn't exist in the specified path, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     * @return string|null
     */
    public function scanUpwardsForFile(string $filename): ?string
    {
        // Check filesystem restrictions 
        $this->checkRestrictions($this->paths, false);

        foreach ($this->paths as $path) {
            $this->fileExists($path);

            while (strlen($path) > 1) {
                $path = Strings::slash($path);

                if (file_exists($path . $filename)) {
                    // The requested file is found! Return the path where it was found
                    return $path;
                }

                $path = dirname($path);
            }
        }

        return null;
    }



    /**
     * Returns the total size in bytes of the tree under the specified path
     *
     * @return int The amount of bytes this tree takes
     */
    public function treeFileSize(): int
    {
        // Check filesystem restrictions 
        $this->checkRestrictions($this->paths, false);

        $return = 0;

        foreach ($this->paths as $path) {
            $this->fileExists($path);

            foreach (scandir($path) as $file) {
                if (($file == '.') or ($file == '..')) continue;

                if (is_dir($path . $file)) {
                    // Recurse
                    $return += Path::new($path . $file, $this->restrictions)->treeFileSize();

                } else {
                    $return += filesize($path . $file);
                }
            }
        }

        return $return;
    }



    /**
     * Returns the amount of files under the object path (directories not included in count)
     *
     * @return int The amount of files
     */
    public function treeFileCount(): int
    {
        // Check filesystem restrictions 
        $this->checkRestrictions($this->paths, false);

        $return = 0;

        foreach ($this->paths as $path) {
            $this->fileExists($path);

            foreach (scandir($path) as $file) {
                if (($file == '.') or ($file == '..')) continue;

                if (is_dir($path . $file)) {
                    $return += Path::new($path . $file, $this->restrictions)->treeFileCount();

                } else {
                    $return++;
                }
            }
        }

        return $return;
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
        // Get configuration. We need file and directory default modes
        $mode = Config::get('filesystem.mode.default.directory', 0750, $mode);

        foreach ($this->paths as $path) {
            // If the object file exists and is writable, then we're done.
            if (is_writable($path)) {
                continue;
            }

            // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...

            if (file_exists($path)) {
                // Great! The file exists, but it is not writable at this moment. Try to make it writable.
                try {
                    Log::warning(tr('The object path ":path" (Realpath ":path") is not writable. Attempting to apply default directory mode ":mode"', [
                        ':file' => $path,
                        ':path' => realpath($path),
                        ':mode' => $mode
                    ]));

                    File::new($path, $this->restrictions)->chmod('u+w');

                } catch (ProcessesException $e) {
                    throw new FileNotWritableException(tr('The object file ":file" (Realpath ":path") is not writable, and could not be made writable', [
                        ':file' => $path,
                        ':path' => realpath($path)
                    ]));
                }
            }

            // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
            Path::new(dirname($path), $this->restrictions)->ensure();

            Log::warning(tr('The object path ":path" (Realpath ":path") does not exist. Attempting to create it with file mode ":mode"', [
                ':mode' => Strings::fromOctal($mode),
                ':file' => $path,
                ':path' => realpath($path)
            ]));

            mkdir($path, $mode, true);
        }
    }



    /**
     * Check the specified $path against this objects' restrictions
     *
     * @param string $path
     * @param bool $write
     * @return void
     */
    protected function checkRestrictions(string $path, bool $write)
    {
        $this->restrictions->check($path, $write);
    }



    /**
     * Checks if the specified path exists
     *
     * @param string|null $path
     * @return void
     */
    protected function fileExists(?string $path): void
    {
        if (!file_exists($path)) {
            throw new FilesystemException(tr('Specified path ":path" does not exist', [':path' => $path]));
        }
    }



    /**
     * Requires that this Path object has only one path
     *
     * @return void
     */
    protected function requireSinglePath(): void
    {
        if (count($this->paths) > 1) {
            throw new OutOfBoundsException(tr('Path object has ":count" paths specified while only one path is allowed', [
                'count' => count($this->paths)
            ]));
        }
    }
}