<?php

namespace Phoundation\Filesystem;

/**
 * Path class
 *
 * This library contains various filesystem path related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Path
{
    /**
     * Ensures existence of the specified path
     *
     * @author Sven Olaf Oostenbrink <sven@capmega.com>
     * @copyright Copyright (c) 2018 Capmega
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
    public static function ensure(string $path, ?string $mode = null, bool $clear = false, string|array $restrictions = ROOT): string
    {
        global $_CONFIG;

        try{
            if($mode === null){
                $mode = $_CONFIG['file']['dir_mode'];

                if(!$mode){
                    /*
                     * Mode configuration is not available (yet?)
                     * Fall back to a default mode, 0770 for directories
                     */
                    $mode = 0770;
                }
            }

            if($clear){
                /*
                 * Delete the currently existing file so we can  be sure we have an
                 * empty directory
                 */
                file_delete($path, $restrictions);
            }

            if(!file_exists(unslash($path))){
                /*
                 * The complete requested path doesn't exist. Try to create it, but
                 * directory by directory so that we can correct issues as we run in
                 * to them
                 */
                $dirs = explode('/', str_starts_not($path, '/'));
                $path = '';

                foreach($dirs as $dir){
                    $path .= '/'.$dir;

                    if(file_exists($path)){
                        if(!is_dir($path)){
                            /*
                             * Some normal file is in the way. Delete the file, and
                             * retry
                             */
                            file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions){
                                file_delete($path, $restrictions);
                            });

                            return file_ensure_path($path, $mode);
                        }

                        continue;

                    }elseif(is_link($path)){
                        /*
                         * This is a dead symlink, delete it
                         */
                        file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions){
                            file_delete($path, $restrictions);
                        });
                    }

                    try{
                        /*
                         * Make sure that the parent path is writable when creating
                         * the directory
                         */
                        file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode){
                            mkdir($path, $mode);
                        });

                    }catch(Exception $e){
                        /*
                         * It sometimes happens that the specified path was created
                         * just in between the file_exists and mkdir
                         */
                        if(!file_exists($path)){
                            throw $e;
                        }
                    }
                }

            }elseif(!is_dir($path)){
                /*
                 * Some other file is in the way. Delete the file, and retry.
                 *
                 * Ensure that the "file" is not accidentally specified as a
                 * directory ending in a /
                 */
                file_delete(str_ends_not($path, '/'), $restrictions);
                return file_ensure_path($path, $mode);
            }

            return slash(realpath($path).'/');

        }catch(Exception $e){
            throw new CoreException(tr('file_ensure_path(): Failed to ensure path ":path"', array(':path' => $path)), $e);
        }
    }
}