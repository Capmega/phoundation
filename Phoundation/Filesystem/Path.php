<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Config;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
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
    public static function ensure(string $path, ?string $mode = null, ?bool $clear = false, ?Restrictions $restrictions = null): string
    {
        $mode = Config::get('filesystem.mode.defaults.directories', 0750, $mode);

        if ($clear) {
            // Delete the currently existing file so we can  be sure we have an
            File::delete($path, $restrictions);
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
                        File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions) {
                            File::delete($path, $restrictions);
                        });

                        return Path::ensure($path, $mode);
                    }

                    continue;

                } elseif (is_link($path)) {
                    // This is a dead symlink, delete it
                    File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions) {
                        File::delete($path, $restrictions);
                    });
                }

                try {
                    // Make sure that the parent path is writable when creating the directory
                    File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode) {
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
            File::delete(Strings::endsNotWith($path, '/'), $restrictions);
            return file_ensure_path($path, $mode);
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
    public static function checkReadable(string $file, ?string $type = null, ?Throwable $previous_e = null): void
    {
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
    public static function temp(bool|string $create = true, bool $limit_to_session = true) : string
    {
        Path::ensure(TMP);

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

        $file = TMP.$name;

        // Temp file can not exist
        if (file_exists($file)) {
            File::delete($file);
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
    public static function real(string $path): ?string
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
    public static function isEmpty(string $path): bool
    {
        if (!is_dir($path)) {
            File::checkReadable($path);

            throw new PathNotDirectoryException(tr('The specified path ":path" is not a directory', [':path' => $path]));
        }

        // Start reading the directory.
        $handle   = opendir($path);

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
}