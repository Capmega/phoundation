<?php
/*
 * File library
 *
 * This library contains various file related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package file
 */



/*
 * Append specified data string to the end of the specified file
 */
function file_append($target, $data) {
    global $_CONFIG;

    try {
        /*
         * Open target
         */
        if (!file_exists(dirname($target))) {
            throw new CoreException(tr('file_append(): Specified target path ":target" does not exist', array(':target' => dirname($target))), 'not-exists');
        }

        $target_h = fopen($target, 'a');
        fwrite($target_h, $data);
        fclose($target_h);

    }catch(Exception $e) {
        throw new CoreException(tr('file_append(): Failed'), $e);
    }
}



/*
 * Concatenates a list of files to a target file
 */
function file_concat($target, $sources) {
    global $_CONFIG;

    try {
        if (!is_array($sources)) {
            $sources = array($sources);
        }

        /*
         * Open target
         */
        if (!file_exists(dirname($target))) {
            throw new CoreException(tr('file_concat(): Specified target path ":target" does not exist', array(':target' => dirname($target))), 'not-exists');
        }

        $target_h = fopen($target, 'a');

        foreach ($sources as $source) {
            $source_h = fopen($source, 'r');

            while (!feof($source_h)) {
                $data = fread($source_h, 8192);
                fwrite($target_h, $data);
            }

            fclose($source_h);
        }

        fclose($target_h);

    }catch(Exception $e) {
        throw new CoreException(tr('file_concat(): Failed'), $e);
    }
}



/*
 * Move uploaded image to correct target
 */
function file_get_uploaded($source) {
    global $_CONFIG;

    try {
        $destination = PATH_ROOT.'data/uploads/';

        if (is_array($source)) {
            /*
             * Asume this is a PHP file upload array entry
             */
            if (empty($source['tmp_name'])) {
                throw new CoreException(tr('file_move_uploaded(): Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry'), 'invalid');
            }

            $real   = $source['name'];
            $source = $source['tmp_name'];

        } else {
            $real   = basename($source);
        }


        is_file($source);
        Path::ensure($destination);

        /*
         * Ensure we're not overwriting anything!
         */
        if (file_exists($destination.$real)) {
            $real = Strings::untilReverse($real, '.').'_'.substr(uniqid(), -8, 8).'.'.Strings::fromReverse($real, '.');
        }

        if (!move_uploaded_file($source, $destination.$real)) {
            throw new CoreException(tr('file_move_uploaded(): Faield to move file ":source" to destination ":destination"', array(':source' => $source, ':destination' => $destination)), 'move');
        }

        /*
         * Return destination file
         */
        return $destination.$real;

    }catch(Exception $e) {
        throw new CoreException(tr('file_move_uploaded(): Failed'), $e);
    }
}



/*
 * Create a target, but don't put anything in it
 */
function file_assign_target($path, $extension = false, $singledir = false, $length = 4) {
    try {
        return file_move_to_target('', $path, $extension, $singledir, $length);

    }catch(Exception $e) {
        throw new CoreException(tr('file_assign_target(): Failed'), $e);
    }
}



/*
 * Create a target, but don't put anything in it, and return path+filename without extension
 */
function file_assign_target_clean($path, $extension = false, $singledir = false, $length = 4) {
    try {
        return str_replace($extension, '', file_move_to_target('', $path, $extension, $singledir, $length));

    }catch(Exception $e) {
        throw new CoreException(tr('file_assign_target_clean(): Failed'), $e);
    }
}



/*
 * Copy specified file, see file_move_to_target for implementation
 */
function file_copy_to_target($file, $path, $extension = false, $singledir = false, $length = 4) {
    try {
        if (is_array($file)) {
            throw new CoreException(tr('file_copy_to_target(): Specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', array(':file' => Strings::Log($file))));
        }

        return file_move_to_target($file, $path, $extension, $singledir, $length, true);

    }catch(Exception $e) {
        throw new CoreException(tr('file_copy_to_target(): Failed'), $e);
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
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_move_to_target($file, $path, $extension = false, $singledir = false, $length = 4, $copy = false, $context = null) {
    try {
        if (is_array($file)) {
            $upload = $file;
            $file   = $file['name'];
        }

        if (isset($upload) and $copy) {
            throw new CoreException(tr('file_move_to_target(): Copy option has been set, but specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', array(':file' => $file)));
        }

        $path     = Path::ensure($path);
        $filename = basename($file);

        if (!$filename) {
            /*
             * We always MUST have a filename
             */
            $filename = bin2hex(random_bytes(32));
        }

        /*
         * Ensure we have a local copy of the file to work with
         */
        if ($file) {
            $file = file_get_local($file, $is_downloaded, $context);
        }

        if (!$extension) {
            $extension = file_get_extension($filename);
        }

        if ($length) {
            $targetpath = Strings::slash(file_create_target_path($path, $singledir, $length));

        } else {
            $targetpath = Strings::slash($path);
        }

        $target = $targetpath.strtolower(str_convert_accents(Strings::untilReverse($filename, '.'), '-'));

        /*
         * Check if there is a "point" already in the extension
         * not obligatory at the start of the string
         */
        if ($extension) {
            if (strpos($extension, '.') === false) {
                $target .= '.'.$extension;

            } else {
               $target .= $extension;
            }
        }

        /*
         * Only move file is target does not yet exist
         */
        if (file_exists($target)) {
            if (isset($upload)) {
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
        if ($file) {
            if (isset($upload)) {
                /*
                 * This is an uploaded file
                 */
                file_move_uploaded($upload['tmp_name'], $target);

            } else {
                /*
                 * This is a normal file
                 */
                if ($copy and !$is_downloaded) {
                    copy($file, $target);

                } else {
                    rename($file, $target);
                    file_clear_path(dirname($file), false);
                }
            }
        }

        return Strings::from($target, $path);

    }catch(Exception $e) {
        throw new CoreException(tr('file_move_to_target(): Failed'), $e);
    }
}



/*
 * Creates a random path in specified base path (If it does not exist yet), and returns that path
 */
function file_create_target_path($path, $singledir = false, $length = false) {
    global $_CONFIG;

    try {
        if ($length === false) {
            $length = $_CONFIG['file']['target_path_size'];
        }

        $path = Strings::unslash(Path::ensure($path));

        if ($singledir) {
            /*
             * Assign path in one dir, like abcde/
             */
            $path = Strings::slash($path).substr(uniqid(), -$length, $length);

        } else {
            /*
             * Assign path in multiple dirs, like a/b/c/d/e/
             */
            foreach (str_split(substr(uniqid(), -$length, $length)) as $char) {
                $path .= DIRECTORY_SEPARATOR.$char;
            }
        }

        return Strings::slash(Path::ensure($path));

    }catch(Exception $e) {
        throw new CoreException(tr('file_create_target_path(): Failed'), $e);
    }
}



/*
 * Ensure that the specified file exists in the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_ensure_file($file, $mode = null, $path_mode = null) {
    global $_CONFIG;

    try {
        if (!$mode) {
            $mode = $_CONFIG['file']['file_mode'];
        }

        Path::ensure(dirname($file), $path_mode);

        if (!file_exists($file)) {
            /*
             * Create the file
             */
            File::new()->executeMode(dirname($file), 0770, function() use ($file, $mode) {
                log_console(tr('file_ensure_file(): Warning: file ":file" did not exist and was created empty to ensure system stability, but information may be missing', array(':file' => $file)), 'VERBOSE/yellow');
                touch($file);

                if ($mode) {
                    chmod($file, $mode);
                }
            });
        }

        return $file;

    }catch(Exception $e) {
        throw new CoreException(tr('file_ensure_file(): Failed'), $e);
    }
}



/*
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
 * @param null octal $mode If the specified $path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
 * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
 * @return string The specified file
 */
function Path::ensure($path, $mode = null, $clear = false, $restrictions = PATH_ROOT) {
    global $_CONFIG;

    try {
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

            foreach ($dirs as $dir) {
                $path .= '/'.$dir;

                if (file_exists($path)) {
                    if (!is_dir($path)) {
                        /*
                         * Some normal file is in the way. Delete the file, and
                         * retry
                         */
                        File::new()->executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions) {
                            file_delete($path, $restrictions);
                        });

                        return Path::ensure($path, $mode);
                    }

                    continue;

                } elseif (is_link($path)) {
                    /*
                     * This is a dead symlink, delete it
                     */
                    File::new()->executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode, $restrictions) {
                        file_delete($path, $restrictions);
                    });
                }

                try {
                    /*
                     * Make sure that the parent path is writable when creating
                     * the directory
                     */
                    File::new()->executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($path, $mode) {
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
            file_delete(Strings::endsNotWith($path, '/'), $restrictions);
            return Path::ensure($path, $mode);
        }

        return Strings::slash(realpath($path).'/');

    }catch(Exception $e) {
        throw new CoreException(tr('Path::ensure(): Failed to ensure path ":path"', array(':path' => $path)), $e);
    }
}



/*
 * Delete the path, and each parent directory until a non empty directory is encountered
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package files
 * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
 *
 * @param list $paths A list of path patterns to be cleared
 * @param null list $params[restrictions] A list of paths to which file_delete() operations will be restricted
 * @return void
 */
function file_clear_path($paths, $restrictions = null) {
    try {
        /*
         * Multiple paths specified, clear all
         */
        if (is_array($paths)) {
            foreach ($paths as $path) {
                file_clear_path($path, $restrictions);
            }

            return;
        }

        $path = $paths;

        /*
         * Restrict location access
         */
        Restrict::restrict($path, $restrictions);

        if (!file_exists($path)) {
            /*
             * This section does not exist, jump up to the next section
             */
            $path = dirname($path);

            try {
                Restrict::restrict($path, $restrictions);
                return file_clear_path($path, $restrictions);

            }catch(Exception $e) {
                if ($e->getCode() === 'access-denied') {
                    /*
                     * We no longer have access to move up more, stop here.
                     */
                    log_console(tr('file_clear_path(): Stopped recursing upward on path ":path" because filesystem restrictions do not permit to move further up', array(':path' => $path)), 'VERYVERBOSE/warning');
                    return;
                }
            }
        }

        if (!is_dir($path)) {
            /*
             * This is a normal file. Delete it and continue with the directory above
             */
            unlink($path);

        } else {
            /*
             * This is a directory. See if its empty
             */
            $h        = opendir($path);
            $contents = false;

            while (($file = readdir($h)) !== false) {
                /*
                 * Skip . and ..
                 */
                if (($file == '.') or ($file == '..')) continue;

                $contents = true;
                break;
            }

            closedir($h);

            if ($contents) {
                /*
                 * Do not remove anything more, there is contents here!
                 */
                return;
            }

            /*
             * Remove this entry and continue;
             */
            try {
                File::new()->executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($restrictions, $path) {
                    file_delete(array('patterns'       => $path,
                                      'clean_path'     => false,
                                      'force_writable' => true,
                                      'restrictions'   => $restrictions));
                });

            }catch(Exception $e) {
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content
                 * in this directory, so it's no longer empty. Just register
                 * the event and leave it be.
                 */
                log_console(tr('file_clear_path(): Failed to remove empty path ":path" with exception ":e"', array(':path' => $path, ':e' => $e)), 'warning');
                return;
            }
        }

        /*
         * Go one entry up, check if we're still within restrictions, and
         * continue deleting
         */
        $path = dirname($path);

        try {
            file_clear_path($path, $restrictions);

        }catch(Exception $e) {
            if ($e->getCode() === 'access-denied') {
                /*
                 * We no longer have access to move up more, stop here.
                 */
                log_console(tr('file_clear_path(): Stopped recursing upward on path ":path" because restrictions do not allow us to move further up', array(':path' => $path)), 'warning');
                return;
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('file_clear_path(): Failed'), $e);
    }
}



/*
 * Return the extension of the specified filename
 */
function file_get_extension($filename) {
    try {
        return Strings::fromReverse($filename, '.');

    }catch(Exception $e) {
        throw new CoreException(tr('file_get_extension(): Failed'), $e);
    }
}



/*
 * Return a file path for a temporary file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
 * @param null string $name If specified, use PATH_ROOT/data/tmp/$name instead of a randomly generated filename
 * @return string The filename for the temp file
 */
function file_temp($create = true, $extension = null, $limit_to_session = true) {
    try {
        Path::ensure(PATH_TMP);

        /*
         * Temp file will contain the session ID
         */
        if ($limit_to_session) {
            $session_id = session_id();
            $name       = substr(hash('sha1', uniqid().microtime()), 0, 12);

            if ($session_id) {
                $name = $session_id.'-'.$name;
            }

        } else {
            $name = substr(hash('sha1', uniqid().microtime()), 0, 12);
        }

        if ($extension) {
            /*
             * Temp file will have specified extension
             */
            $name .= '.'.$extension;
        }

        $file = PATH_TMP.$name;

        /*
         * Temp file can not exist
         */
        if (file_exists($file)) {
    		file_delete($file);
        }

        if ($create) {
            if ($create === true) {
                touch($file);

            } else {
                if (!is_string($create)) {
                    throw new CoreException(tr('file_temp(): Specified $create variable is of datatype ":type" but should be either false, true, or a data string that should be written to the temp file', array(':type' => gettype($create))), $e);
                }

                file_put_contents($file, $create);
            }
        }

        return $file;

    }catch(Exception $e) {
        throw new CoreException(tr('file_temp(): Failed'), $e);
    }
}



/*
 * Return the absolute path for the specified path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added function and documentation
 *
 * @param string $path
 * @return string The absolute path
 */
function file_absolute_path($path) {
    try {
        if (!$path) {
            return getcwd();
        }

        if ($path[0] === '/') {
            return $path;
        }

        return Strings::slash(getcwd()).Strings::unslash($path);

    }catch(Exception $e) {
        throw new CoreException('file_absolute_path(): Failed', $e);
    }
}



/*
 * Returns the mimetype data for the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added documentation
 *
 * @param string $file to be tested
 * @return string The mimetype data for the specified file
 */
function file_mimetype($file) {
    static $finfo = false;

    try {
        /*
         * Check the specified file
         */
        if (!$file) {
            throw new CoreException(tr('file_mimetype(): No file specified'), 'not-specified');
        }

        if (!is_file($file)) {
            if (!file_exists($file)) {
                throw new CoreException(tr('file_mimetype(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exist');
            }

            if (is_dir($file)) {
                throw new CoreException(tr('file_mimetype(): Specified file ":file" is not a normal file but a directory', array(':file' => $file)), 'invalid');
            }

            throw new CoreException(tr('file_mimetype(): Specified file ":file" is not a file', array(':file' => $file)), 'invalid');
        }

        if (!$finfo) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        }

        return finfo_file($finfo, $file);

    }catch(Exception $e) {
        throw new CoreException(tr('file_mimetype(): Failed'), $e);
    }
}



/*
 * Returns true or false if file is ASCII or not
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added documentation
 *
 * @param string $file to be tested
 * @return bolean True if the file is a text file, false if not
 */
function file_is_text($file) {
    try {
        if (Strings::until(file_mimetype($file), '/') == 'text') return true;
        if (str_from (file_mimetype($file), '/') == 'xml' ) return true;

        return false;

    }catch(Exception $e) {
        throw new CoreException(tr('file_is_text(): Failed'), $e);
    }
}



/*
 * Returns true if the specified file exists and is a file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.4: Added documentation
 *
 * @param string $file The file to be tested
 * @return bolean True if the file exists and is a file
 */
function file_check($file) {
    if (!file_exists($file)) {
        throw new CoreException(tr('file_check(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
    }

    if (!is_file($file)) {
        throw new CoreException(tr('file_check(): Specified file ":file" is not a file', array(':file' => $file)), 'notafile');
    }
}



/*
 * Return all files in a directory that match the specified pattern with optional recursion.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_list_tree($path, $pattern = null, $recursive = true) {
    try {
        /*
         * Validate path
         */
        if (!is_dir($path)) {
            if (!is_file($path)) {
                if (!file_exists($path)) {
                    throw new CoreException(tr('file_list_tree(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exist');
                }

                throw new CoreException(tr('file_list_tree(): Specified path ":path" is not a directory or a file', array(':path' => $path)), 'invalid');
            }

            return array($path);
        }

        $return = array();
        $fh    = opendir($path);

        /*
         * Go over all files
         */
        while (($filename = readdir($fh)) !== false) {
            /*
             * Loop through the files, skipping . and .. and recursing if necessary
             */
            if (($filename == '.') or ($filename == '..')) {
                continue;
            }

            /*
             * Does the file match the specified pattern?
             */
            if ($pattern) {
                $match = preg_match($pattern, $filename);

                if (!$match) {
                    continue;
                }
            }

            /*
             * Get the complete file path
             */
            $file = Strings::slash($path).$filename;

            /*
             * Add the file to the list. If the file is a directory, then
             * recurse instead.
             *
             * Do NOT add the directory itself, only files!
             */
            if (is_dir($file) and $recursive) {
                $return = array_merge($return, file_list_tree($file));

            } else {
                $return[] = $file;
            }
        }

        closedir($fh);

        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('file_list_tree(): Failed for ":path"', array(':path' => $path)), $e);
    }
}



/*
 * Delete a file weather it exists or not, without error, using the "rm" command
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @template Function reference
 * @package files
 * @see file_safe_pattern()
 * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
 * @version 2.7.60: Fixed safe file pattern issues
 *
 * @param params $params
 * @param list $params[patterns] A list of path patterns to be deleted
 * @param null list $params[restrictions] A list of paths to which file_delete() operations will be restricted
 * @param boolean $params[clean_path] If specified true, all directories above each specified pattern will be deleted as well as long as they are empty. This way, no empty directories will be left laying around
 * @param boolean $params[sudo] If specified true, the rm command will be executed using sudo
 * @param boolean $params[force_writable] If specified true, the function will first execute chmod ug+w on each specified patterns before deleting them
 * @return natural The amount of orphaned files, and orphaned `files` entries found and processed
 */
function file_delete($params, $restrictions = null) {
    try {
        if (!$params) {
            throw new CoreException(tr('file_delete(): No files or parameters specified'), 'not-specified');
        }

        array_params ($params, 'patterns');
        array_ensure ($params, 'patterns,restrictions,sudo');
        array_default($params, 'restrictions'  , $restrictions);
        array_default($params, 'clean_path'    , true);
        array_default($params, 'force_writable', false);

        /*
         * Both patterns and restrictions should be arrays, make them so now to
         * avoid them being converted multiple times later on
         */
        $params['patterns']     = Arrays::force($params['patterns']);
        $params['restrictions'] = Arrays::force($params['restrictions']);

        /*
         * Delete all specified patterns
         */
        foreach ($params['patterns'] as $pattern) {
            /*
             * Restrict pattern access
             */
            Restrict::restrict($pattern, $params['restrictions']);

            if ($params['force_writable']) {
                try {
                    /*
                     * First ensure that the files to be deleted are writable
                     */
                    file_chmod(array('path'         => $pattern,
                                     'mode'         => 'ug+w',
                                     'recursive'    => true,
                                     'restrictions' => $params['restrictions']));

                }catch(Exception $e) {
                    /*
                     * If chmod failed because the pattern doesn't exist, then
                     * ignore the issue, and continue as the files have to be
                     * deleted anyway
                     */
                    $data = $e->getData();
                    $data = array_shift($data);

                    if (preg_match('/chmod: cannot access .+?: No such file or directory/', $data)) {
                        continue;
                    }
                }
            }

            /*
             * Execute the rm command
             */
            safe_exec(array('commands' => array('rm', array('sudo' => $params['sudo'], '-rf', '#' => file_safe_pattern($pattern)))));

            /*
             * If specified to do so, clear the path upwards from the specified
             * pattern
             */
            if ($params['clean_path']) {
                file_clear_path(dirname($pattern), $params['restrictions']);
            }
        }

    }catch(Exception $e) {
        throw new CoreException(tr('file_delete(): Failed'), $e);
    }
}



/*
 * Returns a safe version of the specified pattern
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see file_delete()
 * @see file_chown()
 * @version 2.7.60: Added function and documentation
 *
 * @param string $pattern The pattern to make safe
 * @return string The safe pattern
 */
function file_safe_pattern($pattern) {
    try {
        /*
         * Escape patterns manually here, safe_exec() will be told NOT to
         * escape them to avoid issues with *
         */
        $pattern = Arrays::force($pattern, '*');

        foreach ($pattern as &$item) {
            $item = escapeshellarg($item);
        }

        return implode('*', $pattern);

    }catch(Exception $e) {
        throw new CoreException(tr('file_safe_pattern(): Failed'), $e);
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
function file_copy_tree($source, $destination, $search = null, $replace = null, $extensions = null, $mode = true, $novalidate = false, $restrictions = null) {
    global $_CONFIG;

    try {
        /*
         * Choose between copy filemode (mode is null), set filemode ($mode is a string or octal number) or preset filemode (take from config, TRUE)
         */
        if (!is_bool($mode) and !is_null($mode)) {
            if (is_string($mode)) {
                $mode = intval($mode, 8);
            }

            $filemode = $mode;
        }

        if (substr($destination, 0, 1) != '/') {
            /*
             * This is not an absolute path
             */
            $destination = PWD.$destination;
        }

        /*
         * Validations
         */
        if (!$novalidate) {
            /*
             * Prepare search / replace
             */
            if (!$search) {
                /*
                 * We can only replace if we search
                 */
                $search     = null;
                $replace    = null;
                $extensions = null;

            } else {
                if (!is_array($extensions)) {
                    $extensions = array($extensions);
                }

                if (!is_array($search)) {
                    $search = explode(',', $search);
                }

                if (!is_array($replace)) {
                    $replace = explode(',', $replace);
                }

                if (count($search) != count($replace)) {
                    throw new CoreException(tr('file_copy_tree(): The search parameters count ":search" and replace parameters count ":replace" do not match', array(':search' => count($search), ':replace' => count($replace))), 'parameternomatch');
                }
            }

            if (!file_exists($source)) {
                throw new CoreException(tr('file_copy_tree(): Specified source ":source" does not exist', array(':source' => $source)), 'not-exists');
            }

            $destination = Strings::unslash($destination);

            if (!file_exists($destination)) {
// :TODO: Check if dirname() here is correct? It somehow does not make sense
                if (!file_exists(dirname($destination))) {
                    throw new CoreException(tr('file_copy_tree(): Specified destination ":destination" does not exist', array(':destination' => dirname($destination))), 'not-exists');
                }

                if (!is_dir(dirname($destination))) {
                    throw new CoreException(tr('file_copy_tree(): Specified destination ":destination" is not a directory', array(':destination' => dirname($destination))), 'not-directory');
                }

                if (is_dir($source)) {
                    /*
                     * We are copying a directory, destination dir does not yet exist
                     */
                    mkdir($destination);

                } else {
                    /*
                     * We are copying just one file
                     */
                }

            } else {
                /*
                 * Destination already exists,
                 */
                if (is_dir($source)) {
                    if (!is_dir($destination)) {
                        throw new CoreException(tr('file_copy_tree(): Cannot copy source directory ":source" into destination file ":destination"', array(':source' => $source, ':destination' => $destination)), 'failed');
                    }

                } else {
                    /*
                     * Source is a file
                     */
                    if (!is_dir($destination)) {
                        /*
                         * Remove destination file since it would be overwritten
                         */
                        file_delete($destination, $restrictions);
                    }
                }
            }
        }

        if (is_dir($source)) {
            $source      = Strings::slash($source);
            $destination = Strings::slash($destination);

            foreach (scandir($source) as $file) {
                if (($file == '.') or ($file == '..')) {
                    /*
                     * Only replacing down
                     */
                    continue;
                }

                if (is_null($mode)) {
                    $filemode = $_CONFIG['file']['dir_mode'];

                } elseif (is_link($source.$file)) {
                    /*
                     * No file permissions for symlinks
                     */
                    $filemode = false;

                } else {
                    $filemode = fileperms($source.$file);
                }

                if (is_dir($source.$file)) {
                    /*
                     * Recurse
                     */
                    if (file_exists($destination.$file)) {
                        /*
                         * Destination path already exists. This -by the way- means that the
                         * destination tree was not clean
                         */
                        if (!is_dir($destination.$file)) {
                            /*
                             * Were overwriting here!
                             */
                            file_delete($destination.$file, $restrictions);
                        }
                    }

                    Path::ensure($destination.$file, $filemode);
                }

                file_copy_tree($source.$file, $destination.$file, $search, $replace, $extensions, $mode, true);
           }

        } else {
            if (is_link($source)) {
                $link = readlink($source);

                if (substr($link, 0, 1) == '/') {
                    /*
                     * Absolute link, this is ok
                     */
                    $reallink = $link;

                } else {
                    /*
                     * Relative link, get the absolute path
                     */
                    $reallink = Strings::slash(dirname($source)).$link;
                }

                if (!file_exists($reallink)) {
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
            if ($mode === null) {
                $filemode = $_CONFIG['file']['file_mode'];

            } elseif ($mode === true) {
                $filemode = fileperms($source);
            }

            /*
             * Check if the file requires search / replace
             */
            if (!$search) {
                /*
                 * No search specified, just copy tree
                 */
                $doreplace = false;

            } elseif (!$extensions) {
                /*
                 * No extensions specified, search / replace all files in tree
                 */
                $doreplace = true;

            } else {
                /*
                 * Check extension if we should search / replace
                 */
                $doreplace = false;

                foreach ($extensions as $extension) {
                    $len = strlen($extension);

                    if (!substr($source, -$len, $len) != $extension) {
                        $doreplace = true;
                        break;
                    }
                }
            }

            if (!$doreplace) {
                /*
                 * Just a simple filecopy will suffice
                 */
                copy($source, $destination);

            } else {
                $data = file_get_contents($source);

                foreach ($search as $id => $svalue) {
                    if ((substr($svalue, 0, 1 == '/')) and (substr($svalue, -1, 1 == '/'))) {
                        /*
                         * Do a regex search / replace
                         */
                        $data = preg_replace($svalue, $replace[$id], $data);

                    } else {
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

            if ($mode) {
                /*
                 * Update file mode
                 */
                try {
                    chmod($destination, $filemode);

                }catch(Exception $e) {
                    throw new CoreException(tr('file_copy_tree(): Failed to set filemode for ":destination"', array(':destination' => $destination)), $e);
                }
            }
        }

        return $destination;

    }catch(Exception $e) {
        throw new CoreException(tr('file_copy_tree(): Failed'), $e);
    }
}



/*
 * Seach for $search file in $source, and move them all to $destination using the $rename result expression
 */
function file_rename($source, $destination, $search, $rename) {
    try {
        /*
         * Validations
         */
        if (!file_exists($source)) {
            throw new CoreException(tr('file_rename(): Specified source ":source" does not exist', array(':source' => $source)), 'exists');
        }

        if (!file_exists($destination)) {
            throw new CoreException(tr('file_rename(): Specified destination ":destination" does not exist', array(':destination' => $destination)), 'exists');
        }

        if (!is_dir($destination)) {
            throw new CoreException(tr('file_rename(): Specified destination ":destination" is not a directory', array(':destination' => $destination)), 'invalid');
        }

        if (is_file($source)) {
            /*
             * Rename just one file
             */

        } else {
            /*
             * Rename all files in this directory
             */

        }


    }catch(Exception $e) {
        throw new CoreException(tr('file_rename(): Failed'), $e);
    }
}



/*
 * Create temporary directory (sister function from tempnam)
 */
function file_temp_dir($prefix = '', $mode = null) {
    global $_CONFIG;

    try {
        /*
         * Use default configged mode, or specific mode?
         */
        if ($mode === null) {
            $mode = $_CONFIG['file']['dir_mode'];
        }

        Path::ensure($path = PATH_TMP);

        while (true) {
            $unique = uniqid($prefix);

            if (!file_exists($path.$unique)) {
                break;
            }
        }

        $path = $path.$unique;

        /*
         * Make sure the temp dir exists
         */
        Path::ensure($path);

        return Strings::slash($path);

    }catch(Exception $e) {
        throw new CoreException(tr('file_tempdir(): Failed'), $e);
    }
}



/*
 * Change file mode, optionally recursively
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see file_safe_pattern()
 * @version 2.6.30: Added function and documentation
 * @version 2.7.60: Fixed safe file pattern issues
 *
 * @param params $params A parameters array
 * @param string $mode
 * @param list $restrictions
 * @param string $params[path]
 * @param boolean $params[recursive] If set to true, apply specified mode to the specified path and all files below by recursion
 * @param octal string $params[mode] The mode to apply to the specified path (and all files below if recursive is specified)
 * @param list $params[restrictions]
 * @param natural $params[timeout]
 * @return void
 */
function file_chmod($params, $mode = null, $restrictions = null) {
    try {
        array_params($params, 'path');
        Arrays::ensure($params, 'recursive,sudo');
        array_default($params, 'timeout'     , 30);
        array_default($params, 'mode'        , $mode);
        array_default($params, 'restrictions', $restrictions);

        if (!($params['mode'])) {
            throw new CoreException(tr('No file mode specified'), 'not-specified');
        }

        if (!$params['path']) {
            throw new CoreException(tr('No path specified'), 'not-specified');
        }

        foreach (Arrays::force($params['path']) as $path) {
            Restrict::restrict($path, $params['restrictions']);

            $arguments      = array();
            $arguments[]    = $params['mode'];
            $arguments['#'] = file_safe_pattern($path);

            if ($params['recursive']) {
                $arguments[] = '-R';
            }

            if ($params['sudo']) {
                $arguments['sudo'] = $params['sudo'];
            }

            safe_exec(array('timeout'  => $params['timeout'],
                            'commands' => array('chmod', $arguments)));
        }

    }catch(Exception $e) {
        throw new CoreException('file_chmod(): Failed', $e);
    }
}



/*
 * Return the extension for the specified file
 */
function file_extension($file) {
    return pathinfo($file, PATHINFO_EXTENSION);
}



/*
 * If the specified file is an HTTP, HTTPS, or FTP URL, then get it locally as a temp file
 */
function file_get_local($url, &$is_downloaded = false, $context = null) {
    try {
        $context = file_create_stream_context($context);
        $url     = trim($url);

        if ((stripos($url, 'http:') === false) and (stripos($url, 'https:') === false) and (stripos($url, 'ftp:') === false)) {
            if (!file_exists($url)) {
                throw new CoreException(tr('file_get_local(): Specified file ":file" does not exist', array(':file' => $url)), 'not-exists');
            }

            if (is_uploaded_file($url)) {
                $tmp  = file_get_uploaded($url);
                $file = file_temp($url, null, false);

                rename($tmp, $file);
                return $file;
            }

            return $url;
        }

        /*
         * First download the file to a temporary location
         */
        $file          = str_replace(array('://', '/'), '-', $url);
        $file          = file_temp($file);
        $is_downloaded = true;

        Path::ensure(dirname($file));
        file_put_contents($file, file_get_contents($url, false, $context));

        return $file;

    }catch(Exception $e) {
        $message = $e->getMessage();
        $message = strtolower($message);

        if (str_contains($message, '404 not found')) {
            throw new CoreException(tr('file_get_local(): URL ":file" does not exist', array(':file' => $url)), 'file-404');
        }

        if (str_contains($message, '400 bad request')) {
            throw new CoreException(tr('file_get_local(): URL ":file" is invalid', array(':file' => $url)), 'file-400');
        }

        throw new CoreException(tr('file_get_local(): Failed for file ":file"', array(':file' => $url)), $e);
    }
}



/*
 * Return a system path for the specified type
 */
function file_system_path($type, $path = '') {
    switch ($type) {
        case 'img':
            // no-break
        case 'image':
            return '/pub/img/'.$path;

        case 'css':
            // no-break
        case 'style':
            return '/pub/css/'.$path;

        default:
            throw new CoreException(tr('file_system_path(): Unknown type ":type" specified', array(':type' => $type)), 'unknown');
    }
}



/*
 * Pick and return a random file name from the specified path
 *
 * Warning: This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
 */
function file_random($path) {
    try {
        if (!file_exists($path)) {
            throw new CoreException(tr('file_random(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        if (!file_exists($path)) {
            throw new CoreException(tr('file_random(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        $files = scandir($path);

        unset($files[array_search('.' , $files)]);
        unset($files[array_search('..', $files)]);

        if (!$files) {
            throw new CoreException(tr('file_random(): The specified path ":path" contains no files', array(':path' => $path)), 'not-exists');
        }

        return Strings::slash($path).array_get_random($files);

    }catch(Exception $e) {
        throw new CoreException(tr('file_random(): Failed'), $e);
    }
}



/*
 * Store a file temporarily with a label in $_SESSION['files'][label]
 */
function file_session_store($label, $file = null, $path = PATH_TMP) {
    try {
        if ($file === null) {
            /*
             * No file specified, return the file name for the specified label
             * Then remove the temporary file and the label
             */
            if (isset($_SESSION['files'][$label])) {
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
        if (!empty($_SESSION['files'][$label])) {
           file_delete($_SESSION['files'][$label]);
        }

        Arrays::ensure($_SESSION, 'files');

        $target = file_move_to_target($file, $path, false, true, 1);

        $_SESSION['files'][$label] = $file;

        return $file;

    }catch(Exception $e) {
        throw new CoreException(tr('file_session_store(): Failed'), $e);
    }
}



/*
 * Checks if the specified path exists, is a dir, and optionally, if its writable or not
 */
function file_check_dir($path, $writable = false) {
    try {
        if (!file_exists($path)) {
            throw new CoreException(tr('file_check_dir(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        if (!is_dir($path)) {
            throw new CoreException(tr('file_check_dir(): The specified path ":path" is not a directory', array(':path' => $path)), 'notadirectory');
        }

        if ($writable and !is_writable($path)) {
            throw new CoreException(tr('file_check_dir(): The specified path ":path" is not writable', array(':path' => $path)), 'notwritable');
        }

    }catch(Exception $e) {
        throw new CoreException(tr('file_check_dir(): Failed'), $e);
    }
}



/*
 * Send the specified file to the client as a download using the HTTP protocol with correct headers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.89: Rewrote function and added documentation
 *
 * @param params $params The file parameters
 * @return void
 */
function file_http_download($params) {
    global $_CONFIG;

    try {
        Arrays::ensure($params, 'file,data,name');
        array_default($params, 'restrictions', PATH_ROOT.'data/downloads');
        array_default($params, 'compression' , $_CONFIG['file']['download']['compression']);
        array_default($params, 'filename'    , basename($params['file']));
        array_default($params, 'attachment'  , false);
        array_default($params, 'die'         , true);

        /*
         * Validate the file name for the user
         */
        if (!$params['filename']) {
            throw new CoreException(tr('file_http_download(): No filename specified. Note: This is not the file to be downloaded to the client, but the name it will have when saved on the clients storage'), 'not-specified');
        }

        if (!is_scalar($params['filename'])) {
                throw new CoreException(tr('file_http_download(): Specified filename ":filename" is not scalar', array(':filename' => $params['filename'])), 'invalid');
        }

        if (mb_strlen($params['filename']) > 250) {
                throw new CoreException(tr('file_http_download(): Specified filename ":filename" is too long, it cannot be longer than 250 characters', array(':filename' => $params['filename'])), 'invalid');
        }

        if ($params['data']) {
            /*
             * Send the specified data as a file to the client
             * Write the data to a temp file first so we can just upload from
             * there
             */
            if ($params['file']) {
                throw new CoreException(tr('file_http_download(): Both "file" and "data" were specified, these parameters are mutually exclusive. Please specify one or the other'), 'invalid');
            }

            $params['file'] = file_temp($params['data']);
            $params['data'] = $params['file'];
        }

        if (!$params['file']) {
            throw new CoreException(tr('file_http_download(): No file or data specified to download to client'), 'not-specified');
        }

        /*
         * Send a file from disk
         * Validate data
         */
        if (!is_scalar($params['file'])) {
            throw new CoreException(tr('file_http_download(): Specified file ":file" is not scalar', array(':file' => $params['file'])), 'invalid');
        }

        if (!file_exists($params['file'])) {
            throw new CoreException(tr('file_http_download(): Specified file ":file" does not exist or is not accessible', array(':file' => $params['file'])), 'not-exists');
        }

        if (!is_readable($params['file'])) {
            throw new CoreException(tr('file_http_download(): Specified file ":file" exists but is not readable', array(':file' => $params['file'])), 'not-readable');
        }

        Restrict::restrict($params['file'], $params['restrictions']);

        /*
         * We have to send the right content type headers and we might need to
         * figure out if we need to use compression or not
         */
        $mimetype  = mime_content_type($params['file']);
        $primary   = Strings::until($mimetype, '/');
        $secondary = Strings::from($mimetype , '/');

        /*
         * What file mode will we use?
         */
        if (file_is_binary($primary, $secondary)) {
            $mode = 'rb';

        } else {
            $mode = 'r';
        }

        /*
         * Do we need compression?
         */
        if ($params['compression'] === 'auto') {
            /*
             * Detect if the file is already compressed. If so, we don't need
             * the server to try to compress the data stream too because it
             * won't do anything (possibly make it even worse)
             */
            $params['compression'] = !file_is_compressed($primary, $secondary);
        }

        if ($params['compression']) {
            if (is_executable('apache_setenv')) {
                apache_setenv('no-gzip', 0);
            }

            ini_set('zlib.output_compression', 'On');

        } else {
            if (is_executable('apache_setenv')) {
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
        header('Content-length: '.$bytes);

        if ($params['attachment']) {
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
        if ($params['data']) {
            file_delete($params['data']);
        }

        if ($params['die']) {
            die();
        }

    }catch(Exception $e) {
        /*
         * If we created a temporary file for a given data string, then delete
         * the temp file
         */
        if ($params['data']) {
            file_delete($params['data']);
        }

        throw new CoreException(tr('file_http_download(): Failed'), $e);
    }
}



/*
 * Return true if the specified mimetype is for a binary file or false if it is for a text file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.90: Added function and documentation
 *
 * @param string $mimetype The primary mimetype section to check. If the mimetype is "text/plain", this variable would receive "text". You can also leave $secondary empty and specify the complete mimetype "text/plain" here, both will work
 * @param string $mimetype The secondary mimetype section to check. If the mimetype is "text/plain", this variable would receive "plain". If the complete mimetype is specified in $primary, you can leave this one empty
 * @return boolean True if the specified mimetype is for a binary file, false if it is a text file
 */
function file_is_binary($primary, $secondary = null) {
    try {
// :TODO: IMPROVE THIS! Loads of files that are not text/ are still not binary
        /*
         * Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
         */
        if (!$secondary) {
            if (!str_contains($primary, '/')) {
                throw new CoreException(tr('file_is_compressed(): Invalid primary mimetype data "" specified. Either specify the complete mimetype in $primary, or specify the independant primary and secondary sections in $primary and $secondary', array(':primary' => $primary)), $e);
            }

            $secondary = Strings::from($primary , '/');
            $primary   = Strings::until($primary, '/');
        }

        /*
         * Check the mimetype data
         */
        switch ($primary) {
            case 'text':
                /*
                 * Readonly
                 */
                return false;

            default:
                switch ($secondary) {
                    case 'json':
                        // no-break
                    case 'ld+json':
                        // no-break
                    case 'svg+xml':
                        // no-break
                    case 'x-csh':
                        // no-break
                    case 'x-sh':
                        // no-break
                    case 'xhtml+xml':
                        // no-break
                    case 'xml':
                        // no-break
                    case 'xml':
                        // no-break
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

    }catch(Exception $e) {
        throw new CoreException('file_is_binary(): Failed', $e);
    }
}



/*
 * Return true if the specified mimetype is for a compressed file, false if not
 *
 * This function will check the primary and secondary sections of the mimetype and depending on their values, determine if the file format should use compression or not
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.5.90: Added function and documentation
 *
 * @param string $mimetype The primary mimetype section to check. If the mimetype is "text/plain", this variable would receive "text". You can also leave $secondary empty and specify the complete mimetype "text/plain" here, both will work
 * @param string $mimetype The secondary mimetype section to check. If the mimetype is "text/plain", this variable would receive "plain". If the complete mimetype is specified in $primary, you can leave this one empty
 * @return boolean True if the specified mimetype is for a compressed file, false if not
 */
function file_is_compressed($primary, $secondary = null) {
    try {
// :TODO: IMPROVE THIS! Loads of files that may be mis detected
        /*
         * Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
         */
        if (!$secondary) {
            if (!str_contains($primary, '/')) {
                throw new CoreException(tr('file_is_compressed(): Invalid primary mimetype data "" specified. Either specify the complete mimetype in $primary, or specify the independant primary and secondary sections in $primary and $secondary', array(':primary' => $primary)), $e);
            }

            $secondary = Strings::from($primary , '/');
            $primary   = Strings::until($primary, '/');
        }

        /*
         * Check the mimetype data
         */
        if (str_contains($secondary, 'compressed')) {
            /*
             * This file is already compressed
             */
            return true;

        } elseif (str_contains($secondary, 'zip')) {
            /*
             * This file is already compressed
             */
            return true;

        } else {
            switch ($secondary) {
                case 'jpeg':
                    // no-break
                case 'mpeg':
                    // no-break
                case 'ogg':
                    /*
                     * This file is already compressed
                     */
                    return true;

                default:
                    switch ($primary) {
                        case 'audio':
                            switch ($secondary) {
                                case 'mpeg':
                                    // no-break
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

    }catch(Exception $e) {
        throw new CoreException('template_function(): Failed', $e);
    }
}




/*
 * Copy a file with progress notification
 *
 * @example:
 * function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
 *     if ($notification_code == STREAM_NOTIFY_PROGRESS) {
 *         // save $bytes_transferred and $bytes_max to file or database
 *     }
 * }
 *
 * file_copy_progress($source, $target, 'stream_notification_callback');
 */
function file_copy_progress($source, $target, $callback) {
    try {
        $c = stream_context_create();
        stream_context_set_params($c, array('notification' => $callback));
        copy($source, $target, $c);

    }catch(Exception $e) {
        throw new CoreException(tr('file_copy_progress(): Failed'), $e);
    }
}



/*
 *
 */
function file_mode_readable($mode) {
    try {
        $return = '';
        $mode   = substr((string) decoct($mode), -3, 3);

        for($i = 0; $i < 3; $i++) {
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

    }catch(Exception $e) {
        throw new CoreException(tr('file_mode_readable(): Failed'), $e);
    }
}



/*
 * Calculate either the total size of the tree under the specified path, or the amount of files (directories not included in count)
 * @$method (string) either "size" or "count", the required value to return
 */
function file_tree($path, $method) {
    try {
        if (!file_exists($path)) {
            throw new CoreException(tr('file_tree(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        switch ($method) {
            case 'size':
                // no-break
            case 'count':
                break;

            default:
                throw new CoreException(tr('file_tree(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
        }

        $return = 0;
        $path   = Strings::slash($path);

        foreach (scandir($path) as $file) {
            if (($file == '.') or ($file == '..')) continue;

            if (is_dir($path.$file)) {
                $return += file_tree($path.$file, $method);

            } else {
                switch ($method) {
                    case 'size':
                        $return += filesize($path.$file);
                        break;

                    case 'count':
                        $return++;
                        break;
                }
            }
        }

        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('file_tree(): Failed'), $e);
    }
}



/*
 *
 */
function file_ensure_writable($path) {
    try {
        if (is_writable($path)) {
            return false;
        }

        $perms = fileperms($path);

        if (is_dir($path)) {
            chmod($path, 0770);

        } else {
            if (is_executable($path)) {
                chmod($path, 0770);

            } else {
                chmod($path, 0660);
            }
        }

        return $perms;

    }catch(Exception $e) {
        throw new CoreException(tr('file_ensure_writable(): Failed'), $e);
    }
}



/*
 * Returns array with all permission information about the specified file.
 *
 * Idea taken from http://php.net/manual/en/function.fileperms.php
 */
function file_type($file) {
    try {
        $perms  = fileperms($file);

        $socket    = (($perms & 0xC000) == 0xC000);
        $symlink   = (($perms & 0xA000) == 0xA000);
        $regular   = (($perms & 0x8000) == 0x8000);
        $bdevice   = (($perms & 0x6000) == 0x6000);
        $cdevice   = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe  = (($perms & 0x1000) == 0x1000);

        if ($socket) {
            /*
             * This file is a socket
             */
            return 'socket';

        } elseif ($symlink) {
            /*
             * This file is a symbolic link
             */
            return 'symbolic link';

        } elseif ($regular) {
            /*
             * This file is a regular file
             */
            return 'regular file';

        } elseif ($bdevice) {
            /*
             * This file is a block device
             */
            return 'block device';

        } elseif ($directory) {
            /*
             * This file is a directory
             */
            return 'directory';

        } elseif ($cdevice) {
            /*
             * This file is a character device
             */
            return 'character device';

        } elseif ($fifopipe) {
            /*
             * This file is a FIFO pipe
             */
            return 'fifo pipe';
        }

        /*
         * This file is an unknown type
         */
        return 'unknown';

    }catch(Exception $e) {
        throw new CoreException(tr('file_type(): Failed for file ":file"', array(':file' => $file)), $e);
    }
}



/*
 * Returns array with all permission information about the specified file.
 *
 * Idea taken from http://php.net/manual/en/function.fileperms.php
 */
function file_get_permissions($file) {
    try {
        $perms  = fileperms($file);
        $return = array();

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
            /*
             * This file is a socket
             */
            $return['mode'] = 's';
            $return['type'] = 'socket';

        } elseif ($return['symlink']) {
            /*
             * This file is a symbolic link
             */
            $return['mode'] = 'l';
            $return['type'] = 'symbolic link';

        } elseif ($return['regular']) {
            /*
             * This file is a regular file
             */
            $return['mode'] = '-';
            $return['type'] = 'regular file';

        } elseif ($return['bdevice']) {
            /*
             * This file is a block device
             */
            $return['mode'] = 'b';
            $return['type'] = 'block device';

        } elseif ($return['directory']) {
            /*
             * This file is a directory
             */
            $return['mode'] = 'd';
            $return['type'] = 'directory';

        } elseif ($return['cdevice']) {
            /*
             * This file is a character device
             */
            $return['mode'] = 'c';
            $return['type'] = 'character device';

        } elseif ($return['fifopipe']) {
            /*
             * This file is a FIFO pipe
             */
            $return['mode'] = 'p';
            $return['type'] = 'fifo pipe';

        } else {
            /*
             * This file is an unknown type
             */
            $return['mode']    = 'u';
            $return['type']    = 'unknown';
            $return['unknown'] = true;
        }

        $return['owner'] = array('r' =>  ($perms & 0x0100),
                                 'w' =>  ($perms & 0x0080),
                                 'x' => (($perms & 0x0040) and !($perms & 0x0800)),
                                 's' => (($perms & 0x0040) and  ($perms & 0x0800)),
                                 'S' =>  ($perms & 0x0800));

        $return['group'] = array('r' =>  ($perms & 0x0020),
                                 'w' =>  ($perms & 0x0010),
                                 'x' => (($perms & 0x0008) and !($perms & 0x0400)),
                                 's' => (($perms & 0x0008) and  ($perms & 0x0400)),
                                 'S' =>  ($perms & 0x0400));

        $return['other'] = array('r' =>  ($perms & 0x0004),
                                 'w' =>  ($perms & 0x0002),
                                 'x' => (($perms & 0x0001) and !($perms & 0x0200)),
                                 't' => (($perms & 0x0001) and  ($perms & 0x0200)),
                                 'T' =>  ($perms & 0x0200));

        /*
         * Owner
         */
        $return['mode'] .= (($perms & 0x0100) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0080) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0040) ?
                           (($perms & 0x0800) ? 's' : 'x' ) :
                           (($perms & 0x0800) ? 'S' : '-'));

        /*
         * Group
         */
        $return['mode'] .= (($perms & 0x0020) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0010) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0008) ?
                           (($perms & 0x0400) ? 's' : 'x' ) :
                           (($perms & 0x0400) ? 'S' : '-'));

        /*
         * World
         */
        $return['mode'] .= (($perms & 0x0004) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0002) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0001) ?
                           (($perms & 0x0200) ? 't' : 'x' ) :
                           (($perms & 0x0200) ? 'T' : '-'));

        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('file_get_permissions(): Failed'), $e);
    }
}



/*
 * Execute the specified callback on all files in the specified tree
 */
function file_tree_execute($params) {
    try {
        Arrays::ensure($params);
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
        if (empty($params['callback'])) {
            throw new CoreException(tr('file_tree_execute(): No callback function specified'), 'not-specified');
        }

        if (!is_callable($params['callback'])) {
            throw new CoreException(tr('file_tree_execute(): Specified callback is not a function'), 'invalid');
        }

        if (!($params['path'])) {
            throw new CoreException(tr('file_tree_execute(): No path specified'), 'not-specified');
        }

        if (substr($params['path'], 0, 1) !== '/') {
            throw new CoreException(tr('file_tree_execute(): No absolute path specified'), 'invalid');
        }

        if (!file_exists($params['path'])) {
            throw new CoreException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $params['path'])), 'not-exists');
        }

        /*
         * Follow hidden files?
         */
        if ((substr(basename($params['path']), 0, 1) == '.') and !$params['follow_hidden']) {
            if (VERBOSE and PLATFORM_CLI) {
                log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $params['path'])), 'yellow');
            }

            return 0;
        }

        /*
         * Filter this path?
         */
        foreach (Arrays::force($params['filters']) as $filter) {
            if (preg_match($filter, $params['path'])) {
                if (VERBOSE and PLATFORM_CLI) {
                    log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $params['path'], ':filter' => $filter)), 'yellow');
                }

                return 0;
            }
        }

        $count = 0;
        $type  = file_type($params['path']);

        switch ($type) {
            case 'regular file':
                $params['callback']($params['path']);
                $count++;

                log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $params['path'])), 'VERYVERBOSEDOT/green');
                break;

            case 'symlink':
                if ($params['follow_symlinks']) {
                    $params['path'] = readlink($params['path']);
                    $count += file_tree_execute($params);
                }

                break;

            case 'directory':
                $h    = opendir($params['path']);
                $path = Strings::slash($params['path']);

                while (($file = readdir($h)) !== false) {
                    try {
                        if (($file == '.') or ($file == '..')) continue;

                        if ((substr(basename($file), 0, 1) == '.') and !$params['follow_hidden']) {
                            if (VERBOSE and PLATFORM_CLI) {
                                log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $file)), 'yellow');
                            }

                            continue;
                        }

                        $file = $path.$file;

                        if (!file_exists($file)) {
                            throw new CoreException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $file)), 'not-exists');
                        }

                        $type = file_type($file);

                        switch ($type) {
                            case 'link':
                                if (!$params['follow_symlinks']) {
                                    continue 2;
                                }

                                $file = readlink($file);

                                /*
                                 * We got the target file, but we don't know what it is.
                                 * Restart the process recursively to process this file
                                 */

                                // no-break

                            case 'directory':
                                // no-break
                            case 'regular file':
                                if (($type != 'directory') or $params['execute_directory']) {
                                    /*
                                     * Filter this path?
                                     */
                                    $skip = false;

                                    foreach (Arrays::force($params['filters']) as $filter) {
                                        if (preg_match($filter, $file)) {
                                            if (VERBOSE and PLATFORM_CLI) {
                                                log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $params['path'], ':filter' => $filter)), 'yellow');
                                            }

                                            $skip = true;
                                        }
                                    }

                                    if (!$skip) {
                                        $result = $params['callback']($file, $type, $params['params']);
                                        $count++;

                                        if ($result === false) {
                                            /*
                                             * When the callback returned boolean false, cancel all other files
                                             */
                                            log_console(tr('file_tree_execute(): callback returned FALSE for file ":file", skipping rest of directory contents!', array(':file' => $file)), 'yellow');
                                            goto end;
                                        }

                                        log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $file)), 'VERYVERBOSEDOT/green');
                                    }
                                }

                                if (($type == 'directory') and $params['recursive']) {
                                    $params['path'] = $file;
                                    $count         += file_tree_execute($params);
                                }

                                break;

                            default:
                                /*
                                 * Skip this unsupported file type
                                 */
                                if (VERBOSE and PLATFORM_CLI) {
                                    log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $type)), 'yellow');
                                }
                        }

                    }catch(Exception $e) {
                        if (!$params['ignore_exceptions']) {
                            throw $e;
                        }

                        if ($e->getCode() === 'not-exists') {
                            log_console(tr('file_tree_execute(): Skipping file ":file", it does not exist (in case of a symlink, it may be that the target does not exist)', array(':file' => $file)), 'VERBOSE/yellow');

                        } else {
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
                if (VERBOSE and PLATFORM_CLI) {
                    log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $params['path'])), 'yellow');
                }
        }

        return $count;

    }catch(Exception $e) {
        throw new CoreException(tr('file_tree_execute(): Failed'), $e);
    }
}





/*
 * If specified path is not absolute, then return a path that is sure to start
 * within PATH_ROOT
 */
function file_root($path) {
    try {
        if (substr($path, 0, 1) !== '/') {
            $path = PATH_ROOT.$path;
        }

        return $path;

    }catch(Exception $e) {
        throw new CoreException(tr('file_root(): Failed'), $e);
    }
}



/*
 * Execute the specified callback after setting the specified mode on the
 * specified path. Once the callback has finished, the path will have its
 * original file mode applied again
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see Path::ensure()
 * @version 2.7.13: Added function and documentation
 * @note: If the specified path has an asterix (*) in front of it, ALL sub directories will be updated with the specified mode, and each will have their original file mode restored after
 *
 * @param string array $path The path that will have its mode updated. When * is added in front of the path, ALL sub directories will be updated with the new mode as well, and placed back with their old modes after the command has executed
 * @param string $mode The mode to which the specified directory should be set during execution
 * @param function $callback The function to be executed after the file mode of the specified path has been updated
 * @return string The result from the callback function
 */
function File::new()->executeMode($path, $mode, $callback, $params = null) {
    try {
        /*
         * Apply to all directories below?
         */
        if ($path[0] === '*') {
            $path  = substr($path, 1);
            $multi = true;

        } else {
            $multi = false;
        }

        if (!file_exists($path)) {
            throw new CoreException(tr('File::new()->executeMode(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        if (!is_string($callback) and !is_callable($callback)) {
            throw new CoreException(tr('File::new()->executeMode(): Specified callback ":callback" is invalid, it should be a string or a callable function', array(':callback' => $callback)), 'invalid');
        }

        /*
         * Set the requested mode
         */
        try {
            if (is_dir($path) and $multi) {
                $paths = cli_find(array('type'  => 'd',
                                        'start' => $path));

                foreach ($paths as $subpath) {
                    $modes[$subpath] = fileperms($subpath);
                    chmod($subpath, $mode);
                }

            } else {
                if ($mode) {
                    $original_mode = fileperms($path);
                    chmod($path, $mode);
                }
            }

        }catch(Exception $e) {
            if (empty($subpath)) {
                if (!is_writable($path)) {
                    throw new CoreException(tr('File::new()->executeMode(): Failed to set mode "0:mode" to specified path ":path", access denied', array(':mode' => decoct($mode), ':path' => $path)), $e);
                }

            } else {
                if (!is_writable($subpath)) {
                    throw new CoreException(tr('File::new()->executeMode(): Failed to set mode "0:mode" to specified subpath ":path", access denied', array(':mode' => decoct($mode), ':path' => $subpath)), $e);
                }
            }

            $message = $e->getmessages();
            $message = array_shift($message);
            $message = strtolower($message);

            if (str_contains($message, 'operation not permitted')) {
                throw new CoreException(tr('File::new()->executeMode(): Failed to set mode "0:mode" to specified path ":path", operation not permitted', array(':mode' => decoct($mode), ':path' => $path)), $e);
            }

            throw $e;
        }

        $return = $callback($path, $params, $mode);

        /*
         * Return the original mode
         */
        if ($mode) {
            if ($multi) {
                foreach ($modes as $subpath => $mode) {
                    /*
                     * Path may have been deleted by the callback (for example,
                     * a file_delete() call may have cleaned up the path) so
                     * ensure the path still exists
                     */
                    if (file_exists($subpath)) {
                        chmod($subpath, $mode);
                    }
                }

            } else {
                /*
                 * Path may have been deleted by the callback (for example,
                 * a file_delete() call may have cleaned up the path) so
                 * ensure the path still exists
                 */
                if (file_exists($path)) {
                    chmod($path, $original_mode);
                }
            }
        }

        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('File::new()->executeMode(): Failed for path(s) ":path"', array(':path' => $path)), $e);
    }
}



/*
 *
 */
function file_link_exists($file) {
    if (file_exists($file)) {
        return true;
    }

    if (is_link($file)) {
        throw new CoreException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exists');
    }

    throw new CoreException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exists');
}



/*
 * Open the specified source, read the contents, and replace $search with $replace. Write results in $target
 * $replaces should be a $search => $replace key value array, where the $search values are regex expressions
 */
function file_search_replace($source, $target, $replaces) {
    try {
        if (!file_exists($source)) {
            throw new CoreException(tr('file_search_replace(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

        if (!file_exists(dirname($target))) {
            throw new CoreException(tr('file_search_replace(): Specified target path ":targetg" does not exist', array(':target' => $target)), 'not-exists');
        }

        if (!is_array($replaces)) {
            throw new CoreException(tr('file_search_replace(): Specified $replaces ":replaces" should be a search => replace array', array(':replaces' => $replaces)), 'invalid');
        }

        $fs       = fopen($source, 'r');
        $ft       = fopen($target, 'w');

        $position = 0;
        $length   = 8192;
        $filesize = filesize($source);

        while ($position < $filesize) {
             $data      = fread($fs, $length);
             $position += $length;
             fseek($fs, $position);

             /*
              * Execute search / replaces
              */
             foreach ($replaces as $search => $replace) {
                $data = preg_replace($search, $replace, $data);
             }

             fwrite($ft, $data, strlen($data));
        }

        fclose($fs);
        fclose($ft);

    }catch(Exception $e) {
        throw new CoreException(tr('file_search_replace(): Failed'), $e);
    }
}



/*
 * Return line count for this file
 */
function file_line_count($source) {
    try {
        if (!file_exists($source)) {
            throw new CoreException(tr('file_line_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

    }catch(Exception $e) {
        throw new CoreException(tr('file_line_count(): Failed'), $e);
    }
}



/*
 * Return word count for this file
 */
function file_word_count($source) {
    try {
        if (!file_exists($source)) {
            throw new CoreException(tr('file_word_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
        }

    }catch(Exception $e) {
        throw new CoreException(tr('file_word_count(): Failed'), $e);
    }
}



/*
 * Scan the entire file path upward for the specified file.
 * If the specified file doesn't exist in the specified path, go one dir up,
 * all the way to root /
 */
function file_scan($path, $file) {
    try {
        if (!file_exists($path)) {
            throw new CoreException(tr('file_scan(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
        }

        while (strlen($path) > 1) {
            $path = Strings::slash($path);

            if (file_exists($path.$file)) {
                /*
                 * The requested file is found! Return the path where it was found
                 */
                return $path;
            }

            $path = dirname($path);
        }

        return false;

    }catch(Exception $e) {
        throw new CoreException(tr('file_word_count(): Failed'), $e);
    }
}



/*
 * Move specified path to a backup
 */
function file_move_to_backup($path) {
    try {
        if (!file_exists($path)) {
            /*
             * Specified path doesn't exist, just ignore
             */
            return false;
        }

        $backup_path = $path.'~'.date_convert(null, 'Ymd-His');

        /*
         * Main sitemap file already exist, move to backup
         */
        if (file_exists($backup_path)) {
            /*
             * Backup already exists as well, script run twice
             * in under a second. Delete the current one
             * as the backup was generated less than a second
             * ago
             */
            file_delete($path, PATH_ROOT.'data/backups');
            return true;
        }

        rename($path, $backup_path);
        return true;

    }catch(Exception $e) {
        throw new CoreException(tr('file_move_to_backup(): Failed'), $e);
    }
}



/*
 * Update the specified file owner and group
 */
function file_chown($file, $user = null, $group = null) {
    try {
        if (!$user) {
             $user = posix_getpwuid(posix_getuid());
             $user = $user['name'];
        }

        if (!$group) {
             $group = posix_getpwuid(posix_getuid());
             $group = $group['name'];
        }

        $file = realpath($file);

        if (!$file) {
            throw new CoreException(tr('file_chown(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
        }

        if (!strstr($file, PATH_ROOT)) {
            throw new CoreException(tr('file_chown(): Specified file ":file" is not in the projects PATH_ROOT path ":path"', array(':path' => $path, ':file' => $file)), 'invalid');
        }

        safe_exec(array('commands' => array('chown', array('sudo' => true, $user.':'.$group, $file))));

    }catch(Exception $e) {
        throw new CoreException(tr('file_chown(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 *
 * @param string $path
 * @param string $prefix
 * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
 */
function file_path_contains_symlink($path, $prefix = null) {
    try {
        if (!$path) {
            throw new CoreException(tr('file_path_contains_symlink(): No path specified'), 'not-specified');
        }

        if (substr($path, 0, 1) === '/') {
            if ($prefix) {
                throw new CoreException(tr('file_path_contains_symlink(): The specified path ":path" is absolute, which requires $prefix to be null, but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
            }

            $location = '/';

        } else {
            /*
             * Specified $path is relative, so prefix it with $prefix
             */
            if (substr($prefix, 0, 1) !== '/') {
                throw new CoreException(tr('file_path_contains_symlink(): The specified path ":path" is relative, which requires an absolute $prefix but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
            }

            $location = Strings::endsWith($prefix, '/');
        }

        $path = Strings::endsNotWith(Strings::startsNotWith($path, '/'), '/');

        foreach (explode('/', $path) as $section) {
            $location .= $section;

            if (!file_exists($location)) {
                throw new CoreException(tr('file_path_contains_symlink(): The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', array(':path' => $path, ':prefix' => $prefix, ':location' => $location)), 'not-exists');
            }

            if (is_link($location)) {
                return true;
            }

            $location .= '/';
        }

        return false;

    }catch(Exception $e) {
        throw new CoreException(tr('file_path_contains_symlink(): Failed'), $e);
    }
}



/*
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_create_stream_context($context) {
    try {
        if (!$context) return null;

        if (!is_array($context)) {
            throw new CoreException(tr('file_create_stream_context(): Specified context is invalid, should be an array but is an ":type"', array(':type' => gettype($context))), 'invalid');
        }

        return stream_context_create($context);

    }catch(Exception $e) {
        throw new CoreException(tr('file_create_stream_context(): Failed'), $e);
    }
}



/*
 * Perform a "sed" action on the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_sed($params) {
    try {
        Arrays::ensure($params, 'ok_exitcodes,function,sudo,background,domain');

        if (empty($params['source'])) {
            throw new CoreException(tr('file_sed(): No source file specified'), 'not-specified');
        }

        if (empty($params['regex'])) {
            throw new CoreException(tr('file_sed(): No regex specified'), 'not-specified');
        }

        if (empty($params['target'])) {
            $arguments[] = 'i';
            $arguments[] = $params['regex'];
            $arguments[] = $params['source'];

        } else {
            $arguments[] = $params['regex'];
            $arguments[] = $params['source'];
            $arguments['redirect'] = '> '.$params['target'];
        }

        if (!empty($params['sudo'])) {
            $arguments['sudo'] = $params['sudo'];
        }

        safe_exec(array('domain'       => $params['domain'],
                        'background'   => $params['background'],
                        'function'     => $params['function'],
                        'ok_exitcodes' => $params['ok_exitcodes'],
                        'commands'     => array('sed' => $arguments)));

    }catch(Exception $e) {
        throw new CoreException('file_sed(): Failed', $e);
    }
}



/*
 * Cat the output from one file to another
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_cat($params) {
    try {
        Arrays::ensure($params, 'ok_exitcodes,function,sudo,background,domain');

        if (empty($params['source'])) {
            throw new CoreException(tr('file_cat(): No source file specified'), 'not-specified');
        }

        if (empty($params['target'])) {
            throw new CoreException(tr('file_cat(): No target file specified'), 'not-specified');
        }

        if (!empty($params['sudo'])) {
            $arguments['sudo'] = $params['sudo'];
        }

        safe_exec(array('domain'       => $params['domain'],
                        'background'   => $params['background'],
                        'function'     => $params['function'],
                        'ok_exitcodes' => $params['ok_exitcodes'],
                        'commands'     => array('cat' => $arguments)));

    }catch(Exception $e) {
        throw new CoreException('file_cat(): Failed', $e);
    }
}



/*
 * Ensure that the specified file is not in restricted zones. This applies to real paths, with their symlinks expaned
 *
 * Authorized areas, by default, are the following paths. Any other path will be restricted
 *
 * PATH_ROOT/data
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
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function Restrict::restrict($params, &$restrictions = null) {
    try {
        /*
         * Determine what restrictions apply. The restrictions is a white list
         * containing the paths where the calling function is allowed to work
         */
        if (!$restrictions) {
            /*
             * If the file was specified as an array, then the restrictions may
             * have been included in there for convenience.
             */
            if (is_array($params) and isset($params['restrictions'])) {
                $restrictions = $params['restrictions'];
            }

            if (!$restrictions) {
                /*
                 * Disable all restrictions?
                 */
                if ($restrictions === false) {
                    /*
                     * No restrictions required
                     */
                    return false;
                }

                /*
                 * Apply default restrictions
                 */
                $restrictions = array(PATH_ROOT.'data/tmp', PATH_ROOT.'data/cache', '/tmp');
            }

        } else {
            /*
             * Restrictions may have been specified as a CSV list, ensure its an
             * array so we can process then all
             */
            $restrictions = Arrays::force($restrictions);
        }

        /*
         * If this is a string containing a single path, then test it
         */
        if (is_string($params)) {
            /*
             * The file or path to be checked must start with the $restriction
             * Unslash the $restriction to avoid checking a path like "/test/"
             * against a restriction "/test" and having it fail because of the
             * missing slash at the end
             */
            foreach ($restrictions as &$restriction) {
                if ($restriction === false) {
                    return false;
                }

                $restriction = Strings::unslash($restriction);

                if (substr($params, 0, strlen($restriction)) === $restriction) {
                    /*
                     * Passed!
                     */
                    return;
                }
            }

            unset($restriction);
            throw new CoreException(tr('Restrict::restrict(): The specified file or path ":path" is outside of the authorized paths ":authorized"', array(':path' => $params, ':authorized' => $restrictions)), 'access-denied', $restrictions);
        }

        /*
         * Search for default fields
         */
        $keys = array('source', 'target', 'source_path', 'source_path', 'path');

        foreach ($keys as $key) {
            if (isset($params[$key])) {
                /*
                 * All these must be tested
                 */
                try {
                    Restrict::restrict($params[$key], $restrictions);

                }catch(Exception $e) {
                    throw new CoreException(tr('Restrict::restrict(): Failed for key ":key" test', array(':key' => $key)), $e);
                }
            }
        }

    }catch(Exception $e) {
        throw new CoreException('Restrict::restrict(): Failed', $e);
    }
}




/*
 * Locates the specifed command and returns it path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
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
function file_which($command, $whereis = false) {
    try {
        $result = safe_exec(array('ok_exitcodes' => '0,1',
                                  'commands'     => array(($whereis ? 'whereis' : 'which'), array($command))));

        $result = array_shift($result);

        return get_null($result);

    }catch(Exception $e) {
        throw new CoreException('file_which(): Failed', $e);
    }
}






/*
 * Search / replace the specified file
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @see file_copy_tree()
 *
 * @param array string $search The key(s) for which should be searched
 * @param array string $replace The values that will replace the specified key(s)
 * @param string $file The file that needs to have the search / replace done
 * @return string The $file
 */
function file_replace($search, $replace, $file) {
    try {
        $data = file_get_contents($file);
        $data = str_replace($search, $replace, $file);

        file_put_contents($file, $data);
        return $file;

    }catch(Exception $e) {
        throw new CoreException('file_replace(): Failed', $e);
    }
}



/*
 * DEPRECATED FUNCTIONS
 */
function file_chmod_tree($path, $filemode, $dirmode = 0770) {
    try {
        return file_chmod($path, $filemode, $dirmode = 0770);

    }catch(Exception $e) {
        throw new CoreException('file_chmod_tree(): Failed', $e);
    }
}



/*
 * Return a single file from the specified file section
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
 * @version 2.8.21: Added function and documentation
 * @note This function throws an exception if multiple files matched
 * @example
 * code
 * $result = file_from_path('/etc/passw');
 * showdie($result);
 * /code
 *
 * This would return
 * code
 * /etc/passwd
 * /code
 *
 * @param string $file
 * @return string The result
 */
function file_from_part($file) {
    try {
        $target = null;

        file_tree_execute(array('execute_directory' => true,
                                'path'              => dirname($file),
                                'callback'          => function($path) use ($file, &$target) {
                                                           if (str_contains($path, $file)) {
                                                               if ($target) {
                                                                   /*
                                                                    * We already found another file matching the specified path part
                                                                    */
                                                                   throw new CoreException(tr('file_from_path(): Found multiple files for specified file part ":file"', array(':file' => $file)), 'multiple');
                                                               }

                                                               $target = $path;
                                                           }
                                              }));

        return $target;

    }catch(Exception $e) {
        throw new CoreException(tr('file_from_part(): Failed'), $e);
    }
}



/*
 * realpath() wrapper that won't crash with an exception if the specified string is not a real path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package file
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
 * @return boolean string The real path extrapolated from the specified $path, if exists. False if whatever was specified does not exist.
 */
function file_is_path($path) {
    try {
        return realpath($path);

    }catch(Exception $e) {
        if (!is_string($path)) {
            throw new CoreException(tr('file_is_path(): The specified path should be a string but is a ":type"', array(':type' => $path)), 'invalid');
        }

        /*
         * If PHP threw an error for the path not being a path at all, just
         * return false
         */
        $data = $e->getData(true);

        if (str_contains($data, 'expects parameter 1 to be a valid path')) {
            return false;
        }

        /*
         * This is some other error, keep throwing
         */
        throw new CoreException(tr('file_is_path(): Failed'), $e);
    }
}
