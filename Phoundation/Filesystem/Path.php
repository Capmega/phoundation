<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Strings;
use Phoundation\Filesystem\Exception\FilesystemException;

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
     * @param string|null octal $mode If the specified $path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
     * @return string The specified file
     */
    public static function ensure(string $path, ?string $mode = null, ?bool $clear = false, ?Restrictions $restrictions = null): string
    {
        if ($mode === null) {
            $mode = $_CONFIG['file']['dir_mode'];

            if (!$mode) {
                /*
                 * Mode configuration is not available (yet?)
                 * Fall back to a default mode, 0770 for directories
                 */
                $mode = 0770;
            }
        }

        if ($clear) {
            /*
             * Delete the currently existing file so we can  be sure we have an
             * empty directory
             */
            file_delete($path, $restrictions);
        }

        if (!file_exists(Strings::unslash($path))) {
            /*
             * The complete requested path doesn't exist. Try to create it, but
             * directory by directory so that we can correct issues as we run in
             * to them
             */
            $dirs = explode('/', Strings::startsNotWith($path, '/'));
            $path = '';

            foreach($dirs as $dir) {
                $path .= '/'.$dir;

                if (file_exists($path)) {
                    if (!is_dir($path)) {
                        /*
                         * Some normal file is in the way. Delete the file, and
                         * retry
                         */
                        File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions) {
                            File::delete($path, $restrictions);
                        });

                        return Path::ensure($path, $mode);
                    }

                    continue;

                } elseif (is_link($path)) {
                    /*
                     * This is a dead symlink, delete it
                     */
                    File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions) {
                        File::delete($path, $restrictions);
                    });
                }

                try{
                    /*
                     * Make sure that the parent path is writable when creating
                     * the directory
                     */
                    File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode) {
                        mkdir($path, $mode);
                    });

                }catch(Exception $e) {
                    /*
                     * It sometimes happens that the specified path was created
                     * just in between the file_exists and mkdir
                     */
                    if (!file_exists($path)) {
                        throw $e;
                    }
                }
            }

        } elseif (!is_dir($path)) {
            /*
             * Some other file is in the way. Delete the file, and retry.
             *
             * Ensure that the "file" is not accidentally specified as a
             * directory ending in a /
             */
            File::delete(Strings::endsNotWith($path, '/'), $restrictions);
            return file_ensure_path($path, $mode);
        }

        return Strings::slash(realpath($path).'/');
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
        try{
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
}