<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\PathNotDirectoryException;
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
     * The File object
     *
     * @var File|null $file
     */
    protected ?File $file = null;

    /**
     * The file access permissions
     *
     * @var Restrictions|null
     */
    protected ?Restrictions $restrictions = null;



    /**
     * File class constructor
     *
     * @param Restrictions|null $restrictions
     * @param File|null $file
     */
    public function __construct(?Restrictions $restrictions = null, ?File $file = null)
    {
        $this->file         = ($file ?? new File($restrictions));
        $this->restrictions = Core::ensureRestrictions($restrictions);
    }



    /**
     * Returns a new File object with the specified restrictions
     *
     * @param Restrictions|null $restrictions
     * @return Path
     */
    public static function new(?Restrictions $restrictions = null): Path
    {
        return new Path($restrictions);
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
     * @param string $path The path that must exist
     * @param string|null $mode octal $mode If the specified $path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
     * @return string The specified file
     */
    public function ensure(string $path, ?string $mode = null, ?bool $clear = false, ?Restrictions $restrictions = null): string
    {
        $this->file->validateFilename($path);

        $mode = Config::get('filesystem.mode.directories', 0750, $mode);

        if ($clear) {
            // Delete the currently existing path so we can  be sure we have a clean path to work with
            $this->file->delete($path, false, false, $restrictions);
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
                        $this->file->each(dirname($path))
                            ->setPathMode(0770)
                            ->execute(function(string $file) use ($restrictions) {
                                $this->file->delete($file, false, false, $restrictions);
                            });

                        return $this->ensure($path, $mode);
                    }

                    continue;

                } elseif (is_link($path)) {
                    // This is a dead symlink, delete it
                    $this->file->each(dirname($path))
                        ->setPathMode(0770)
                        ->execute(function(string $file) use ($restrictions) {
                            $this->file->delete($file, false, false, $restrictions);
                        });
                }

                try {
                    // Make sure that the parent path is writable when creating the directory
                    $this->file->each(dirname($path))
                        ->setPathMode(0770)
                        ->execute(function(string $file) use ($path, $mode) {
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
            $this->file->delete(Strings::endsNotWith($path, '/'), false, false, $restrictions);
            return $this->ensure($path, $mode);
        }

        return Strings::slash(realpath($path));
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
     * @param string $file
     * @param string|null $type
     * @param Throwable|null $previous_e
     * @return void
     */
    public function checkReadable(string $file, ?string $type = null, ?Throwable $previous_e = null): void
    {
        $this->file->validateFilename($file);

        if (!file_exists($file)) {
            if (!file_exists(dirname($file))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist and neither does the parent path ":path"', [':type' => ($type ? '' : ' ' . $type), ':file' => $file, ':path' => dirname($file)]), previous: $previous_e);
            }

            throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }

        if (!is_readable($file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }

        if ($previous_e) {
            // This method was called because a read action failed, throw an exception for it
            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }
    }



    /**
     * Return a file path for a temporary directory
     *
     * @param bool $create If set to false, only the file path will be returned, the temporary file will NOT be created.
     *                     If set to true, the file will be created. If set to a string, the temp file will be created
     *                     with as contents the $create string
     * @param bool $limit_to_session
     * @return string The filename for the temp directory
     * @version 2.5.90: Added documentation, expanded $create to be able to contain data for the temp file
     * @note: If the resolved temp directory path already exist, it will be deleted, so take care when using $name!
     */
    public function temp(bool|string $create = true, bool $limit_to_session = true) : string
    {
        Path::ensure(PATH_TMP);

        // Temp file will contain the session ID
        if ($limit_to_session) {
            $session_id = session_id();
            $name       = substr(hash('sha1', uniqid().microtime()), 0, 12);

            if ($session_id) {
                $name = $session_id.'-' . $name;
            }

        } else {
            $name = substr(hash('sha1', uniqid().microtime()), 0, 12);
        }

        $file = PATH_TMP.$name;

        // Temp file can not exist
        if (file_exists($file)) {
            $this->file->delete($file);
        }

        if ($create) {
            mkdir($file);
        }

        return $file;
    }



    /**
     * realpath() wrapper that won't crash with an exception if the specified string is not a real path
     *
     * @version 2.8.40: Added function and documentation
     * @example
     * code
     * show(is_path('test'));
     * showdie(is_path('/bin'));
     * /code
     *
     * This would return
     * code
     * false
     * /bin
     * /code
     *
     * @param string $path
     * @return ?string string The real path extrapolated from the specified $path, if exists. False if whatever was
     *                 specified does not exist.
     */
    public function real(string $path): ?string
    {
        try {
            return realpath($path);

        }catch(\Throwable $e) {
            // If PHP threw an error for the path not being a path at all, just return false
            $data = $e->getData(true);

            if (str_contains($data, 'expects parameter 1 to be a valid path')) {
                return null;
            }

            // This is some other error, keep throwing
            throw new FilesystemException(tr('Failed'), previous: $e);
        }
    }



    /**
     * Returns true if the specified directory is empty
     *
     * @param string $path
     * @return bool
     */
    public function isEmpty(string $path): bool
    {
        if (!is_dir($path)) {
            $this->file->checkReadable($path);

            throw new PathNotDirectoryException(tr('The specified path ":path" is not a directory', [
                ':path' => $path
            ]));
        }

        // Start reading the directory.
        $handle = opendir($path);

        while (($file = readdir($handle)) !== false) {
            // Skip . and ..
            if (($file == '.') or ($file == '..')) continue;

            // Yeah, this has files
            closedir($handle);
            return false;
        }

        // Yay, no files encountered!
        closedir($handle);
        return true;
    }



    /**
     * Return the absolute path for the specified path
     *
     * @note If the specified path exists, and it is a directory, this function will automatically add a trailing / to
     *       the path name
     * @param string|null $path
     * @param string|null $prefix
     * @param bool $must_exist
     * @return string The absolute path
     */
    public function absolute(?string $path = null, string $prefix = null, bool $must_exist = true): string
    {
        if (!$path) {
            return PATH_ROOT;
        }

        $path = trim($path);

        if ($path[0] === '/') {
            // This is already an absolute path
            $return = $path;
        } else {
            // This is not an absolute path, make it an absolute path
            if (!$prefix) {
                $prefix = PATH_ROOT;
            }

            $return = Strings::slash($prefix) . Strings::unslash($path);
        }

        // If this is a directory, make sure it has a slash suffix
        if (file_exists($return)) {
            if (is_dir($return)) {
                $return = Strings::slash($return);
            }
        } else {
            if ($must_exist) {
                throw new FilesystemException(tr('The specified path ":path" does not exist', [
                    ':path' => $path
                ]));
            }

            // Path doesn't exist, but apparently that's okay! Continue!
        }

        return $return;
    }


    /**
     * Delete the path, and each parent directory until a non empty directory is encountered
     *
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
     *
     * @param array|string $patterns A list of path patterns to be cleared
     * @param bool $sudo
     * @param Restrictions|null $restrictions
     * @return void
     */
    public function clear(array|string $patterns, bool $sudo = false, ?Restrictions $restrictions = null): void
    {
        // Multiple paths specified, clear all
        if (is_array($patterns)) {
            foreach ($patterns as $pattern) {
                Path::clear($pattern, $sudo, $restrictions);
            }

            return;
        }

        $pattern = $patterns;

        while ($pattern) {
            // Restrict location access
            Core::ensureRestrictions($restrictions)->check($pattern);

            if (!file_exists($pattern)) {
                // This section does not exist, jump up to the next section above
                $pattern = dirname($pattern);
                continue;
            }

            if (!is_dir($pattern)) {
                // This is a normal file, we only delete directories here!
                throw new OutOfBoundsException(tr('Not clearning ":pattern", it is not a directory', [
                    ':pattern' => $pattern
                ]));
            }

            if (!Path::isEmpty($pattern)) {
                // Do not remove anything more, there is contents here!
                return;
            }

            // Remove this entry and continue;
            try {
                $this->file->delete($pattern, false, $sudo, $restrictions);

            }catch(Exception $e) {
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content in this directory, so it's no longer empty.
                 * Just register the event and leave it be.
                 */
                Log::warning(tr('Failed to remove empty pattern ":pattern" with exception ":e"', [
                    ':pattern' => $pattern,
                    ':e' => $e
                ]));

                return;
            }

            // Go one entry up, check if we're still within restrictions, and continue deleting
            $pattern = dirname($pattern);
        }
    }



    /**
     * Creates a random path in specified base path (If it does not exist yet), and returns that path
     *
     * @param string $path
     * @param bool $singledir
     * @param int $length
     * @return string
     */
    public function createTarget(string $path, bool $singledir = false, int $length = 0): string
    {
        if (!$length) {
            $length = Config::get('filesystem.target-path-size', 8);
        }

        $path = Strings::unslash(Path::ensure($path));

        if ($singledir) {
            // Assign path in one dir, like abcde/
            $path = Strings::slash($path).substr(uniqid(), -$length, $length);

        } else {
            // Assign path in multiple dirs, like a/b/c/d/e/
            foreach (str_split(substr(uniqid(), -$length, $length)) as $char) {
                $path .= DIRECTORY_SEPARATOR.$char;
            }
        }

        return Strings::slash(Path::ensure($path));
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
        if ($this->restrictions === null) {
            throw new FilesystemException(tr('No filesystem restrictions available'));
        }

        $this->restrictions->check($path, $write);
    }
}