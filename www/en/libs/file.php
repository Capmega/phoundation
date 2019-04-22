<?php
/*
 * File library
 *
 * This library contains various file related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package file
 */



/*
 * Append specified data string to the end of the specified file
 */
function file_append($target, $data){
    global $_CONFIG;

    try {
        /*
         * Open target
         */
        if(!file_exists(dirname($target))){
            throw new BException(tr('file_append(): Specified target path ":target" does not exist', array(':target' => dirname($target))), 'not-exists');
        }

        $target_h = fopen($target, 'a');
        fwrite($target_h, $data);
        fclose($target_h);

    }catch(Exception $e){
        throw new BException(tr('file_append(): Failed'), $e);
    }
}



/*
 * Concatenates a list of files to a target file
 */
function file_concat($target, $sources){
    global $_CONFIG;

    try {
        if(!is_array($sources)){
            $sources = array($sources);
        }

        /*
         * Open target
         */
        if(!file_exists(dirname($target))){
            throw new BException(tr('file_concat(): Specified target path ":target" does not exist', array(':target' => dirname($target))), 'not-exists');
        }

        $target_h = fopen($target, 'a');

        foreach($sources as $source){
            $source_h = fopen($source, 'r');

            while(!feof($source_h)){
                $data = fread($source_h, 8192);
                fwrite($target_h, $data);
            }

            fclose($source_h);
        }

        fclose($target_h);

    }catch(Exception $e){
        throw new BException(tr('file_concat(): Failed'), $e);
    }
}



/*
 * Move uploaded image to correct target
 */
function file_get_uploaded($source){
    global $_CONFIG;

    try{
        $destination = ROOT.'data/uploads/';

        if(is_array($source)){
            /*
             * Asume this is a PHP file upload array entry
             */
            if(empty($source['tmp_name'])){
                throw new BException(tr('file_move_uploaded(): Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry'), 'invalid');
            }

            $real   = $source['name'];
            $source = $source['tmp_name'];

        }else{
            $real   = basename($source);
        }


        is_file($source);
        file_ensure_path($destination);

        /*
         * Ensure we're not overwriting anything!
         */
        if(file_exists($destination.$real)){
            $real = str_runtil($real, '.').'_'.substr(uniqid(), -8, 8).'.'.str_rfrom($real, '.');
        }

        if(!move_uploaded_file($source, $destination.$real)){
            throw new BException(tr('file_move_uploaded(): Faield to move file ":source" to destination ":destination"', array(':source' => $source, ':destination' => $destination)), 'move');
        }

        /*
         * Return destination file
         */
        return $destination.$real;

    }catch(Exception $e){
        throw new BException(tr('file_move_uploaded(): Failed'), $e);
    }
}



/*
 * Create a target, but don't put anything in it
 */
function file_assign_target($path, $extension = false, $singledir = false, $length = 4){
    try{
        return file_move_to_target('', $path, $extension, $singledir, $length);

    }catch(Exception $e){
        throw new BException(tr('file_assign_target(): Failed'), $e);
    }
}



/*
 * Create a target, but don't put anything in it, and return path+filename without extension
 */
function file_assign_target_clean($path, $extension = false, $singledir = false, $length = 4){
    try{
        return str_replace($extension, '', file_move_to_target('', $path, $extension, $singledir, $length));

    }catch(Exception $e){
        throw new BException(tr('file_assign_target_clean(): Failed'), $e);
    }
}



/*
 * Copy specified file, see file_move_to_target for implementation
 */
function file_copy_to_target($file, $path, $extension = false, $singledir = false, $length = 4){
    try{
        if(is_array($file)){
            throw new BException(tr('file_copy_to_target(): Specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', array(':file' => str_log($file))));
        }

        return file_move_to_target($file, $path, $extension, $singledir, $length, true);

    }catch(Exception $e){
        throw new BException(tr('file_copy_to_target(): Failed'), $e);
    }
}



/*
 * Move specified file (must be either file string or PHP uploaded file array) to a target and returns the target name
 *
 * IMPORTANT! Extension here is just "the rest of the filename", which may be _small.jpg, or just the extension, .jpg
 * If only an extension is desired, it is VERY important that its specified as ".jpg" and not "jpg"!!
 *
 * $path sets the base path for where the file should be stored
 * If $extension is false, the files original extension will be retained. If set to a value, the extension will be that value
 * If $singledir is set to false, the resulting file will be in a/b/c/d/e/, if its set to true, it will be in abcde
 * $length specifies howmany characters the subdir should have (4 will make a/b/c/d/ or abcd/)
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 *
 * @param string $file
 * @param string $path
 * @param string $extension
 * @param string $singledir
 * @param string $length
 * @param string $copy
 * @param string $context
 * @return string The target file
 */
function file_move_to_target($file, $path, $extension = false, $singledir = false, $length = 4, $copy = false, $context = null){
    try{
        if(is_array($file)){
            $upload = $file;
            $file   = $file['name'];
        }

        if(isset($upload) and $copy){
            throw new BException(tr('file_move_to_target(): Copy option has been set, but specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', array(':file' => $file)));
        }

        $path     = file_ensure_path($path);
        $filename = basename($file);

        if(!$filename){
            /*
             * We always MUST have a filename
             */
            $filename = uniqid();
        }

        /*
         * Ensure we have a local copy of the file to work with
         */
        if($file){
            $file = file_get_local($file, $is_downloaded, $context);
        }

        if(!$extension){
            $extension = file_get_extension($filename);
        }

        if($length){
            $targetpath = slash(file_create_target_path($path, $singledir, $length));

        }else{
            $targetpath = slash($path);
        }

        $target = $targetpath.strtolower(str_convert_accents(str_runtil($filename, '.'), '-'));

        /*
         * Check if there is a "point" already in the extension
         * not obligatory at the start of the string
         */
        if($extension){
            if(strpos($extension, '.') === false){
                $target .= '.'.$extension;

            }else{
               $target .= $extension;
            }
        }

        /*
         * Only move file is target does not yet exist
         */
        if(file_exists($target)){
            if(isset($upload)){
                /*
                 * File was specified as an upload array
                 */
                return file_move_to_target($upload, $path, $extension, $singledir, $length, $copy);
            }

            return file_move_to_target($file, $path, $extension, $singledir, $length, $copy);
        }

        /*
         * Only move if file was specified. If no file specified, then we will only return the available path
         */
        if($file){
            if(isset($upload)){
                /*
                 * This is an uploaded file
                 */
                file_move_uploaded($upload['tmp_name'], $target);

            }else{
                /*
                 * This is a normal file
                 */
                if($copy and !$is_downloaded){
                    copy($file, $target);

                }else{
                    rename($file, $target);
                    file_clear_path(dirname($file));
                }
            }
        }

        return str_from($target, $path);

    }catch(Exception $e){
        throw new BException(tr('file_move_to_target(): Failed'), $e);
    }
}



/*
 * Creates a random path in specified base path (If it does not exist yet), and returns that path
 */
function file_create_target_path($path, $singledir = false, $length = false){
    global $_CONFIG;

    try{
        if($length === false){
            $length = $_CONFIG['file']['target_path_size'];
        }

        $path = unslash(file_ensure_path($path));

        if($singledir){
            /*
             * Assign path in one dir, like abcde/
             */
            $path = slash($path).substr(uniqid(), -$length, $length);

        }else{
            /*
             * Assign path in multiple dirs, like a/b/c/d/e/
             */
            foreach(str_split(substr(uniqid(), -$length, $length)) as $char){
                $path .= DIRECTORY_SEPARATOR.$char;
            }
        }

        return slash(file_ensure_path($path));

    }catch(Exception $e){
        throw new BException(tr('file_create_target_path(): Failed'), $e);
    }
}



/*
 * Ensure that the specified file exists in the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @note Will log to the console in case the file was created
 * @version 2.4.16: Added documentation, improved log output
 *
 * @param string $file The file that must exist
 * @param null octal $mode If the specified $file does not exist, it will be created with this file mode. Defaults to $_CONFIG[fs][file_mode]
 * @param null octal $path_mode If parts of the path for the file do not exist, these will be created as well with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
 * @return string The specified file
 */
function file_ensure_file($file, $mode = null, $path_mode = null){
    global $_CONFIG;

    try{
        if(!$mode){
            $mode = $_CONFIG['file']['file_mode'];
        }

        file_ensure_path(dirname($file), $path_mode);

        if(!file_exists($file)){
            /*
             * Create the file
             */
            file_execute_mode(dirname($file), 0770, function() use ($file, $mode){
                log_console(tr('file_ensure_file(): Warning: file ":file" did not exist and was created empty to ensure system stability, but information may be missing', array(':file' => $file)), 'VERBOSE/yellow');
                touch($file);

                if($mode){
                    chmod($file, $mode);
                }
            });
        }

        return $file;

    }catch(Exception $e){
        throw new BException(tr('file_ensure_file(): Failed'), $e);
    }
}



/*
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
 * @param null octal $mode If the specified $path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
 * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
 * @return string The specified file
 */
function file_ensure_path($path, $mode = null, $clear = false){
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
            file_delete($path);
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
                        file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode){
                            file_delete($path);
                        });

                        return file_ensure_path($path, $mode);
                    }

                    continue;

                }elseif(is_link($path)){
                    /*
                     * This is a dead symlink, delete it
                     */
                    file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode){
                        file_delete($path);
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
             * Some other file is in the way. Delete the file, and retry
             */
            file_delete($path);
            return file_ensure_path($path, $mode);
        }

        return slash(realpath($path).'/');

    }catch(Exception $e){
        throw new BException(tr('file_ensure_path(): Failed to ensure path ":path"', array(':path' => $path)), $e);
    }
}



/*
 * Delete the path until directory is no longer empty
 */
function file_clear_path($path){
    try{
        if(!file_exists($path)){
            /*
             * This section does not exist, jump up to the next section
             */
            return file_clear_path(dirname($path));
        }

        if(!is_dir($path)){
            /*
             * This is a normal file. Delete it and continue with the directory above
             */
            unlink($path);

        }else{
            /*
             * This is a directory. See if its empty
             */
            $h        = opendir($path);
            $contents = false;

            while(($file = readdir($h)) !== false){
                /*
                 * Skip . and ..
                 */
                if(($file == '.') or ($file == '..')) continue;

                $contents = true;
                break;
            }

            closedir($h);

            if($contents){
                /*
                 * Do not remove anything more, there is contents here!
                 */
                return true;
            }

            /*
             * Remove this entry and continue;
             */
            try{
                file_execute_mode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function($path){
                    file_delete($path);
                });

            }catch(Exception $e){
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content
                 * in this directory, so it's no longer empty. Just register
                 * the event and leave it be.
                 */
                log_console(tr('file_clear_path(): Failed to remove empty path ":path" with exception ":e"', array(':path' => $path, ':e' => $e)), 'failed');
                return true;
            }
        }

        /*
         * Go one entry up and continue
         */
        $path = str_runtil(unslash($path), '/');
        file_clear_path($path);

    }catch(Exception $e){
        throw new BException(tr('file_clear_path(): Failed'), $e);
    }
}



/*
 * Return the extension of the specified filename
 */
function file_get_extension($filename){
    try{
        return str_rfrom($filename, '.');

    }catch(Exception $e){
        throw new BException(tr('file_get_extension(): Failed'), $e);
    }
}



/*
 * Return a file path for a temporary file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.90: Added documentation, expanded $create to be able to contain data for the temp file
 * @note: If the resolved temp file path already exist, it will be deleted, so take care when using $name!
 * @example
 * code
 * $result = file_temp('This is temporary data!');
 * showdie(file_get_contents($result));
 * /code
 *
 * This would return
 * code
 * This is temporary data!
 * /code
 *
 * @param boolean string $create If set to false, only the file path will be returned, the temporary file will NOT be created. If set to true, the file will be created. If set to a string, the temp file will be created with as contents the $create string
 * @param null string $name If specified, use ROOT/data/tmp/$name instead of a randomly generated filename
 * @return string The filename for the temp file
 */
function file_temp($create = true, $name = null){
    try{
        file_ensure_path(TMP);

        if(!$name){
            $name = substr(hash('sha1', uniqid().microtime()), 0, 12);
        }

        $path = TMP.$name;

        if(file_exists($path)){
    		file_delete($path);
        }

        if($create){
            if($create === true){
                touch($path);

            }else{
                if(!is_string($create)){
                    throw new BException(tr('file_temp(): Specified $create variable is of datatype ":type" but should be either false, true, or a data string that should be written to the temp file', array(':type' => gettype($create))), $e);
                }

                file_put_contents($path, $create);
            }
        }

        return $path;

    }catch(Exception $e){
        throw new BException(tr('file_temp(): Failed'), $e);
    }
}



/*
 * Tree delete
 *
 * Kindly taken from http://lixlpixel.org/recursive_function/php/recursive_directory_delete/
 * Slightly rewritten and cleaned up by Sven Oostenbrink
 */
function file_delete_tree($directory){
    try{
        $directory = unslash($directory);

        if(!file_exists($directory) and !is_link($directory)){
            /*
             * The path itself no (longer) exists. Maybe it was already
             * deleted, but the situation now is exactly how this function is
             * supposed to leave it behind, so we're okay and done!
             */
            return;

        }elseif(is_link($directory) or !is_dir($directory)){
            /*
             * This is a file (or symlink), fine, delete it and lets continue!
             */
            unlink($directory);
            return;
        }

        $handle = opendir($directory);

        while (false !== ($item = readdir($handle))){
            if($item != '.' && $item != '..'){
                $path = $directory.'/'.$item;

                if(is_dir($path)){
                    file_delete_tree($path);

                }else{
                    try{
                        unlink($path);

                    }catch(Exception $e){
                        /*
                         * Failed, very very likely access denied, so ATTEMPT to make writable and try again. If fails again, just exception
                         */
                        file_chmod_tree(dirname($path), 0660, 0770);
                        unlink($path);
                    }
                }
            }
        }

        closedir($handle);
        file_delete($directory);

    }catch(Exception $e){
        throw new BException(tr('file_delete_tree(): Failed'), $e);
    }
}



/*
 * Return the absolute path for the specified path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added function and documentation
 *
 * @param string $path
 * @return string The absolute path
 */
function file_absolute_path($path){
    try{
        if(!$path){
            return getcwd();
        }

        if($path[0] === '/'){
            return $path;
        }

        return slash(getcwd()).unslash($path);

    }catch(Exception $e){
        throw new BException('file_absolute_path(): Failed', $e);
    }
}



/*
 * Returns the mimetype data for the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added documentation
 *
 * @param string $file to be tested
 * @return string The mimetype data for the specified file
 */
function file_mimetype($file){
    static $finfo = false;

    try{
        /*
         * Check the specified file
         */
        if(!$file){
            throw new BException(tr('file_mimetype(): No file specified'), 'not-specified');
        }

        if(!is_file($file)){
            if(!file_exists($file)){
                throw new BException(tr('file_mimetype(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exist');
            }

            if(is_dir($file)){
                throw new BException(tr('file_mimetype(): Specified file ":file" is not a normal file but a directory', array(':file' => $file)), 'invalid');
            }

            throw new BException(tr('file_mimetype(): Specified file ":file" is not a file', array(':file' => $file)), 'invalid');
        }

        if(!$finfo){
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        }

        return finfo_file($finfo, $file);

    }catch(Exception $e){
        throw new BException(tr('file_mimetype(): Failed'), $e);
    }
}



/*
 * Returns true or false if file is ASCII or not
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added documentation
 *
 * @param string $file to be tested
 * @return bolean True if the file is a text file, false if not
 */
function file_is_text($file){
    try{
        if(str_until(file_mimetype($file), '/') == 'text') return true;
        if(str_from (file_mimetype($file), '/') == 'xml' ) return true;

        return false;

    }catch(Exception $e){
        throw new BException(tr('file_is_text(): Failed'), $e);
    }
}



/*
 * Returns true if the specified file exists and is a file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added documentation
 *
 * @param string $file The file to be tested
 * @return bolean True if the file exists and is a file
 */
function file_check($file){
    if(!file_exists($file)){
        throw new BException(tr('file_check(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
    }

    if(!is_file($file)){
        throw new BException(tr('file_check(): Specified file ":file" is not a file', array(':file' => $file)), 'notafile');
    }
}



/*
 * Return all files in a directory that match the specified pattern with optional recursion.
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4.40: Added documentation, upgraded function
 *
 * @param string $path The path from which
 * @param string $pattern
 * @param boolean $recursive If set to true, return all files below the specified path, including in sub directories
 * @return array The matched files
 */
function file_list_tree($path, $pattern = null, $recursive = true){
    try{
        /*
         * Validate path
         */
        if(!is_dir($path)){
            if(!is_file($path)){
                if(!file_exists($path)){
                    throw new BException(tr('file_list_tree(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exist');
                }

                throw new BException(tr('file_list_tree(): Specified path ":path" is not a directory or a file', array(':path' => $path)), 'invalid');
            }

            return array($path);
        }

        $retval = array();
        $fh    = opendir($path);

        /*
         * Go over all files
         */
        while(($filename = readdir($fh)) !== false){
            /*
             * Loop through the files, skipping . and .. and recursing if necessary
             */
            if(($filename == '.') or ($filename == '..')){
                continue;
            }

            /*
             * Does the file match the specified pattern?
             */
            if($pattern){
                $match = preg_match($pattern, $filename);

                if(!$match){
                    continue;
                }
            }

            /*
             * Get the complete file path
             */
            $file = slash($path).$filename;

            /*
             * Add the file to the list. If the file is a directory, then
             * recurse instead.
             *
             * Do NOT add the directory itself, only files!
             */
            if(is_dir($file) and $recursive){
                $retval = array_merge($retval, file_list_tree($file));

            }else{
                $retval[] = $file;
            }
        }

        closedir($fh);

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('file_list_tree(): Failed for ":path"', array(':path' => $path)), $e);
    }
}



/*
 * Delete a file, weather it exists or not, without error
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package files
 * @see file_restrict() This function uses file restrictions, see file_restrict() for more information
 *
 * @param mixed $patterns
 * @param boolean $clean_path
 * @param boolean $sudo
 * @param null list $restrictions A list of paths to which file_delete() operations will be restricted
 * @return natural The amount of orphaned files, and orphaned `files` entries found and processed
 */
function file_delete($patterns, $clean_path = false, $sudo = false, $restrictions = null){
    try{
        if(!$patterns){
            throw new BException(tr('file_delete(): No files or patterns specified'), 'not-specified');
        }

        foreach(array_force($patterns) as $pattern){
            file_restrict($pattern, $restrictions);
            safe_exec(array('commands' => array('rm', array('sudo' => $sudo, '-rf', $pattern))));

            if($clean_path){
                file_clear_path(dirname($patterns));
            }
        }

    }catch(Exception $e){
        throw new BException(tr('file_delete(): Failed'), $e);
    }
}



/*
 * Copy an entire tree with replace option
 *
 * Extensions (may be string or array with strings) sets which
 * file extensions will have search / replace. If set to false
 * all files will have search / replace applied.
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
function file_copy_tree($source, $destination, $search = null, $replace = null, $extensions = null, $mode = true, $novalidate = false){
    global $_CONFIG;

    try{
        /*
         * Choose between copy filemode (mode is null), set filemode ($mode is a string or octal number) or preset filemode (take from config, TRUE)
         */
        if(!is_bool($mode) and !is_null($mode)){
            if(is_string($mode)){
                $mode = intval($mode, 8);
            }

            $filemode = $mode;
        }

        if(substr($destination, 0, 1) != '/'){
            /*
             * This is not an absolute path
             */
            $destination = PWD.$destination;
        }

        /*
         * Validations
         */
        if(!$novalidate){
            /*
             * Prepare search / replace
             */
            if(!$search){
                /*
                 * We can only replace if we search
                 */
                $search     = null;
                $replace    = null;
                $extensions = null;

            }else{
                if(!is_array($extensions)){
                    $extensions = array($extensions);
                }

                if(!is_array($search)){
                    $search = explode(',', $search);
                }

                if(!is_array($replace)){
                    $replace = explode(',', $replace);
                }

                if(count($search) != count($replace)){
                    throw new BException(tr('file_copy_tree(): The search parameters count ":search" and replace parameters count ":replace" do not match', array(':search' => count($search), ':replace' => count($replace))), 'parameternomatch');
                }
            }

            if(!file_exists($source)){
                throw new BException(tr('file_copy_tree(): Specified source ":source" does not exist', array(':source' => $source)), 'not-exists');
            }

            $destination = unslash($destination);

            if(!file_exists($destination)){
// :TODO: Check if dirname() here is correct? It somehow does not make sense
                if(!file_exists(dirname($destination))){
                    throw new BException(tr('file_copy_tree(): Specified destination ":destination" does not exist', array(':destination' => dirname($destination))), 'not-exists');
                }

                if(!is_dir(dirname($destination))){
                    throw new BException(tr('file_copy_tree(): Specified destination ":destination" is not a directory', array(':destination' => dirname($destination))), 'not-directory');
                }

                if(is_dir($source)){
                    /*
                     * We are copying a directory, destination dir does not yet exist
                     */
                    mkdir($destination);

                }else{
                    /*
                     * We are copying just one file
                     */
                }

            }else{
                /*
                 * Destination already exists,
                 */
                if(is_dir($source)){
                    if(!is_dir($destination)){
                        throw new BException(tr('file_copy_tree(): Cannot copy source directory ":source" into destination file ":destination"', array(':source' => $source, ':destination' => $destination)), 'failed');
                    }

                }else{
                    /*
                     * Source is a file
                     */
                    if(!is_dir($destination)){
                        /*
                         * Remove destination file since it would be overwritten
                         */
                        file_delete($destination);
                    }
                }
            }
        }

        if(is_dir($source)){
            $source      = slash($source);
            $destination = slash($destination);

            foreach(scandir($source) as $file){
                if(($file == '.') or ($file == '..')){
                    /*
                     * Only replacing down
                     */
                    continue;
                }

                if(is_null($mode)){
                    $filemode = $_CONFIG['file']['dir_mode'];

                }elseif(is_link($source.$file)){
                    /*
                     * No file permissions for symlinks
                     */
                    $filemode = false;

                }else{
                    $filemode = fileperms($source.$file);
                }

                if(is_dir($source.$file)){
                    /*
                     * Recurse
                     */
                    if(file_exists($destination.$file)){
                        /*
                         * Destination path already exists. This -by the way- means that the
                         * destination tree was not clean
                         */
                        if(!is_dir($destination.$file)){
                            /*
                             * Were overwriting here!
                             */
                            file_delete($destination.$file);
                        }
                    }

                    file_ensure_path($destination.$file, $filemode);
                }

                file_copy_tree($source.$file, $destination.$file, $search, $replace, $extensions, $mode, true);
           }

        }else{
            if(is_link($source)){
                $link = readlink($source);

                if(substr($link, 0, 1) == '/'){
                    /*
                     * Absolute link, this is ok
                     */
                    $reallink = $link;

                }else{
                    /*
                     * Relative link, get the absolute path
                     */
                    $reallink = slash(dirname($source)).$link;
                }

                if(!file_exists($reallink)){
                    /*
                     * This symlink points to no file, its dead
                     */
                    log_console('file_copy_tree(): Encountered dead symlink "'.$source.'", copying anyway...', 'warning');
                }

                /*
                 * This is a symlink. Just create a new symlink that points to the same path
                 */
                return symlink($link, $destination);
            }

            /*
             * Determine mode
             */
            if($mode === null){
                $filemode = $_CONFIG['file']['file_mode'];

            }elseif($mode === true){
                $filemode = fileperms($source);
            }

            /*
             * Check if the file requires search / replace
             */
            if(!$search){
                /*
                 * No search specified, just copy tree
                 */
                $doreplace = false;

            }elseif(!$extensions){
                /*
                 * No extensions specified, search / replace all files in tree
                 */
                $doreplace = true;

            }else{
                /*
                 * Check extension if we should search / replace
                 */
                $doreplace = false;

                foreach($extensions as $extension){
                    $len = strlen($extension);

                    if(!substr($source, -$len, $len) != $extension){
                        $doreplace = true;
                        break;
                    }
                }
            }

            if(!$doreplace){
                /*
                 * Just a simple filecopy will suffice
                 */
                copy($source, $destination);

            }else{
                $data = file_get_contents($source);

                foreach($search as $id => $svalue){
                    if((substr($svalue, 0, 1 == '/')) and (substr($svalue, -1, 1 == '/'))){
                        /*
                         * Do a regex search / replace
                         */
                        $data = preg_replace($svalue, $replace[$id], $data);

                    }else{
                        /*
                         * Do a normal search / replace
                         */
                        $data = str_replace($svalue, $replace[$id], $data);
                    }
                }

                /*
                 * Done, now write to the target file!
                 */
                file_put_contents($destination, $data);
            }

            if($mode){
                /*
                 * Update file mode
                 */
                try{
                    chmod($destination, $filemode);

                }catch(Exception $e){
                    throw new BException(tr('file_copy_tree(): Failed to set filemode for ":destination"', array(':destination' => $destination)), $e);
                }
            }
        }

        return $destination;

    }catch(Exception $e){
        throw new BException(tr('file_copy_tree(): Failed'), $e);
    }
}



/*
 * Seach for $search file in $source, and move them all to $destination using the $rename result expression
 */
function file_rename($source, $destination, $search, $rename){
    try{
        /*
         * Validations
         */
        if(!file_exists($source)){
            throw new BException(tr('file_rename(): Specified source ":source" does not exist', array(':source' => $source)), 'exists');
        }

        if(!file_exists($destination)){
            throw new BException(tr('file_rename(): Specified destination ":destination" does not exist', array(':destination' => $destination)), 'exists');
        }

        if(!is_dir($destination)){
            throw new BException(tr('file_rename(): Specified destination ":destination" is not a directory', array(':destination' => $destination)), 'invalid');
        }

        if(is_file($source)){
            /*
             * Rename just one file
             */

        }else{
            /*
             * Rename all files in this directory
             */

        }


    }catch(Exception $e){
        throw new BException(tr('file_rename(): Failed'), $e);
    }
}



/*
 * Create temporary directory (sister function from tempnam)
 */
function file_temp_dir($prefix = '', $mode = null){
    global $_CONFIG;

    try{
        /*
         * Use default configged mode, or specific mode?
         */
        if($mode === null){
            $mode = $_CONFIG['file']['dir_mode'];
        }

        file_ensure_path($path = TMP);

        while(true){
            $unique = uniqid($prefix);

            if(!file_exists($path.$unique)){
                break;
            }
        }

        $path = $path.$unique;

        /*
         * Make sure the temp dir exists
         */
        file_ensure_path($path);

        return slash($path);

    }catch(Exception $e){
        throw new BException(tr('file_tempdir(): Failed'), $e);
    }
}



/*
 * chmod an entire directory, recursively
 * Copied from http://www.php.net/manual/en/function.chmod.php#84273
 */
function file_chmod_tree($path, $filemode, $dirmode = 0770){
    try{
        if(!is_dir($path)){
            return chmod($path, $filemode);
        }

        $dh = opendir($path);

        while (($file = readdir($dh)) !== false){
            if(($file != '.') or ($file != '..')) continue;

            $fullpath = $path.'/'.$file;

            if(is_link($fullpath)){
                /*
                 * This is a link. ignore it.
                 */

            }elseif(!is_dir($fullpath)){
                if(!chmod($fullpath, $filemode)){
                    throw new BException(tr('file_chmod_tree(): Failed to chmod file ":fullpath" to mode ":filemode"', array(':fullpath' => $fullpath, ':filemode' => $filemode)), 'failed');
                }

            }else{
                /*
                 * This is a directory, recurse
                 */
                file_chmod_tree($fullpath, $filemode, $dirmode);
            }
        }

        closedir($dh);

        if(!chmod($path, $dirmode)){
            throw new BException(tr('file_chmod_tree(): Failed to chmod directory ":path" to mode ":mode"', array(':path' => $path, ':mode' => $dirmode)), 'failed');
        }

        return true;

    }catch(Exception $e){
        throw new BException('file_chmod_tree(): Failed', $e);
    }
}



/*
 * Return the extension for the specified file
 */
function file_extension($file){
    return pathinfo($file, PATHINFO_EXTENSION);
}



/*
 * If the specified file is an HTTP, HTTPS, or FTP URL, then get it locally as a temp file
 */
function file_get_local($file, &$is_downloaded = false, $context = null){
    try{
        $context = file_create_stream_context($context);
        $file    = trim($file);

        if((stripos($file, 'http:') === false) and (stripos($file, 'https:') === false) and (stripos($file, 'ftp:') === false)){
            if(!file_exists($file)){
                throw new BException(tr('file_get_local(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
            }

            if(is_uploaded_file($file)){
                $tmp  = file_get_uploaded($file);
                $file = file_temp($file);

                rename($tmp, $file);
            }

            return $file;
        }

        /*
         * First download the file to a temporary location
         */
        $orgfile       = $file;
        $file          = str_replace(array('://', '/'), '-', $file);
        $file          = file_temp($file);
        $is_downloaded = true;

        file_ensure_path(dirname($file));
        file_put_contents($file, file_get_contents($orgfile, false, $context));

        return $file;

    }catch(Exception $e){
        $message = $e->getMessage();
        $message = strtolower($message);

        if(str_exists($message, '404 not found')){
            throw new BException(tr('file_get_local(): URL ":file" does not exist', array(':file' => $file)), 'file-404');
        }

        if(str_exists($message, '400 bad request')){
            throw new BException(tr('file_get_local(): URL ":file" is invalid', array(':file' => $file)), 'file-400');
        }

        throw new BException(tr('file_get_local(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Return a system path for the specified type
 */
function file_system_path($type, $path = ''){
    switch($type){
        case 'img':
            // FALLTHROUGH
        case 'image':
            return '/pub/img/'.$path;

        case 'css':
            // FALLTHROUGH
        case 'style':
            return '/pub/css/'.$path;

        default:
            throw new BException(tr('file_system_path(): Unknown type ":type" specified', array(':type' => $type)), 'unknown');
    }
}



/*
 * Pick and return a random file name from the specified path
 *
 * Warning: This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
 */
function file_random($path){
    try{
        if(!file_exists($path)){
            throw new BException(tr('file_random(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        if(!file_exists($path)){
            throw new BException(tr('file_random(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        $files = scandir($path);

        unset($files[array_search('.' , $files)]);
        unset($files[array_search('..', $files)]);

        if(!$files){
            throw new BException(tr('file_random(): The specified path ":path" contains no files', array(':path' => $path)), 'not-exists');
        }

        return slash($path).array_get_random($files);

    }catch(Exception $e){
        throw new BException(tr('file_random(): Failed'), $e);
    }
}



/*
 * Store a file temporarily with a label in $_SESSION['files'][label]
 */
function file_session_store($label, $file = null, $path = TMP){
    try{
        if($file === null){
            /*
             * No file specified, return the file name for the specified label
             * Then remove the temporary file and the label
             */
            if(isset($_SESSION['files'][$label])){
                $file = $_SESSION['files'][$label];
                unset($_SESSION['files'][$label]);
                return $file;
            }

            return false;
        }

        /*
         * Store this file temporary
         * Check if a file already exists. If so, remove it, and store this one.
         */
        if(!empty($_SESSION['files'][$label])){
            file_delete_tree($_SESSION['files'][$label]);
        }

        array_ensure($_SESSION, 'files');

        $target = file_move_to_target($file, $path, false, true, 1);

        $_SESSION['files'][$label] = $file;

        return $file;

    }catch(Exception $e){
        throw new BException(tr('file_session_store(): Failed'), $e);
    }
}



/*
 * Checks if the specified path exists, is a dir, and optionally, if its writable or not
 */
function file_check_dir($path, $writable = false){
    try{
        if(!file_exists($path)){
            throw new BException(tr('file_check_dir(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        if(!is_dir($path)){
            throw new BException(tr('file_check_dir(): The specified path ":path" is not a directory', array(':path' => $path)), 'notadirectory');
        }

        if($writable and !is_writable($path)){
            throw new BException(tr('file_check_dir(): The specified path ":path" is not writable', array(':path' => $path)), 'notwritable');
        }

    }catch(Exception $e){
        throw new BException(tr('file_check_dir(): Failed'), $e);
    }
}



/*
 * Send the specified file to the client as a download using the HTTP protocol with correct headers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.89: Rewrote function and added documentation
 *
 * @param params $params The file parameters
 * @return void
 */
function file_http_download($params){
    global $_CONFIG;

    try{
        array_ensure($params, 'file,data,name');
        array_default($params, 'restrictions', ROOT.'data/downloads');
        array_default($params, 'compression' , $_CONFIG['file']['download']['compression']);
        array_default($params, 'filename'    , basename($params['file']));
        array_default($params, 'attachment'  , false);
        array_default($params, 'die'         , true);

        /*
         * Validate the file name for the user
         */
        if(!$params['filename']){
            throw new BException(tr('file_http_download(): No filename specified. Note: This is not the file to be downloaded to the client, but the name it will have when saved on the clients storage'), 'not-specified');
        }

        if(!is_scalar($params['filename'])){
                throw new BException(tr('file_http_download(): Specified filename ":filename" is not scalar', array(':filename' => $params['filename'])), 'invalid');
        }

        if(mb_strlen($params['filename']) > 250){
                throw new BException(tr('file_http_download(): Specified filename ":filename" is too long, it cannot be longer than 250 characters', array(':filename' => $params['filename'])), 'invalid');
        }

        if($params['data']){
            /*
             * Send the specified data as a file to the client
             * Write the data to a temp file first so we can just upload from
             * there
             */
            if($params['file']){
                throw new BException(tr('file_http_download(): Both "file" and "data" were specified, these parameters are mutually exclusive. Please specify one or the other'), 'invalid');
            }

            $params['file'] = file_temp($params['data']);
            $params['data'] = $params['file'];
        }

        if(!$params['file']){
            throw new BException(tr('file_http_download(): No file or data specified to download to client'), 'not-specified');
        }

        /*
         * Send a file from disk
         * Validate data
         */
        if(!is_scalar($params['file'])){
            throw new BException(tr('file_http_download(): Specified file ":file" is not scalar', array(':file' => $params['file'])), 'invalid');
        }

        if(!file_exists($params['file'])){
            throw new BException(tr('file_http_download(): Specified file ":file" does not exist or is not accessible', array(':file' => $params['file'])), 'not-exists');
        }

        if(!is_readable($params['file'])){
            throw new BException(tr('file_http_download(): Specified file ":file" exists but is not readable', array(':file' => $params['file'])), 'not-readable');
        }

        file_restrict($params['file'], $params['restrictions']);

        /*
         * We have to send the right content type headers and we might need to
         * figure out if we need to use compression or not
         */
        $mimetype  = mime_content_type($params['file']);
        $primary   = str_until($mimetype, '/');
        $secondary = str_from($mimetype , '/');

        /*
         * What file mode will we use?
         */
        if(file_is_binary($primary, $secondary)){
            $mode = 'rb';

        }else{
            $mode = 'r';
        }

        /*
         * Do we need compression?
         */
        if($params['compression'] === 'auto'){
            /*
             * Detect if the file is already compressed. If so, we don't need
             * the server to try to compress the data stream too because it
             * won't do anything (possibly make it even worse)
             */
            $params['compression'] = !file_is_compressed($primary, $secondary);
        }

        if($params['compression']){
            if(is_executable('apache_setenv')){
                apache_setenv('no-gzip', 0);
            }

            ini_set('zlib.output_compression', 'On');

        }else{
            if(is_executable('apache_setenv')){
                apache_setenv('no-gzip', 1);
            }

            ini_set('zlib.output_compression', 'Off');
        }

        /*
         * Send the specified file to the client
         */
        $bytes = filesize($params['file']);
        log_file(tr('HTTP downloading ":bytes" bytes file ":file" to client as ":filename"', array(':bytes' => $bytes, ':filename' => $params['filename'], ':file' => $params['file'])), 'http-download', 'cyan');

// :TODO: Are these required?
        //header('Expires: -1');
        //header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: '.$mimetype);
        header("Content-length: ".$bytes);

        if($params['attachment']){
            /*
             * Instead of sending the file to the browser to display directly,
             * send it as a file attachement that will be downloaded to their
             * disk
             */
            header('Content-Disposition: attachment; filename="'.$params['filename'].'"');
        }

        $f = fopen($params['file'], $mode);
        fpassthru($f);
        fclose($f);

        /*
         * If we created a temporary file for a given data string, then delete
         * the temp file
         */
        if($params['data']){
            file_delete($params['data']);
        }

        if($params['die']){
            die();
        }

    }catch(Exception $e){
        /*
         * If we created a temporary file for a given data string, then delete
         * the temp file
         */
        if($params['data']){
            file_delete($params['data']);
        }

        throw new BException(tr('file_http_download(): Failed'), $e);
    }
}



/*
 * Return true if the specified mimetype is for a binary file or false if it is for a text file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.90: Added function and documentation
 *
 * @param string $mimetype The primary mimetype section to check. If the mimetype is "text/plain", this variable would receive "text". You can also leave $secondary empty and specify the complete mimetype "text/plain" here, both will work
 * @param string $mimetype The secondary mimetype section to check. If the mimetype is "text/plain", this variable would receive "plain". If the complete mimetype is specified in $primary, you can leave this one empty
 * @return boolean True if the specified mimetype is for a binary file, false if it is a text file
 */
function file_is_binary($primary, $secondary = null){
    try{
// :TODO: IMPROVE THIS! Loads of files that are not text/ are still not binary
        /*
         * Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
         */
        if(!$secondary){
            if(!str_exists($primary, '/')){
                throw new BException(tr('file_is_compressed(): Invalid primary mimetype data "" specified. Either specify the complete mimetype in $primary, or specify the independant primary and secondary sections in $primary and $secondary', array(':primary' => $primary)), $e);
            }

            $secondary = str_from($primary , '/');
            $primary   = str_until($primary, '/');
        }

        /*
         * Check the mimetype data
         */
        switch($primary){
            case 'text':
                /*
                 * Readonly
                 */
                return false;

            default:
                switch($secondary){
                    case 'json':
                        // FALLTHROUGH
                    case 'ld+json':
                        // FALLTHROUGH
                    case 'svg+xml':
                        // FALLTHROUGH
                    case 'x-csh':
                        // FALLTHROUGH
                    case 'x-sh':
                        // FALLTHROUGH
                    case 'xhtml+xml':
                        // FALLTHROUGH
                    case 'xml':
                        // FALLTHROUGH
                    case 'xml':
                        // FALLTHROUGH
                    case 'vnd.mozilla.xul+xml':
                        /*
                         * This should be text
                         */
                        return false;
                }
        }

        /*
         * This should be binary
         */
        return true;

    }catch(Exception $e){
        throw new BException('file_is_binary(): Failed', $e);
    }
}



/*
 * Return true if the specified mimetype is for a compressed file, false if not
 *
 * This function will check the primary and secondary sections of the mimetype and depending on their values, determine if the file format should use compression or not
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.90: Added function and documentation
 *
 * @param string $mimetype The primary mimetype section to check. If the mimetype is "text/plain", this variable would receive "text". You can also leave $secondary empty and specify the complete mimetype "text/plain" here, both will work
 * @param string $mimetype The secondary mimetype section to check. If the mimetype is "text/plain", this variable would receive "plain". If the complete mimetype is specified in $primary, you can leave this one empty
 * @return boolean True if the specified mimetype is for a compressed file, false if not
 */
function file_is_compressed($primary, $secondary = null){
    try{
// :TODO: IMPROVE THIS! Loads of files that may be mis detected
        /*
         * Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
         */
        if(!$secondary){
            if(!str_exists($primary, '/')){
                throw new BException(tr('file_is_compressed(): Invalid primary mimetype data "" specified. Either specify the complete mimetype in $primary, or specify the independant primary and secondary sections in $primary and $secondary', array(':primary' => $primary)), $e);
            }

            $secondary = str_from($primary , '/');
            $primary   = str_until($primary, '/');
        }

        /*
         * Check the mimetype data
         */
        if(str_exists($secondary, 'compressed')){
            /*
             * This file is already compressed
             */
            return true;

        }elseif(str_exists($secondary, 'zip')){
            /*
             * This file is already compressed
             */
            return true;

        }else{
            switch($secondary){
                case 'jpeg':
                    // FALLTHROUGH
                case 'mpeg':
                    // FALLTHROUGH
                case 'ogg':
                    /*
                     * This file is already compressed
                     */
                    return true;

                default:
                    switch($primary){
                        case 'audio':
                            switch($secondary){
                                case 'mpeg':
                                    // FALLTHROUGH
                                case 'ogg':
                            }
                            break;

                        case 'image':
                            break;

                        case 'video':
                            break;

                        default:
                            /*
                             * This file probably is not compressed
                             */
                            return false;
                    }
            }
        }

    }catch(Exception $e){
        throw new BException('template_function(): Failed', $e);
    }
}



/*
 * Send the specified file to the client using the HTTP protocol with correct headers
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.92: Rewrote function and added documentation
 *
 * @param $file
 * @return void
 */
function file_http_send($params){
    global $_CONFIG;

    try{
        array_ensure($params, 'file,data,name');
        array_default($params, 'restrictions', ROOT.'data/downloads');
        array_default($params, 'compression' , $_CONFIG['file']['download']['compression']);
        array_default($params, 'filename'    , basename($params['file']));
        array_default($params, 'die'         , true);

        /*
         * Do we need compression?
         */
        if($params['compression']){
            apache_setenv('no-gzip', 0);
            ini_set('zlib.output_compression', 'On');

        }else{
            apache_setenv('no-gzip', 1);
            ini_set('zlib.output_compression', 'Off');
        }

        /*
         * Validate the file name for the user
         */
        if(!$params['filename']){
            throw new BException(tr('file_http_download(): No filename specified. Note: This is not the file to be downloaded to the client, but the name it will have when saved on the clients storage'), 'not-specified');
        }

        if(!is_scalar($params['filename'])){
                throw new BException(tr('file_http_download(): Specified filename ":filename" is not scalar', array(':filename' => $params['filename'])), 'invalid');
        }

        if(mb_strlen($params['filename']) > 250){
                throw new BException(tr('file_http_download(): Specified filename ":filename" is too long, it cannot be longer than 250 characters', array(':filename' => $params['filename'])), 'invalid');
        }

        if($params['data']){
            /*
             * Send the specified data as a file to the client
             * Write the data to a temp file first so we can just upload from
             * there
             */
            if($params['file']){
                throw new BException(tr('file_http_download(): Both "file" and "data" were specified, these parameters are mutually exclusive. Please specify one or the other'), 'invalid');
            }

            $params['file'] = file_temp($params['data']);
            unset($params['data']);
        }

        if(!$params['file']){
            throw new BException(tr('file_http_download(): No file or data specified to download to client'), 'not-specified');
        }

        /*
         * Send a file from disk
         * Validate data
         */
        if(!is_scalar($params['file'])){
            throw new BException(tr('file_http_download(): Specified file ":file" is not scalar', array(':file' => $params['file'])), 'invalid');
        }

        if(file_exists($params['file'])){
            throw new BException(tr('file_http_download(): Specified file ":file" does not exist or is not accessible', array(':file' => $params['file'])), 'not-exists');
        }

        if(is_readable($params['file'])){
            throw new BException(tr('file_http_download(): Specified file ":file" exists but is not readable', array(':file' => $params['file'])), 'not-readable');
        }

        file_restrict($params['file'], $params['restrictions']);

        /*
         * We have to send the right content type headers
         */
        $mimetype = mime_content_type($params['file']);

        /*
         * Send the specified file to the client
         */
        $bytes = filesize($params['file']);
        log_file(tr('HTTP downloading ":bytes" bytes file ":file" to client as ":filename"', array(':bytes' => $bytes, ':filename' => $params['filename'], ':file' => $params['file'])), 'http-download', 'cyan');

// :TODO: Are these required?
        //header('Expires: -1');
        //header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: '.$mimetype);
        header("Content-length: ".$bytes);
        header('Content-Disposition: attachment; filename="'.$params['filename'].'"');

        $f = fopen($params['file']);
        fpassthru($f);
        fclose($f);

        if($params['die']){
            die();
        }

    }catch(Exception $e){
        throw new BException(tr('file_http_download(): Failed'), $e);
    }
}



/*
 * Copy a file with progress notification
 *
 * @example:
 * function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max){
 *     if($notification_code == STREAM_NOTIFY_PROGRESS){
 *         // save $bytes_transferred and $bytes_max to file or database
 *     }
 * }
 *
 * file_copy_progress($source, $target, 'stream_notification_callback');
 */
function file_copy_progress($source, $target, $callback){
    try{
        $c = stream_context_create();
        stream_context_set_params($c, array('notification' => $callback));
        copy($source, $target, $c);

    }catch(Exception $e){
        throw new BException(tr('file_copy_progress(): Failed'), $e);
    }
}



/*
 *
 */
function file_mode_readable($mode){
    try{
        $retval = '';
        $mode   = substr((string) decoct($mode), -3, 3);

        for($i = 0; $i < 3; $i++){
            $number = (integer) substr($mode, $i, 1);

            if(($number - 4) >= 0){
                $retval .= 'r';
                $number -= 4;

            }else{
                $retval .= '-';
            }

            if(($number - 2) >= 0){
                $retval .= 'w';
                $number -= 2;

            }else{
                $retval .= '-';
            }

            if(($number - 1) >= 0){
                $retval .= 'x';

            }else{
                $retval .= '-';
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('file_mode_readable(): Failed'), $e);
    }
}



/*
 * Calculate either the total size of the tree under the specified path, or the amount of files (directories not included in count)
 * @$method (string) either "size" or "count", the required value to return
 */
function file_tree($path, $method){
    try{
        if(!file_exists($path)){
            throw new BException(tr('file_tree(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        switch($method){
            case 'size':
                // FALLTHROUGH
            case 'count':
                break;

            default:
                throw new BException(tr('file_tree(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
        }

        $retval = 0;
        $path   = slash($path);

        foreach(scandir($path) as $file){
            if(($file == '.') or ($file == '..')) continue;

            if(is_dir($path.$file)){
                $retval += file_tree($path.$file, $method);

            }else{
                switch($method){
                    case 'size':
                        $retval += filesize($path.$file);
                        break;

                    case 'count':
                        $retval++;
                        break;
                }
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('file_tree(): Failed'), $e);
    }
}



/*
 *
 */
function file_ensure_writable($path){
    try{
        if(is_writable($path)){
            return false;
        }

        $perms = fileperms($path);

        if(is_dir($path)){
            chmod($path, 0770);

        }else{
            if(is_executable($path)){
                chmod($path, 0770);

            }else{
                chmod($path, 0660);
            }
        }

        return $perms;

    }catch(Exception $e){
        throw new BException(tr('file_ensure_writable(): Failed'), $e);
    }
}



/*
 * Returns array with all permission information about the specified file.
 *
 * Idea taken from http://php.net/manual/en/function.fileperms.php
 */
function file_type($file){
    try{
        $perms  = fileperms($file);

        $socket    = (($perms & 0xC000) == 0xC000);
        $symlink   = (($perms & 0xA000) == 0xA000);
        $regular   = (($perms & 0x8000) == 0x8000);
        $bdevice   = (($perms & 0x6000) == 0x6000);
        $cdevice   = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe  = (($perms & 0x1000) == 0x1000);

        if($socket){
            /*
             * This file is a socket
             */
            return 'socket';

        }elseif($symlink){
            /*
             * This file is a symbolic link
             */
            return 'symbolic link';

        }elseif($regular){
            /*
             * This file is a regular file
             */
            return 'regular file';

        }elseif($bdevice){
            /*
             * This file is a block device
             */
            return 'block device';

        }elseif($directory){
            /*
             * This file is a directory
             */
            return 'directory';

        }elseif($cdevice){
            /*
             * This file is a character device
             */
            return 'character device';

        }elseif($fifopipe){
            /*
             * This file is a FIFO pipe
             */
            return 'fifo pipe';
        }

        /*
         * This file is an unknown type
         */
        return 'unknown';

    }catch(Exception $e){
        throw new BException(tr('file_type(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Returns array with all permission information about the specified file.
 *
 * Idea taken from http://php.net/manual/en/function.fileperms.php
 */
function file_get_permissions($file){
    try{
        $perms  = fileperms($file);
        $retval = array();

        $retval['socket']    = (($perms & 0xC000) == 0xC000);
        $retval['symlink']   = (($perms & 0xA000) == 0xA000);
        $retval['regular']   = (($perms & 0x8000) == 0x8000);
        $retval['bdevice']   = (($perms & 0x6000) == 0x6000);
        $retval['cdevice']   = (($perms & 0x2000) == 0x2000);
        $retval['directory'] = (($perms & 0x4000) == 0x4000);
        $retval['fifopipe']  = (($perms & 0x1000) == 0x1000);
        $retval['perms']     = $perms;
        $retval['unknown']   = false;

        if($retval['socket']){
            /*
             * This file is a socket
             */
            $retval['mode'] = 's';
            $retval['type'] = 'socket';

        }elseif($retval['symlink']){
            /*
             * This file is a symbolic link
             */
            $retval['mode'] = 'l';
            $retval['type'] = 'symbolic link';

        }elseif($retval['regular']){
            /*
             * This file is a regular file
             */
            $retval['mode'] = '-';
            $retval['type'] = 'regular file';

        }elseif($retval['bdevice']){
            /*
             * This file is a block device
             */
            $retval['mode'] = 'b';
            $retval['type'] = 'block device';

        }elseif($retval['directory']){
            /*
             * This file is a directory
             */
            $retval['mode'] = 'd';
            $retval['type'] = 'directory';

        }elseif($retval['cdevice']){
            /*
             * This file is a character device
             */
            $retval['mode'] = 'c';
            $retval['type'] = 'character device';

        }elseif($retval['fifopipe']){
            /*
             * This file is a FIFO pipe
             */
            $retval['mode'] = 'p';
            $retval['type'] = 'fifo pipe';

        }else{
            /*
             * This file is an unknown type
             */
            $retval['mode']    = 'u';
            $retval['type']    = 'unknown';
            $retval['unknown'] = true;
        }

        $retval['owner'] = array('r' =>  ($perms & 0x0100),
                                 'w' =>  ($perms & 0x0080),
                                 'x' => (($perms & 0x0040) and !($perms & 0x0800)),
                                 's' => (($perms & 0x0040) and  ($perms & 0x0800)),
                                 'S' =>  ($perms & 0x0800));

        $retval['group'] = array('r' =>  ($perms & 0x0020),
                                 'w' =>  ($perms & 0x0010),
                                 'x' => (($perms & 0x0008) and !($perms & 0x0400)),
                                 's' => (($perms & 0x0008) and  ($perms & 0x0400)),
                                 'S' =>  ($perms & 0x0400));

        $retval['other'] = array('r' =>  ($perms & 0x0004),
                                 'w' =>  ($perms & 0x0002),
                                 'x' => (($perms & 0x0001) and !($perms & 0x0200)),
                                 't' => (($perms & 0x0001) and  ($perms & 0x0200)),
                                 'T' =>  ($perms & 0x0200));

        /*
         * Owner
         */
        $retval['mode'] .= (($perms & 0x0100) ? 'r' : '-');
        $retval['mode'] .= (($perms & 0x0080) ? 'w' : '-');
        $retval['mode'] .= (($perms & 0x0040) ?
                           (($perms & 0x0800) ? 's' : 'x' ) :
                           (($perms & 0x0800) ? 'S' : '-'));

        /*
         * Group
         */
        $retval['mode'] .= (($perms & 0x0020) ? 'r' : '-');
        $retval['mode'] .= (($perms & 0x0010) ? 'w' : '-');
        $retval['mode'] .= (($perms & 0x0008) ?
                           (($perms & 0x0400) ? 's' : 'x' ) :
                           (($perms & 0x0400) ? 'S' : '-'));

        /*
         * World
         */
        $retval['mode'] .= (($perms & 0x0004) ? 'r' : '-');
        $retval['mode'] .= (($perms & 0x0002) ? 'w' : '-');
        $retval['mode'] .= (($perms & 0x0001) ?
                           (($perms & 0x0200) ? 't' : 'x' ) :
                           (($perms & 0x0200) ? 'T' : '-'));

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('file_get_permissions(): Failed'), $e);
    }
}



/*
 * Execute the specified callback on all files in the specified tree
 */
function file_tree_execute($params){
    try{
        array_ensure($params);
        array_default($params, 'ignore_exceptions', true);
        array_default($params, 'path'             , null);
        array_default($params, 'filters'          , null);
        array_default($params, 'follow_symlinks'  , false);
        array_default($params, 'follow_hidden'    , false);
        array_default($params, 'recursive'        , false);
        array_default($params, 'execute_directory', false);
        array_default($params, 'params'           , null);

        /*
         * Validate data
         */
        if(empty($params['callback'])){
            throw new BException(tr('file_tree_execute(): No callback function specified'), 'not-specified');
        }

        if(!is_callable($params['callback'])){
            throw new BException(tr('file_tree_execute(): Specified callback is not a function'), 'invalid');
        }

        if(!($params['path'])){
            throw new BException(tr('file_tree_execute(): No path specified'), 'not-specified');
        }

        if(substr($params['path'], 0, 1) !== '/'){
            throw new BException(tr('file_tree_execute(): No absolute path specified'), 'invalid');
        }

        if(!file_exists($params['path'])){
            throw new BException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $params['path'])), 'not-exists');
        }

        /*
         * Follow hidden files?
         */
        if((substr(basename($params['path']), 0, 1) == '.') and !$params['follow_hidden']){
            if(VERBOSE and PLATFORM_CLI){
                log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $params['path'])), 'yellow');
            }

            return 0;
        }

        /*
         * Filter this path?
         */
        foreach(array_force($params['filters']) as $filter){
            if(preg_match($filter, $params['path'])){
                if(VERBOSE and PLATFORM_CLI){
                    log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $params['path'], ':filter' => $filter)), 'yellow');
                }

                return 0;
            }
        }

        $count = 0;
        $type  = file_type($params['path']);

        switch($type){
            case 'regular file':
                $params['callback']($params['path']);
                $count++;

                log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $params['path'])), 'VERYVERBOSEDOT/green');
                break;

            case 'symlink':
                if($params['follow_symlinks']){
                    $params['path'] = readlink($params['path']);
                    $count += file_tree_execute($params);
                }

                break;

            case 'directory':
                $h    = opendir($params['path']);
                $path = slash($params['path']);

                while(($file = readdir($h)) !== false){
                    try{
                        if(($file == '.') or ($file == '..')) continue;

                        if((substr(basename($file), 0, 1) == '.') and !$params['follow_hidden']){
                            if(VERBOSE and PLATFORM_CLI){
                                log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $file)), 'yellow');
                            }

                            continue;
                        }

                        $file = $path.$file;

                        if(!file_exists($file)){
                            throw new BException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $file)), 'not-exists');
                        }

                        $type = file_type($file);

                        switch($type){
                            case 'link':
                                if(!$params['follow_symlinks']){
                                    continue 2;
                                }

                                $file = readlink($file);

                                /*
                                 * We got the target file, but we don't know what it is.
                                 * Restart the process recursively to process this file
                                 */

                                // FALLTHROUGH

                            case 'directory':
                                // FALLTHROUGH
                            case 'regular file':
                                if(($type != 'directory') or $params['execute_directory']){
                                    /*
                                     * Filter this path?
                                     */
                                    $skip = false;

                                    foreach(array_force($params['filters']) as $filter){
                                        if(preg_match($filter, $file)){
                                            if(VERBOSE and PLATFORM_CLI){
                                                log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $params['path'], ':filter' => $filter)), 'yellow');
                                            }

                                            $skip = true;
                                        }
                                    }

                                    if(!$skip){
                                        $result = $params['callback']($file, $type, $params['params']);
                                        $count++;

                                        if($result === false){
                                            /*
                                             * When the callback returned boolean false, cancel all other files
                                             */
                                            log_console(tr('file_tree_execute(): callback returned FALSE for file ":file", skipping rest of directory contents!', array(':file' => $file)), 'yellow');
                                            goto end;
                                        }

                                        log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $file)), 'VERYVERBOSEDOT/green');
                                    }
                                }

                                if(($type == 'directory') and $params['recursive']){
                                    $params['path'] = $file;
                                    $count         += file_tree_execute($params);
                                }

                                break;

                            default:
                                /*
                                 * Skip this unsupported file type
                                 */
                                if(VERBOSE and PLATFORM_CLI){
                                    log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $type)), 'yellow');
                                }
                        }

                    }catch(Exception $e){
                        if(!$params['ignore_exceptions']){
                            throw $e;
                        }

                        if($e->getCode() === 'not-exists'){
                            log_console(tr('file_tree_execute(): Skipping file ":file", it does not exist (in case of a symlink, it may be that the target does not exist)', array(':file' => $file)), 'VERBOSE/yellow');

                        }else{
                            log_console($e);
                        }
                    }
                }

                end:
                closedir($h);

                break;

            default:
                /*
                 * Skip this unsupported file type
                 */
                if(VERBOSE and PLATFORM_CLI){
                    log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $params['path'])), 'yellow');
                }
        }

        return $count;

    }catch(Exception $e){
        throw new BException(tr('file_tree_execute(): Failed'), $e);
    }
}



/*
 * If specified path is not absolute, then return a path that is sure to start
 * from the current working directory
 */
function file_absolute($path, $root = null){
    try{
        if(empty($root)){
            $root = slash(getcwd());
        }

        if(substr($path, 0, 1) !== '/'){
            $path = $root.$path;
        }

        return $path;

    }catch(Exception $e){
        throw new BException(tr('file_absolute(): Failed'), $e);
    }
}



/*
 * If specified path is not absolute, then return a path that is sure to start
 * within ROOT
 */
function file_root($path){
    try{
        if(substr($path, 0, 1) !== '/'){
            $path = ROOT.$path;
        }

        return $path;

    }catch(Exception $e){
        throw new BException(tr('file_root(): Failed'), $e);
    }
}



/*
 * Execute the specified callback after setting the specified mode on the
 * specified path. Once the callback has finished, return to the original file
 * mode.
 *
 * The callback function signature is like this:
 * $callback($path, $params, $mode)
 */
function file_execute_mode($path, $mode, $callback, $params = null){
    try{
        if(!file_exists($path)){
            throw new BException(tr('file_execute_mode(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        if(!is_string($callback) and !is_callable($callback)){
            throw new BException(tr('file_execute_mode(): Specified callback ":callback" is invalid, it should be a string or a callable function', array(':callback' => $callback)), 'invalid');
        }

        if($mode){
            $original_mode = fileperms($path);
            chmod($path, $mode);
        }

        if(is_dir($path)){
            $path = slash($path);
        }

        $retval = $callback($path, $params, $mode);

        if($mode){
            chmod($path, $original_mode);
        }

        return $retval;

    }catch(Exception $e){
        throw new BException(tr('file_execute_mode(): Failed for path ":path"', array(':path' => $path)), $e);
    }
}



/*
 *
 */
function file_link_exists($file){
    if(file_exists($file)){
        return true;
    }

    if(is_link($file)){
        throw new BException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exists');
    }

    throw new BException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exists');
}



/*
 * Open the specified source, read the contents, and replace $search with $replace. Write results in $target
 * $replaces should be a $search => $replace key value array, where the $search values are regex expressions
 */
function file_search_replace($source, $target, $replaces){
    try{
        if(!file_exists($source)){
            throw new BException(tr('file_search_replace(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

        if(!file_exists(dirname($target))){
            throw new BException(tr('file_search_replace(): Specified target path ":targetg" does not exist', array(':target' => $target)), 'not-exists');
        }

        if(!is_array($replaces)){
            throw new BException(tr('file_search_replace(): Specified $replaces ":replaces" should be a search => replace array', array(':replaces' => $replaces)), 'invalid');
        }

        $fs       = fopen($source, 'r');
        $ft       = fopen($target, 'w');

        $position = 0;
        $length   = 8192;
        $filesize = filesize($source);

        while($position < $filesize){
             $data      = fread($fs, $length);
             $position += $length;
             fseek($fs, $position);

             /*
              * Execute search / replaces
              */
             foreach($replaces as $search => $replace){
                $data = preg_replace($search, $replace, $data);
             }

             fwrite($ft, $data, strlen($data));
        }

        fclose($fs);
        fclose($ft);

    }catch(Exception $e){
        throw new BException(tr('file_search_replace(): Failed'), $e);
    }
}



/*
 * Return line count for this file
 */
function file_line_count($source){
    try{
        if(!file_exists($source)){
            throw new BException(tr('file_line_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

    }catch(Exception $e){
        throw new BException(tr('file_line_count(): Failed'), $e);
    }
}



/*
 * Return word count for this file
 */
function file_word_count($source){
    try{
        if(!file_exists($source)){
            throw new BException(tr('file_word_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

    }catch(Exception $e){
        throw new BException(tr('file_word_count(): Failed'), $e);
    }
}



/*
 * Scan the entire file path upward for the specified file.
 * If the specified file doesn't exist in the specified path, go one dir up,
 * all the way to root /
 */
function file_scan($path, $file){
    try{
        if(!file_exists($path)){
            throw new BException(tr('file_scan(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        while(strlen($path) > 1){
            $path = slash($path);

            if(file_exists($path.$file)){
                /*
                 * The requested file is found! Return the path where it was found
                 */
                return $path;
            }

            $path = dirname($path);
        }

        return false;

    }catch(Exception $e){
        throw new BException(tr('file_word_count(): Failed'), $e);
    }
}



/*
 * Move specified path to a backup
 */
function file_move_to_backup($path){
    try{
        if(!file_exists($path)){
            /*
             * Specified path doesn't exist, just ignore
             */
            return false;
        }

        $backup_path = $path.'~'.date_convert(null, 'Ymd-His');

        /*
         * Main sitemap file already exist, move to backup
         */
        if(file_exists($backup_path)){
            /*
             * Backup already exists as well, script run twice
             * in under a second. Delete the current one
             * as the backup was generated less than a second
             * ago
             */
            file_delete($path);
            return true;
        }

        rename($path, $backup_path);
        return true;

    }catch(Exception $e){
        throw new BException(tr('file_move_to_backup(): Failed'), $e);
    }
}



/*
 * Update the specified file owner and group
 */
function file_chown($file, $user = null, $group = null){
    try{
        if(!$user){
             $user = posix_getpwuid(posix_getuid());
             $user = $user['name'];
        }

        if(!$group){
             $group = posix_getpwuid(posix_getuid());
             $group = $group['name'];
        }

        $file = realpath($file);

        if(!$file){
            throw new BException(tr('file_chown(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
        }

        if(!strstr($file, ROOT)){
            throw new BException(tr('file_chown(): Specified file ":file" is not in the projects ROOT path ":path"', array(':path' => $path, ':file' => $file)), 'invalid');
        }

        safe_exec(array('commands' => array('chown', array('sudo' => true, $user.':'.$group, $file))));

    }catch(Exception $e){
        throw new BException(tr('file_chown(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 *
 * @param string $path
 * @param string $prefix
 * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
 */
function file_path_contains_symlink($path, $prefix = null){
    try{
        if(!$path){
            throw new BException(tr('file_path_contains_symlink(): No path specified'), 'not-specified');
        }

        if(substr($path, 0, 1) === '/'){
            if($prefix){
                throw new BException(tr('file_path_contains_symlink(): The specified path ":path" is absolute, which requires $prefix to be null, but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
            }

            $location = '/';

        }else{
            /*
             * Specified $path is relative, so prefix it with $prefix
             */
            if(substr($prefix, 0, 1) !== '/'){
                throw new BException(tr('file_path_contains_symlink(): The specified path ":path" is relative, which requires an absolute $prefix but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
            }

            $location = str_ends($prefix, '/');
        }

        $path = str_ends_not(str_starts_not($path, '/'), '/');

        foreach(explode('/', $path) as $section){
            $location .= $section;

            if(!file_exists($location)){
                throw new BException(tr('file_path_contains_symlink(): The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', array(':path' => $path, ':prefix' => $prefix, ':location' => $location)), 'not-exists');
            }

            if(is_link($location)){
                return true;
            }

            $location .= '/';
        }

        return false;

    }catch(Exception $e){
        throw new BException(tr('file_path_contains_symlink(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see https://secure.php.net/manual/en/migration56.openssl.php
 * @see https://secure.php.net/manual/en/function.stream-context-create.php
 * @see https://secure.php.net/manual/en/wrappers.php
 * @see https://secure.php.net/manual/en/context.php
 *
 * @param array $context The stream context
 * @param string $prefix
 * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
 */
function file_create_stream_context($context){
    try{
        if(!$context) return null;

        if(!is_array($context)){
            throw new BException(tr('file_create_stream_context(): Specified context is invalid, should be an array but is an ":type"', array(':type' => gettype($context))), 'invalid');
        }

        return stream_context_create($context);

    }catch(Exception $e){
        throw new BException(tr('file_create_stream_context(): Failed'), $e);
    }
}



/*
 * Perform a "sed" action on the specified file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see safe_exec()
 * @version 2.4.22: Added function and documentation
 *
 * @param params $params The parameters for sed
 * @param null mixed $params[ok_exitcodes]
 * @param null boolean $params[sudo] If set to true, the sed command will be executed using sudo
 * @param null mixed $params[function]
 * @param null mixed $params[background]
 * @return void()
 */
function file_sed($params){
    try{
        array_ensure($params, 'ok_exitcodes,function,sudo,background,domain');

        if(empty($params['source'])){
            throw new BException(tr('file_sed(): No source file specified'), 'not-specified');
        }

        if(empty($params['regex'])){
            throw new BException(tr('file_sed(): No regex specified'), 'not-specified');
        }

        if(empty($params['target'])){
            $arguments[] = 'i';
            $arguments[] = $params['regex'];
            $arguments[] = $params['source'];

        }else{
            $arguments[] = $params['regex'];
            $arguments[] = $params['source'];
            $arguments['redirect'] = '> '.$params['target'];
        }

        if(!empty($params['sudo'])){
            $arguments['sudo'] = $params['sudo'];
        }

        safe_exec(array('domain'       => $params['domain'],
                        'background'   => $params['background'],
                        'function'     => $params['function'],
                        'ok_exitcodes' => $params['ok_exitcodes'],
                        'commands'     => array('sed' => $arguments)));

    }catch(Exception $e){
        throw new BException('file_sed(): Failed', $e);
    }
}



/*
 * Cat the output from one file to another
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see safe_exec()
 * @version 2.4.22: Added function and documentation
 *
 * @param params $params The parameters for sed
 * @param null mixed $params[ok_exitcodes]
 * @param null boolean $params[sudo] If set to true, the sed command will be executed using sudo
 * @param null mixed $params[function]
 * @param null mixed $params[background]
 * @return void()
 */
function file_cat($params){
    try{
        array_ensure($params, 'ok_exitcodes,function,sudo,background,domain');

        if(empty($params['source'])){
            throw new BException(tr('file_cat(): No source file specified'), 'not-specified');
        }

        if(empty($params['target'])){
            throw new BException(tr('file_cat(): No target file specified'), 'not-specified');
        }

        if(!empty($params['sudo'])){
            $arguments['sudo'] = $params['sudo'];
        }

        safe_exec(array('domain'       => $params['domain'],
                        'background'   => $params['background'],
                        'function'     => $params['function'],
                        'ok_exitcodes' => $params['ok_exitcodes'],
                        'commands'     => array('cat' => $arguments)));

    }catch(Exception $e){
        throw new BException('file_cat(): Failed', $e);
    }
}



/*
 * Ensure that the specified file is not in restricted zones. This applies to real paths, with their symlinks expaned
 *
 * Authorized areas, by default, are the following paths. Any other path will be restricted
 *
 * ROOT/data
 * /tmp/
 *
 * If $params is specified as a string, then the function will assume this is a single path and test it
 *
 * If $params is specified as an array, then the function will check for the following keys:
 *
 * * source
 * * target
 * * file
 * * path
 *
 * Any of these will be assumed to be a file path, and tested.
 *
 * If $params[unrestricted] is specified, the function will not test anything
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4.24: Added function and documentation
 *
 * @param mixed $params The parameters on which to restrict the specified file or path. May also simply be a file string, in which case the default parameters apply
 * @param null mixed $params[source]
 * @param null mixed $params[target]
 * @param null mixed $params[file]
 * @param null mixed $params[path]
 * @param null list $params[restrictions] list of paths to which the specified files must be restricted. This will only be used if $restrictions is NULL
 * @param null list $restrictions list of paths to which the specified files must be restricted
 * @return void
 */
function file_restrict($params, $restrictions = null){
    try{
        /*
         * Disable all restrictions?
         */
        if(!empty($params['unrestricted']) or ($restrictions === false)){
            /*
             * No restrictions required
             */
            return false;
        }

        /*
         * Determine what restrictions apply. The restrictions is a white list
         * containing the paths where the calling function is allowed to work
         */
        if(!$restrictions){
            /*
             * If the file was specified as an array, then the restrictions may
             * have been included in there for convenience.
             */
            if(is_array($params) and isset($params['restrictions'])){
                $restrictions = $params['restrictions'];
            }

            if(!$restrictions){
                /*
                 * Apply default restrictions
                 */
                $restrictions = array(ROOT.'data', '/tmp');
            }

        }else{
            /*
             * Restrictions may have been specified as a CSV list, ensure its an
             * array so we can process then all
             */
            $restrictions = array_force($restrictions);
        }

        /*
         * If this is a string containing a single path, then test it
         */
        if(is_string($params)){
            /*
             * The file or path to be checked must start with the $restriction
             * Unslash the $restriction to avoid checking a path like "/test/"
             * against a restriction "/test" and having it fail because of the
             * missing slash at the end
             */
            foreach($restrictions as $restriction){
                unslash($restriction);
                if(substr($params, 0, strlen($restriction)) === $restriction){
                    /*
                     * Passed!
                     */
                    return;
                }
            }

            throw new BException(tr('file_restrict(): The specified file or path ":path" is outside of the authorized paths', array(':path' => $params)), 'access-denied', $restrictions);
        }

        /*
         * Search for default fields
         */
        $keys = array('source', 'target', 'source_path', 'source_path', 'path');

        foreach($keys as $key){
            if(isset($params[$key])){
                /*
                 * All these must be tested
                 */
                try{
                    file_restrict($params[$key], $restrictions);

                }catch(Exception $e){
                    throw new BException(tr('file_restrict(): Failed for key ":key" test', array(':key' => $key)), $e);
                }
            }
        }

    }catch(Exception $e){
        throw new BException('file_restrict(): Failed', $e);
    }
}




/*
 * Locates the specifed command and returns it path
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.0.5: Added function and documentation
 * @version 2.4.16: Added $whereis support
 *
 * @param string $command The command searched for
 * @param boolean $whereis If set to true, instead of "which", "whereis" will be used
 * @return string The path of the specified file
 */
function file_which($command, $whereis = false){
    try{
        $result = safe_exec(array('ok_exitcodes' => '0,1',
                                  'commands'     => array(($whereis ? 'whereis' : 'which'), array($command))));

        $result = array_shift($result);

        return get_null($result);

    }catch(Exception $e){
        throw new BException('file_which(): Failed', $e);
    }
}
?>
