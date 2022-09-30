<?php

namespace Phoundation\Filesystem;

use Exception;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Processes\Exception\ProcessesException;
use Throwable;



/**
 * File class
 *
 * This library contains various filesystem file related functions
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class File
{
    /**
     * Append specified data string to the end of the specified file
     *
     * @param string $file
     * @param string $data
     * @return void
     * @throws FilesystemException
     */
    public static function append(string $file, string $data): void
    {
        Path::ensure(dirname($file));

        $h = fopen($file, 'a');
        fwrite($h, $data);
        fclose($h);
    }



    /**
     * Concatenates a list of files to a target file
     *
     * @param string $target
     * @param string|array $sources
     */
    public static function concat(string $target, string|array $sources): void
    {
        if (!is_array($sources)) {
            $sources = array($sources);
        }

        // Ensure the target path exists
        Path::ensure(dirname($target));

        try {
            $target_h = fopen($target, 'a');
        } catch (Throwable $e) {
            // Failed to open the target file
            self::checkReadable($source, 'target', $e);
        }

        foreach($sources as $source) {
            try {
                $source_h = fopen($source, 'r');
            } catch (Throwable $e) {
                // Failed to open one of the sources, get rid of the partial target file
                self::delete($target);
                self::checkReadable($source, 'source', $e);
            }

            while (!feof($source_h)) {
                $data = fread($source_h, 8192);
                fwrite($target_h, $data);
            }

            fclose($source_h);
        }

        fclose($target_h);
    }



    /**
     * Move uploaded image to correct target
     *
     * @param array|string $source The source file to process
     * @return string The new file path
     * @throws CoreException
     */
    public static function getUploaded(array|string $source): string
    {
        $destination = ROOT.'data/uploads/';

        if (is_array($source)) {
            /*
             * Assume this is a PHP file upload array entry
             */
            if (empty($source['tmp_name'])) {
                throw new FilesystemException(tr('file_move_uploaded(): Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry'), 'invalid');
            }

            $real   = $source['name'];
            $source = $source['tmp_name'];

        } else {
            $real   = basename($source);
        }

        is_file($source);
        Path::ensure($destination);

        // Ensure we're not overwriting anything!
        if (file_exists($destination . $real)) {
            $real = Strings::untilReverse($real, '.').'_'.substr(uniqid(), -8, 8).'.'.Strings::fromReverse($real, '.');
        }

        if (!move_uploaded_file($source, $destination . $real)) {
            throw new FilesystemException(tr('Failed to move file ":source" to destination ":destination"', [':source' => $source, ':destination' => $destination]), 'move');
        }

        // Return destination file
        return $destination . $real;
    }


    /**
     * Create a target, but don't put anything in it
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function assignTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return self::moveToTarget('', $path, $extension, $singledir, $length);
    }


    /**
     * Create a target, but don't put anything in it, and return path+filename without extension
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function assignTargetClean(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return str_replace($extension, '', self::moveToTarget('', $path, $extension, $singledir, $length));
    }


    /**
     * Copy specified file, see file_move_to_target for implementation
     *
     * @param string $file
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public static function copyToTarget(string $file, string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return self::moveToTarget($file, $path, $extension, $singledir, $length, true);
    }


    /**
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
     * @param array|string $file
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @param bool $copy
     * @param string $context
     * @return string The target file
     * @throws Exception
     */
    public static function moveToTarget(array|string $file, string $path, bool $extension = false, bool $singledir = false, int $length = 4, bool $copy = false, mixed $context = null): string
    {
        if (is_array($file)) {
            // Assume this is a PHP $_FILES array entry
            $upload = $file;
            $file   = $file['name'];
        }

        if (isset($upload) and $copy) {
            throw new FilesystemException(tr('Copy option has been set, but specified file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', [':file' => $file]));
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

        $target = $targetpath.strtolower(Strings::convertAccents(Strings::untilReverse($filename, '.'), '-'));

        /*
         * Check if there is a "point" already in the extension
         * not obligatory at the start of the string
         */
        if ($extension) {
            if (!str_contains($extension, '.')) {
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
                return File::moveToTarget($upload, $path, $extension, $singledir, $length, $copy);
            }

            return File::moveToTarget($file, $path, $extension, $singledir, $length, $copy);
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
    }



    /**
     * Creates a random path in specified base path (If it does not exist yet), and returns that path
     *
     * @param string $path
     * @param bool $singledir
     * @param int $length
     * @return string
     */
    public static function createTargetPath(string $path, bool $singledir = false, int $length = 0): string
    {
        if (!$length) {
            $length = Config::get('filesystem.target_path_size', 8);
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
            foreach(str_split(substr(uniqid(), -$length, $length)) as $char) {
                $path .= DIRECTORY_SEPARATOR.$char;
            }
        }

        return Strings::slash(Path::ensure($path));
    }



    /**
     * Ensure that the specified file exists in the specified path
     *
     * @note Will log to the console in case the file was created
     * @version 2.4.16: Added documentation, improved log output
     *
     * @param string $file The file that must exist
     * @param null $mode If the specified $file does not exist, it will be created with this file mode. Defaults to $_CONFIG[fs][file_mode]
     * @param null $path_mode If parts of the path for the file do not exist, these will be created as well with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @return string The specified file
     */
    public static function ensureFile($file, $mode = null, $path_mode = null): string
    {
        $mode = Config::get('filesystem.modes.defaults.file', 0640, $mode);
        Path::ensure(dirname($file), $path_mode);

        if (!file_exists($file)) {
            // Create the file
            self::executeMode(dirname($file), 0770, function() use ($file, $mode) {
                Log::warning(tr('File ":file" did not exist and was created empty to ensure system stability, but information may be missing', [':file' => $file]));
                touch($file);

                if ($mode) {
                    chmod($file, $mode);
                }
            });
        }

        return $file;
    }



    /**
     * Delete the path, and each parent directory until a non-empty directory is encountered
     *
     * @see $restrictions->apply() This function uses file location restrictions, see $restrictions->apply() for more information
     * @param array|string $paths A list of path patterns to be cleared
     * @param ?Restrictions $restrictions A list of paths to which file_delete() operations will be restricted
     * @return void
     */
    public static function clearPath(array|string $paths, ?Restrictions $restrictions = null): void
    {
        // Multiple paths specified, clear all
        if (is_array($paths)) {
            foreach($paths as $path) {
                file_clear_path($path, $restrictions);
            }

            return;
        }

        $path = $paths;

        // Restrict location access
        $restrictions::apply($path, $restrictions);

        if (!file_exists($path)) {
            // This section does not exist, jump up to the next section
            $path = dirname($path);

            try {
                $restrictions::apply($path, $restrictions);
                File::clearPath($path, $restrictions);

            }catch(RestrictionsException $e) {
                // We no longer have access to move up more, stop here.
                Log::warning(tr('Stopped recursing upward on path ":path" because filesystem restrictions do not permit to move further up', [':path' => $path]));
                return;
            }
        }

        if (!is_dir($path)) {
            // This is a normal file. Delete it and continue with the directory above
            unlink($path);

        } else {
            // This is a directory. See if its empty
            $h        = opendir($path);
            $contents = false;

            while (($file = readdir($h)) !== false) {
                // Skip . and ..
                if (($file == '.') or ($file == '..')) continue;

                $contents = true;
                break;
            }

            closedir($h);

            if ($contents) {
                // Do not remove anything more, there is contents here!
                return;
            }

            // Remove this entry and continue;
            try {
                File::executeMode(dirname($path), (is_writable(dirname($path)) ? false : 0770), function() use ($restrictions, $path) {
                    file_delete([
                        'patterns'       => $path,
                        'clean_path'     => false,
                        'force_writable' => true,
                        'restrictions'   => $restrictions
                    ]);
                });

            }catch(Exception $e) {
                /*
                 * The directory WAS empty, but cannot be removed
                 *
                 * In all probability, a parrallel process added a new content
                 * in this directory, so it's no longer empty. Just register
                 * the event and leave it be.
                 */
                Log::warning(tr('file_clear_path(): Failed to remove empty path ":path" with exception ":e"', [':path' => $path, ':e' => $e]));
                return;
            }
        }

        // Go one entry up, check if we're still within restrictions, and continue deleting
        $path = dirname($path);

        try {
            File::clearPath($path, $restrictions);

        }catch(RestrictionsException $e) {
            // We no longer have access to move up more, stop here.
            Log::warning(tr('file_clear_path(): Stopped recursing upward on path ":path" because restrictions do not allow us to move further up', [':path' => $path]));
            return;
        }
    }



    /**
     * Return the extension of the specified filename
     *
     * @param string $filename
     * @return string
     */
    public static function getExtension(string $filename): string
    {
        return Strings::fromReverse($filename, '.');
//        return pathinfo($file, PATHINFO_EXTENSION);
    }



    /**
     * Return a file path for a temporary file
     *
     * @param bool|string $create If set to false, only the file path will be returned, the temporary file will NOT be
     *                            created. If set to true, the file will be created. If set to a string, the temp file
     *                            will be created with as contents the $create string
     * @param bool $extension If specified, use ROOT/data/tmp/$name instead of a randomly generated filename
     * @param bool $limit_to_session
     * @return string The filename for the temp file
     * @version 2.5.90: Added documentation, expanded $create to be able to contain data for the temp file
     * @note: If the resolved temp file path already exist, it will be deleted!
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
     */
    public static function temp(bool|string $create = true, bool $extension = null, bool $limit_to_session = true) : string
    {
        Path::ensure(TMP);

        // Temp file will contain the session ID
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
            // Temp file will have specified extension
            $name .= '.'.$extension;
        }

        $file = TMP.$name;

        // Temp file can not exist
        if (file_exists($file)) {
            File::delete($file);
        }

        if ($create) {
            if ($create === true) {
                touch($file);

            } else {
                if (!is_string($create)) {
                    throw new FilesystemException(tr('Specified $create variable is of datatype ":type" but should be either false, true, or a data string that should be written to the temp file', [':type' => gettype($create)]));
                }

                file_put_contents($file, $create);
            }
        }

        return $file;
    }



    /**
     * Return the absolute path for the specified path
     *
     * @param string $path
     * @return string The absolute path
     */
    public static function absolutePath(string $path): string
    {
        if (!$path) {
            return getcwd();
        }

        if ($path[0] === '/') {
            return $path;
        }

        return Strings::slash(getcwd()).Strings::unslash($path);
    }



    /**
     * Returns the mimetype data for the specified file
     *
     * @version 2.4: Added documentation
     * @param string $file to be tested
     * @return string The mimetype data for the specified file
     */
    public static function mimetype(string $file): string
    {
        static $finfo = false;

        /*
         * Check the specified file
         */
        if (!$file) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        if (!$finfo) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
        }

        $mimetype = finfo_file($finfo, $file);

        if (!$mimetype) {
            // We failed to get mimetype data. Find out why and throw exception
            self::checkReadable($file, '', true, new FilesystemException(tr('Failed to get mimetype information for file ":file"', [':file' => $file])));
        }

        return $mimetype;
    }



    /**
     * Returns true or false if file is ASCII or not
     *
     * @version 2.4: Added documentation
     * @param string $file The file to be tested
     * @return bool True if the file is a text file, false if not
     */
    public static function isText(string $file): bool
    {
        if (Strings::until(file_mimetype($file), '/') == 'text') return true;
        if (Strings::from(file_mimetype($file), '/') == 'xml' ) return true;

        return false;
    }



    /**
     * Return all files in a directory that match the specified pattern with optional recursion.
     *
     * @version 2.4.40: Added documentation, upgraded function
     * @param string $path The path from which
     * @param ?string $pattern
     * @param boolean $recursive If set to true, return all files below the specified path, including in sub-directories
     * @return array The matched files
     */
    public static function listTree(string $path, ?string $pattern = null, bool $recursive = true): array
    {
        // Validate path
        Path::checkReadable($path);

        $retval = [];
        $fh    = opendir($path);

        // Go over all files
        while (($filename = readdir($fh)) !== false) {
            // Loop through the files, skipping . and .. and recursing if necessary
            if (($filename == '.') or ($filename == '..')) {
                continue;
            }

            // Does the file match the specified pattern?
            if ($pattern) {
                $match = preg_match($pattern, $filename);

                if (!$match) {
                    continue;
                }
            }

            // Get the complete file path
            $file = Strings::slash($path).$filename;

            // Add the file to the list. If the file is a directory, then recurse instead. Do NOT add the directory
            // itself, only files!
            if (is_dir($file) and $recursive) {
                $retval = array_merge($retval, file_list_tree($file));

            } else {
                $retval[] = $file;
            }
        }

        closedir($fh);

        return $retval;
    }



    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @see File::safePattern()
     * @see $restrictions->apply() This function uses file location restrictions, see $restrictions->apply() for more information
     * @version 2.7.60: Fixed safe file pattern issues
     * @param params $params
     * @param list $params[patterns] A list of path patterns to be deleted
     * @param null list $params[restrictions] A list of paths to which file_delete() operations will be restricted
     * @param boolean $params[clean_path] If specified true, all directories above each specified pattern will be deleted as well as long as they are empty. This way, no empty directories will be left laying around
     * @param boolean $params[sudo] If specified true, the rm command will be executed using sudo
     * @param boolean $params[force_writable] If specified true, the function will first execute chmod ug+w on each specified patterns before deleting them
     * @return natural The amount of orphaned files, and orphaned `files` entries found and processed
     */
    public static function delete(string|array $patterns, bool $clean_path = true, bool $sudo = false, bool $force_writable = false, ?Restrictions $restrictions = null): void
    {
        // Both patterns and restrictions should be arrays, make them so now to avoid them being converted multiple
        // times later on
        $patterns     = Arrays::force($patterns);
        $restrictions = Arrays::force($restrictions);

        /*
         * Delete all specified patterns
         */
        foreach($patterns as $pattern) {
            /*
             * Restrict pattern access
             */
            $restrictions::apply($pattern);

            if ($force_writable) {
                try {
                    /*
                     * First ensure that the files to be deleted are writable
                     */
                    File::chmod([
                        'path'         => $pattern,
                        'mode'         => 'ug+w',
                        'recursive'    => true,
                        'restrictions' => $restrictions
                    ]);

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
            safe_exec(array('commands' => array('rm', array('sudo' => $sudo, '-rf', '#' => File::safePattern($pattern)))));

            /*
             * If specified to do so, clear the path upwards from the specified
             * pattern
             */
            if ($params['clean_path']) {
                file_clear_path(dirname($pattern), $restrictions);
            }
        }
    }



    /**
     * Returns a safe version of the specified pattern
     *
     * @see File::delete()
     * @see File::chown()
     * @version 2.7.60: Added function and documentation
     * @param string $pattern The pattern to make safe
     * @return string The safe pattern
     */
    public static function safePattern(string|array $pattern): string
    {
        /*
         * Escape patterns manually here, safe_exec() will be told NOT to
         * escape them to avoid issues with *
         */
        $pattern = Arrays::force($pattern, '*');

        foreach($pattern as &$item) {
            $item = escapeshellarg($item);
        }

        return implode('*', $pattern);
    }



    /**
     * Copy an entire tree with replace option
     *
     * Extensions (may be string or array with strings) sets which file extensions will have search / replace. If set to
     * false all files will have search / replace applied.
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
    public static function copyTree(string $source, string $destination, array $search = null, array $replace = null, string|array $extensions = null, mixed $mode = true, bool $novalidate = false, ?Restrictions $restrictions = null): string
    {
        throw new UnderConstructionException('File::copyTree() is under construction');

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
                        throw new FilesystemException(tr('file_copy_tree(): The search parameters count ":search" and replace parameters count ":replace" do not match', array(':search' => count($search), ':replace' => count($replace))), 'parameternomatch');
                    }
                }

                if (!file_exists($source)) {
                    throw new FilesystemException(tr('file_copy_tree(): Specified source ":source" does not exist', array(':source' => $source)), 'not-exists');
                }

                $destination = Strings::unslash($destination);

                if (!file_exists($destination)) {
// :TODO: Check if dirname() here is correct? It somehow does not make sense
                    if (!file_exists(dirname($destination))) {
                        throw new FilesystemException(tr('file_copy_tree(): Specified destination ":destination" does not exist', array(':destination' => dirname($destination))), 'not-exists');
                    }

                    if (!is_dir(dirname($destination))) {
                        throw new FilesystemException(tr('file_copy_tree(): Specified destination ":destination" is not a directory', array(':destination' => dirname($destination))), 'not-directory');
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
                            throw new FilesystemException(tr('file_copy_tree(): Cannot copy source directory ":source" into destination file ":destination"', array(':source' => $source, ':destination' => $destination)), 'failed');
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

                foreach(scandir($source) as $file) {
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

                    foreach($extensions as $extension) {
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

                    foreach($search as $id => $svalue) {
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
                        throw new FilesystemException(tr('file_copy_tree(): Failed to set filemode for ":destination"', array(':destination' => $destination)), $e);
                    }
                }
            }

            return $destination;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_copy_tree(): Failed'), $e);
        }
    }


    /**
     * Search for $search file in $source, and move them all to $destination using the $rename result expression
     *
     * @param string $source
     * @param string $destination
     * @param $search
     * @param $rename
     * @return void
     */
    public static function rename(string $source, string $destination, $search, $rename): void
    {
        throw new UnderConstructionException('File::copyTree() is under construction');

        try {
            /*
             * Validations
             */
            if (!file_exists($source)) {
                throw new FilesystemException(tr('file_rename(): Specified source ":source" does not exist', array(':source' => $source)), 'exists');
            }

            if (!file_exists($destination)) {
                throw new FilesystemException(tr('file_rename(): Specified destination ":destination" does not exist', array(':destination' => $destination)), 'exists');
            }

            if (!is_dir($destination)) {
                throw new FilesystemException(tr('file_rename(): Specified destination ":destination" is not a directory', array(':destination' => $destination)), 'invalid');
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
            throw new FilesystemException(tr('file_rename(): Failed'), $e);
        }
    }


    /**
     * Change file mode, optionally recursively
     *
     * @param string $path
     * @param string $mode The mode to apply to the specified path (and all files below if recursive is specified)
     * @param boolean $recursive If set to true, apply specified mode to the specified path and all files below by recursion
     * @param bool $sudo
     * @param int $timeout
     * @param ?Restrictions $restrictions
     * @return void
     * @see File::safePattern()
     * @version 2.6.30: Added function and documentation
     * @version 2.7.60: Fixed safe file pattern issues
     */
    public static function chmod(string $path, string $mode, bool $recursive = false, bool $sudo = false, int $timeout = 30, ?Restrictions $restrictions = null): void
    {
        if (!($mode)) {
            throw new OutOfBoundsException(tr('No file mode specified'));
        }

        if (!$path) {
            throw new OutOfBoundsException(tr('No path specified'));
        }

        foreach(Arrays::force($path) as $path) {
            $restrictions->apply($path, $restrictions);

            $arguments      = array();
            $arguments[]    = $mode;
            $arguments['#'] = File::safePattern($path);

            if ($recursive) {
                $arguments[] = '-R';
            }

            if ($sudo) {
                $arguments['sudo'] = $sudo;
            }

            safe_exec([
                'timeout'  => $timeout,
                'commands' => array('chmod', $arguments
            ]);
        }
    }


    /**
     * Return a system path for the specified type
     *
     * @param string $type
     * @param string $path
     * @return string
     */
    public static function systemPath(string $type, string $path = ''): string
    {
        switch($type) {
            case 'img':
                // FALLTHROUGH
            case 'image':
                return '/pub/img/'.$path;

            case 'css':
                // FALLTHROUGH
            case 'style':
                return '/pub/css/'.$path;

            default:
                throw new OutOfBoundsException(tr('Unknown type ":type" specified', [':type' => $type]));
        }
    }


    /**
     * Pick and return a random file name from the specified path
     *
     * @note This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
     *
     * @param string $path
     * @return string
     */
    public static function random(string $path): string
    {
        if (!file_exists($path)) {
            throw new FileNotExistException(tr('The specified path ":path" does not exist', [':path' => $path]));
        }

        $files = scandir($path);

        unset($files[array_search('.' , $files)]);
        unset($files[array_search('..', $files)]);

        if (!$files) {
            throw new FilesystemException(tr('file_random(): The specified path ":path" contains no files', array(':path' => $path)), 'not-exists');
        }

        return Strings::slash($path) . array_get_random($files);
    }



    /*
     * Store a file temporarily with a label in $_SESSION['files'][label]
     */
    public static function sessionStore($label, $file = null, $path = TMP)
    {
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
            throw new FilesystemException(tr('file_session_store(): Failed'), $e);
        }
    }



    /*
     * Checks if the specified path exists, is a dir, and optionally, if its writable or not
     */
    public static function checkDir($path, $writable = false)
    {
        try {
            if (!file_exists($path)) {
                throw new FilesystemException(tr('file_check_dir(): The specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
            }

            if (!is_dir($path)) {
                throw new FilesystemException(tr('file_check_dir(): The specified path ":path" is not a directory', array(':path' => $path)), 'notadirectory');
            }

            if ($writable and !is_writable($path)) {
                throw new FilesystemException(tr('file_check_dir(): The specified path ":path" is not writable', array(':path' => $path)), 'notwritable');
            }

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_check_dir(): Failed'), $e);
        }
    }



    /**
     * If the specified file is an HTTP, HTTPS, or FTP URL, then get it locally as a temp file
     *
     * @param string $url
     * @param bool $is_downloaded
     * @param ?array $context
     * @return string
     */
    public static function getLocal($url, bool &$is_downloaded = false, ?array $context = null): string
    {
        try {
            $context = File::createStreamContext($context);
            $url     = trim($url);

            if (str_contains($url, 'http:') and str_contains($url, 'https:') and str_contains($url, 'ftp:')) {
                if (!file_exists($url)) {
                    throw new FileNotExistException(tr('Specified file ":file" does not exist', [':file' => $url]));
                }

                if (is_uploaded_file($url)) {
                    $tmp  = file_get_uploaded($url);
                    $file = File::temp($url, null, false);

                    rename($tmp, $file);
                    return $file;
                }

                return $url;
            }

            // First download the file to a temporary location
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
                throw new FilesystemException(tr('file_get_local(): URL ":file" does not exist', array(':file' => $url)), 'file-404');
            }

            if (str_contains($message, '400 bad request')) {
                throw new FilesystemException(tr('file_get_local(): URL ":file" is invalid', array(':file' => $url)), 'file-400');
            }

            throw new FilesystemException(tr('file_get_local(): Failed for file ":file"', array(':file' => $url)), $e);
        }
    }



    /*
     * Send the specified file to the client as a download using the HTTP protocol with correct headers
     *
     * @version 2.5.89: Rewrote function and added documentation
     * @param params $params The file parameters
     * @return void
     */
    public static function httpDownload($params)
    {
        global $_CONFIG;

        try {
            Arrays::ensure($params, 'file,data,name');
            array_default($params, 'restrictions', ROOT.'data/downloads');
            array_default($params, 'compression' , $_CONFIG['file']['download']['compression']);
            array_default($params, 'filename'    , basename($params['file']));
            array_default($params, 'attachment'  , false);
            array_default($params, 'die'         , true);

            /*
             * Validate the file name for the user
             */
            if (!$params['filename']) {
                throw new FilesystemException(tr('file_http_download(): No filename specified. Note: This is not the file to be downloaded to the client, but the name it will have when saved on the clients storage'), 'not-specified');
            }

            if (!is_scalar($params['filename'])) {
                throw new FilesystemException(tr('file_http_download(): Specified filename ":filename" is not scalar', array(':filename' => $params['filename'])), 'invalid');
            }

            if (mb_strlen($params['filename']) > 250) {
                throw new FilesystemException(tr('file_http_download(): Specified filename ":filename" is too long, it cannot be longer than 250 characters', array(':filename' => $params['filename'])), 'invalid');
            }

            if ($params['data']) {
                /*
                 * Send the specified data as a file to the client
                 * Write the data to a temp file first so we can just upload from
                 * there
                 */
                if ($params['file']) {
                    throw new FilesystemException(tr('file_http_download(): Both "file" and "data" were specified, these parameters are mutually exclusive. Please specify one or the other'), 'invalid');
                }

                $params['file'] = file_temp($params['data']);
                $params['data'] = $params['file'];
            }

            if (!$params['file']) {
                throw new FilesystemException(tr('file_http_download(): No file or data specified to download to client'), 'not-specified');
            }

            /*
             * Send a file from disk
             * Validate data
             */
            if (!is_scalar($params['file'])) {
                throw new FilesystemException(tr('file_http_download(): Specified file ":file" is not scalar', array(':file' => $params['file'])), 'invalid');
            }

            if (!file_exists($params['file'])) {
                throw new FilesystemException(tr('file_http_download(): Specified file ":file" does not exist or is not accessible', array(':file' => $params['file'])), 'not-exists');
            }

            if (!is_readable($params['file'])) {
                throw new FilesystemException(tr('file_http_download(): Specified file ":file" exists but is not readable', array(':file' => $params['file'])), 'not-readable');
            }

            $restrictions->apply($params['file'], $restrictions);

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

            throw new FilesystemException(tr('file_http_download(): Failed'), $e);
        }
    }



    /*
     * Return true if the specified mimetype is for a binary file or false if it is for a text file
     *
     * @version 2.5.90: Added function and documentation
     * @param string $mimetype The primary mimetype section to check. If the mimetype is "text/plain", this variable would receive "text". You can also leave $secondary empty and specify the complete mimetype "text/plain" here, both will work
     * @param string $mimetype The secondary mimetype section to check. If the mimetype is "text/plain", this variable would receive "plain". If the complete mimetype is specified in $primary, you can leave this one empty
     * @return boolean True if the specified mimetype is for a binary file, false if it is a text file
     */
    public static function isBinary($primary, $secondary = null) {
        try {
// :TODO: IMPROVE THIS! Loads of files that are not text/ are still not binary
            /*
             * Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
             */
            if (!$secondary) {
                if (!str_contains($primary, '/')) {
                    throw new FilesystemException(tr('file_is_compressed(): Invalid primary mimetype data "" specified. Either specify the complete mimetype in $primary, or specify the independant primary and secondary sections in $primary and $secondary', array(':primary' => $primary)), $e);
                }

                $secondary = Strings::from($primary , '/');
                $primary   = Strings::until($primary, '/');
            }

            /*
             * Check the mimetype data
             */
            switch($primary) {
                case 'text':
                    /*
                     * Readonly
                     */
                    return false;

                default:
                    switch($secondary) {
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

        }catch(Exception $e) {
            throw new FilesystemException('file_is_binary(): Failed', $e);
        }
    }



    /*
     * Return true if the specified mimetype is for a compressed file, false if not
     *
     * This function will check the primary and secondary sections of the mimetype and depending on their values, determine if the file format should use compression or not
     *
     * @version 2.5.90: Added function and documentation
     * @param string $mimetype The primary mimetype section to check. If the mimetype is "text/plain", this variable would receive "text". You can also leave $secondary empty and specify the complete mimetype "text/plain" here, both will work
     * @param string $mimetype The secondary mimetype section to check. If the mimetype is "text/plain", this variable would receive "plain". If the complete mimetype is specified in $primary, you can leave this one empty
     * @return boolean True if the specified mimetype is for a compressed file, false if not
     */
    public static function isCompressed($primary, $secondary = null) {
        try {
// :TODO: IMPROVE THIS! Loads of files that may be mis detected
            /*
             * Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
             */
            if (!$secondary) {
                if (!str_contains($primary, '/')) {
                    throw new FilesystemException(tr('file_is_compressed(): Invalid primary mimetype data "" specified. Either specify the complete mimetype in $primary, or specify the independant primary and secondary sections in $primary and $secondary', array(':primary' => $primary)), $e);
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
                switch($secondary) {
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
                        switch($primary) {
                            case 'audio':
                                switch($secondary) {
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

        }catch(Exception $e) {
            throw new FilesystemException('template_function(): Failed', $e);
        }
    }



    /*
     * Send the specified file to the client using the HTTP protocol with correct headers
     *
     * @version 2.5.92: Rewrote function and added documentation
     * @param $file
     * @return void
     */
    public static function httpSend($params) {
        global $_CONFIG;

        try {
            Arrays::ensure($params, 'file,data,name');
            array_default($params, 'restrictions', ROOT.'data/downloads');
            array_default($params, 'compression' , $_CONFIG['file']['download']['compression']);
            array_default($params, 'filename'    , basename($params['file']));
            array_default($params, 'die'         , true);

            /*
             * Do we need compression?
             */
            if ($params['compression']) {
                apache_setenv('no-gzip', 0);
                ini_set('zlib.output_compression', 'On');

            } else {
                apache_setenv('no-gzip', 1);
                ini_set('zlib.output_compression', 'Off');
            }

            /*
             * Validate the file name for the user
             */
            if (!$params['filename']) {
                throw new FilesystemException(tr('file_http_download(): No filename specified. Note: This is not the file to be downloaded to the client, but the name it will have when saved on the clients storage'), 'not-specified');
            }

            if (!is_scalar($params['filename'])) {
                throw new FilesystemException(tr('file_http_download(): Specified filename ":filename" is not scalar', array(':filename' => $params['filename'])), 'invalid');
            }

            if (mb_strlen($params['filename']) > 250) {
                throw new FilesystemException(tr('file_http_download(): Specified filename ":filename" is too long, it cannot be longer than 250 characters', array(':filename' => $params['filename'])), 'invalid');
            }

            if ($params['data']) {
                /*
                 * Send the specified data as a file to the client
                 * Write the data to a temp file first so we can just upload from
                 * there
                 */
                if ($params['file']) {
                    throw new FilesystemException(tr('file_http_download(): Both "file" and "data" were specified, these parameters are mutually exclusive. Please specify one or the other'), 'invalid');
                }

                $params['file'] = file_temp($params['data']);
                unset($params['data']);
            }

            if (!$params['file']) {
                throw new FilesystemException(tr('file_http_download(): No file or data specified to download to client'), 'not-specified');
            }

            /*
             * Send a file from disk
             * Validate data
             */
            if (!is_scalar($params['file'])) {
                throw new FilesystemException(tr('file_http_download(): Specified file ":file" is not scalar', array(':file' => $params['file'])), 'invalid');
            }

            if (file_exists($params['file'])) {
                throw new FilesystemException(tr('file_http_download(): Specified file ":file" does not exist or is not accessible', array(':file' => $params['file'])), 'not-exists');
            }

            if (is_readable($params['file'])) {
                throw new FilesystemException(tr('file_http_download(): Specified file ":file" exists but is not readable', array(':file' => $params['file'])), 'not-readable');
            }

            $restrictions->apply($params['file'], $restrictions);

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

            if ($params['die']) {
                die();
            }

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_http_download(): Failed'), $e);
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
    public static function copyProgress($source, $target, $callback) {
        try {
            $c = stream_context_create();
            stream_context_set_params($c, array('notification' => $callback));
            copy($source, $target, $c);

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_copy_progress(): Failed'), $e);
        }
    }



    /*
     *
     */
    public static function modeReadable($mode) {
        try {
            $retval = '';
            $mode   = substr((string) decoct($mode), -3, 3);

            for($i = 0; $i < 3; $i++) {
                $number = (integer) substr($mode, $i, 1);

                if (($number - 4) >= 0) {
                    $retval .= 'r';
                    $number -= 4;

                } else {
                    $retval .= '-';
                }

                if (($number - 2) >= 0) {
                    $retval .= 'w';
                    $number -= 2;

                } else {
                    $retval .= '-';
                }

                if (($number - 1) >= 0) {
                    $retval .= 'x';

                } else {
                    $retval .= '-';
                }
            }

            return $retval;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_mode_readable(): Failed'), $e);
        }
    }



    /*
     * Calculate either the total size of the tree under the specified path, or the amount of files (directories not included in count)
     * @$method (string) either "size" or "count", the required value to return
     */
    public static function tree($path, $method) {
        try {
            if (!file_exists($path)) {
                throw new FilesystemException(tr('file_tree(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
            }

            switch($method) {
                case 'size':
                    // FALLTHROUGH
                case 'count':
                    break;

                default:
                    throw new FilesystemException(tr('file_tree(): Unknown method ":method" specified', array(':method' => $method)), 'unknown');
            }

            $retval = 0;
            $path   = Strings::slash($path);

            foreach(scandir($path) as $file) {
                if (($file == '.') or ($file == '..')) continue;

                if (is_dir($path.$file)) {
                    $retval += file_tree($path.$file, $method);

                } else {
                    switch($method) {
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

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_tree(): Failed'), $e);
        }
    }



    /**
     * Check if the specified file exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string $file
     * @param string|null $type
     * @param bool $no_directories
     * @param Throwable|null $previous_e
     * @return void
     */
    public static function checkReadable(string $file, ?string $type = null, bool $no_directories = false, ?Throwable $previous_e = null) : void
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

        if ($no_directories and is_dir($file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read because it is a directory', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }

        if ($previous_e) {
            // This method was called because a read action failed, throw an exception for it
            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }
    }



    /**
     * Check if the specified file exists and is writable. If not both, an exception will be thrown
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
    public static function checkWritable(string $file, ?string $type = null, ?Throwable $previous_e = null) : void
    {
        if (!file_exists($file)) {
            if (!file_exists(dirname($file))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist and neither does the parent path ":path"', [':type' => ($type ? '' : ' ' . $type), ':file' => $file, ':path' => dirname($file)]), previous: $previous_e);
            }

            throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }

        if (!is_readable($file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be written', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }

        if ($previous_e) {
            // This method was called because a read action failed, throw an exception for it
            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [':type' => ($type ? '' : ' ' . $type), ':file' => $file]), previous: $previous_e);
        }
    }



    /**
     * Ensure that the specified file is writable
     *
     * This method will ensure that the specified file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $file
     *
     * @param string $file
     * @param int|null $file_mode
     * @param int|null $directory_mode
     * @param string $type
     * @return string
     */
    public static function ensureWritable(string $file, ?int $file_mode = null, ?int $directory_mode = null, string $type = 'file'): string
    {
        // If the specified file exists and is writable, then we're done.
        if (is_writable($file)) {

            return false;
        }

        // From here the file is not writable. It may not exist or it may simply not be writable. Lets continue...

        // Get configuration. We need file and directory default modes
        $file_mode = Config::get('filesystem.mode.default.file', 0640, $file_mode);
        $directory_mode = Config::get('filesystem.mode.default.directory', 0750, $directory_mode);

        if (file_exists($file)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The specified file ":file" (Realpath ":path") is not writable. Attempting to apply default file mode "' . $file_mode . '"', [':file' => $file, ':path' => realpath($file)]);
                self::chmod($file, 'u+w');
                return $file;
            } catch (ProcessesException $e) {
                throw new FileNotWritableException(tr(
                    'The specified file ":file" (Realpath ":path") is not writable, and could not be made writable',
                    [
                        ':file' => $file,
                        ':path' => realpath($file)
                    ]
                ));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
        Path::ensure(dirname($file));
        Log::warning(tr(
            'The specified file ":file" (Realpath ":path") does not exist. Attempting to create it with file mode "' . $file_mode . '"',
            [
                ':file' => $file,
                ':path' => realpath($file)
            ]
        ));

        switch ($type) {
            case 'file':
                touch($file);
                chmod($file, $file_mode);
                break;

            case 'directory':
                mkdir($file);
                chmod($file, $directory_mode);
                break;

            default:
                throw new OutOfBoundsException('The specified type "' . Strings::log($type) . '" is invalid, it should be one of "file" or "directory"');
        }

        return realpath($file);
    }



    /*
     * Returns array with all permission information about the specified file.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     */
    public static function type($file) {
        $perms     = fileperms($file);
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
    }



    /*
     * Returns array with all permission information about the specified file.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     */
    public static function getPermissions($file) {
        try {
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

            if ($retval['socket']) {
                /*
                 * This file is a socket
                 */
                $retval['mode'] = 's';
                $retval['type'] = 'socket';

            } elseif ($retval['symlink']) {
                /*
                 * This file is a symbolic link
                 */
                $retval['mode'] = 'l';
                $retval['type'] = 'symbolic link';

            } elseif ($retval['regular']) {
                /*
                 * This file is a regular file
                 */
                $retval['mode'] = '-';
                $retval['type'] = 'regular file';

            } elseif ($retval['bdevice']) {
                /*
                 * This file is a block device
                 */
                $retval['mode'] = 'b';
                $retval['type'] = 'block device';

            } elseif ($retval['directory']) {
                /*
                 * This file is a directory
                 */
                $retval['mode'] = 'd';
                $retval['type'] = 'directory';

            } elseif ($retval['cdevice']) {
                /*
                 * This file is a character device
                 */
                $retval['mode'] = 'c';
                $retval['type'] = 'character device';

            } elseif ($retval['fifopipe']) {
                /*
                 * This file is a FIFO pipe
                 */
                $retval['mode'] = 'p';
                $retval['type'] = 'fifo pipe';

            } else {
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

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_get_permissions(): Failed'), $e);
        }
    }



    /*
     * Execute the specified callback on all files in the specified tree
     */
    public static function treeExecute($params) {
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
                throw new FilesystemException(tr('file_tree_execute(): No callback function specified'), 'not-specified');
            }

            if (!is_callable($params['callback'])) {
                throw new FilesystemException(tr('file_tree_execute(): Specified callback is not a function'), 'invalid');
            }

            if (!($path)) {
                throw new FilesystemException(tr('file_tree_execute(): No path specified'), 'not-specified');
            }

            if (substr($path, 0, 1) !== '/') {
                throw new FilesystemException(tr('file_tree_execute(): No absolute path specified'), 'invalid');
            }

            if (!file_exists($path)) {
                throw new FilesystemException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
            }

            /*
             * Follow hidden files?
             */
            if ((substr(basename($path), 0, 1) == '.') and !$params['follow_hidden']) {
                if (VERBOSE and PLATFORM_CLI) {
                    log_console(tr('file_tree_execute(): Skipping file ":file" because its hidden', array(':file' => $path)), 'yellow');
                }

                return 0;
            }

            /*
             * Filter this path?
             */
            foreach(Arrays::force($params['filters']) as $filter) {
                if (preg_match($filter, $path)) {
                    if (VERBOSE and PLATFORM_CLI) {
                        log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $path, ':filter' => $filter)), 'yellow');
                    }

                    return 0;
                }
            }

            $count = 0;
            $type  = file_type($path);

            switch($type) {
                case 'regular file':
                    $params['callback']($path);
                    $count++;

                    log_console(tr('file_tree_execute(): Executed code for file ":file"', array(':file' => $path)), 'VERYVERBOSEDOT/green');
                    break;

                case 'symlink':
                    if ($params['follow_symlinks']) {
                        $path = readlink($path);
                        $count += file_tree_execute($params);
                    }

                    break;

                case 'directory':
                    $h    = opendir($path);
                    $path = Strings::slash($path);

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
                                throw new FilesystemException(tr('file_tree_execute(): Specified path ":path" does not exist', array(':path' => $file)), 'not-exists');
                            }

                            $type = file_type($file);

                            switch($type) {
                                case 'link':
                                    if (!$params['follow_symlinks']) {
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
                                    if (($type != 'directory') or $params['execute_directory']) {
                                        /*
                                         * Filter this path?
                                         */
                                        $skip = false;

                                        foreach(Arrays::force($params['filters']) as $filter) {
                                            if (preg_match($filter, $file)) {
                                                if (VERBOSE and PLATFORM_CLI) {
                                                    log_console(tr('file_tree_execute(): Skipping file ":file" because of filter ":filter"', array(':file' => $path, ':filter' => $filter)), 'yellow');
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

                                    if (($type == 'directory') and $recursive) {
                                        $path = $file;
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
                        log_console(tr('file_tree_execute(): Skipping file ":file" with unsupported file type ":type"', array(':file' => $file, ':type' => $path)), 'yellow');
                    }
            }

            return $count;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_tree_execute(): Failed'), $e);
        }
    }



    /*
     * If specified path is not absolute, then return a path that is sure to start
     * from the current working directory
     */
    public static function absolute($path, $root = null) {
        try {
            if (empty($root)) {
                $root = Strings::slash(getcwd());
            }

            if (substr($path, 0, 1) !== '/') {
                $path = $root.$path;
            }

            return $path;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_absolute(): Failed'), $e);
        }
    }



    /*
     * If specified path is not absolute, then return a path that is sure to start
     * within ROOT
     */
    public static function root($path) {
        try {
            if (substr($path, 0, 1) !== '/') {
                $path = ROOT.$path;
            }

            return $path;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_root(): Failed'), $e);
        }
    }



    /*
     * Execute the specified callback after setting the specified mode on the
     * specified path. Once the callback has finished, the path will have its
     * original file mode applied again
     *
     * @see Path::ensure()
     * @version 2.7.13: Added function and documentation
     * @note: If the specified path has an asterix (*) in front of it, ALL sub directories will be updated with the specified mode, and each will have their original file mode restored after
     * @param string array $path The path that will have its mode updated. When * is added in front of the path, ALL sub directories will be updated with the new mode as well, and placed back with their old modes after the command has executed
     * @param string $mode The mode to which the specified directory should be set during execution
     * @param function $callback The function to be executed after the file mode of the specified path has been updated
     * @return string The result from the callback function
     */
    public static function executeMode($path, $mode, $callback, $params = null) {
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
                throw new FilesystemException(tr('File::executeMode(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
            }

            if (!is_string($callback) and !is_callable($callback)) {
                throw new FilesystemException(tr('File::executeMode(): Specified callback ":callback" is invalid, it should be a string or a callable function', array(':callback' => $callback)), 'invalid');
            }

            /*
             * Set the requested mode
             */
            try {
                if (is_dir($path) and $multi) {
                    $paths = cli_find(array('type'  => 'd',
                        'start' => $path));

                    foreach($paths as $subpath) {
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
                        throw new FilesystemException(tr('File::executeMode(): Failed to set mode "0:mode" to specified path ":path", access denied', array(':mode' => decoct($mode), ':path' => $path)), $e);
                    }

                } else {
                    if (!is_writable($subpath)) {
                        throw new FilesystemException(tr('File::executeMode(): Failed to set mode "0:mode" to specified subpath ":path", access denied', array(':mode' => decoct($mode), ':path' => $subpath)), $e);
                    }
                }

                $message = $e->getmessages();
                $message = array_shift($message);
                $message = strtolower($message);

                if (str_contains($message, 'operation not permitted')) {
                    throw new FilesystemException(tr('File::executeMode(): Failed to set mode "0:mode" to specified path ":path", operation not permitted', array(':mode' => decoct($mode), ':path' => $path)), $e);
                }

                throw $e;
            }

            $retval = $callback($path, $params, $mode);

            /*
             * Return the original mode
             */
            if ($mode) {
                if ($multi) {
                    foreach($modes as $subpath => $mode) {
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

            return $retval;

        }catch(Exception $e) {
            throw new FilesystemException(tr('File::executeMode(): Failed for path(s) ":path"', array(':path' => $path)), $e);
        }
    }



    /*
     *
     */
    public static function linkExists($file) {
        if (file_exists($file)) {
            return true;
        }

        if (is_link($file)) {
            throw new FilesystemException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exists');
        }

        throw new FilesystemException(tr('file_link_exists(): Symlink ":source" has non existing target ":target"', array(':source' => $file, ':target' => readlink($file))), 'not-exists');
    }



    /*
     * Open the specified source, read the contents, and replace $search with $replace. Write results in $target
     * $replaces should be a $search => $replace key value array, where the $search values are regex expressions
     */
    public static function searchReplace($source, $target, $replaces) {
        try {
            if (!file_exists($source)) {
                throw new FilesystemException(tr('file_search_replace(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
            }

            if (!file_exists(dirname($target))) {
                throw new FilesystemException(tr('file_search_replace(): Specified target path ":targetg" does not exist', array(':target' => $target)), 'not-exists');
            }

            if (!is_array($replaces)) {
                throw new FilesystemException(tr('file_search_replace(): Specified $replaces ":replaces" should be a search => replace array', array(':replaces' => $replaces)), 'invalid');
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
                foreach($replaces as $search => $replace) {
                    $data = preg_replace($search, $replace, $data);
                }

                fwrite($ft, $data, strlen($data));
            }

            fclose($fs);
            fclose($ft);

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_search_replace(): Failed'), $e);
        }
    }



    /*
     * Return line count for this file
     */
    public static function lineCount($source) {
        try {
            if (!file_exists($source)) {
                throw new FilesystemException(tr('file_line_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
            }

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_line_count(): Failed'), $e);
        }
    }



    /*
     * Return word count for this file
     */
    public static function wordCount($source) {
        try {
            if (!file_exists($source)) {
                throw new FilesystemException(tr('file_word_count(): Specified source file ":source" does not exist', array(':source' => $source)), 'not-exists');
            }

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_word_count(): Failed'), $e);
        }
    }



    /*
     * Scan the entire file path upward for the specified file.
     * If the specified file doesn't exist in the specified path, go one dir up,
     * all the way to root /
     */
    public static function scan($path, $file) {
        try {
            if (!file_exists($path)) {
                throw new FilesystemException(tr('file_scan(): Specified path ":path" does not exist', array(':path' => $path)), 'not-exists');
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
            throw new FilesystemException(tr('file_word_count(): Failed'), $e);
        }
    }



    /*
     * Move specified path to a backup
     */
    public static function moveToBackup($path) {
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
                file_delete($path, ROOT.'data/backups');
                return true;
            }

            rename($path, $backup_path);
            return true;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_move_to_backup(): Failed'), $e);
        }
    }



    /*
     * Update the specified file owner and group
     */
    public static function chown($file, $user = null, $group = null) {
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
                throw new FilesystemException(tr('file_chown(): Specified file ":file" does not exist', array(':file' => $file)), 'not-exists');
            }

            if (!strstr($file, ROOT)) {
                throw new FilesystemException(tr('file_chown(): Specified file ":file" is not in the projects ROOT path ":path"', array(':path' => $path, ':file' => $file)), 'invalid');
            }

            safe_exec(array('commands' => array('chown', array('sudo' => true, $user.':'.$group, $file))));

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_chown(): Failed'), $e);
        }
    }



    /*
     *
     *
     * @param string $path
     * @param string $prefix
     * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
     */
    public static function pathContainsSymlink($path, $prefix = null) {
        try {
            if (!$path) {
                throw new FilesystemException(tr('file_path_contains_symlink(): No path specified'), 'not-specified');
            }

            if (substr($path, 0, 1) === '/') {
                if ($prefix) {
                    throw new FilesystemException(tr('file_path_contains_symlink(): The specified path ":path" is absolute, which requires $prefix to be null, but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
                }

                $location = '/';

            } else {
                /*
                 * Specified $path is relative, so prefix it with $prefix
                 */
                if (substr($prefix, 0, 1) !== '/') {
                    throw new FilesystemException(tr('file_path_contains_symlink(): The specified path ":path" is relative, which requires an absolute $prefix but it is ":prefix"', array(':path' => $path, ':prefix' => $prefix)), 'invalid');
                }

                $location = Strings::endsWith($prefix, '/');
            }

            $path = Strings::endsNotWith(Strings::startsNotWith($path, '/'), '/');

            foreach(explode('/', $path) as $section) {
                $location .= $section;

                if (!file_exists($location)) {
                    throw new FilesystemException(tr('file_path_contains_symlink(): The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', array(':path' => $path, ':prefix' => $prefix, ':location' => $location)), 'not-exists');
                }

                if (is_link($location)) {
                    return true;
                }

                $location .= '/';
            }

            return false;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_path_contains_symlink(): Failed'), $e);
        }
    }



    /*
     *
     *
     * @see https://secure.php.net/manual/en/migration56.openssl.php
     * @see https://secure.php.net/manual/en/function.stream-context-create.php
     * @see https://secure.php.net/manual/en/wrappers.php
     * @see https://secure.php.net/manual/en/context.php
     * @param array $context The stream context
     * @param string $prefix
     * @return boolean True if the specified $path (optionally prefixed by $prefix) contains a symlink, false if not
     */
    public static function createStreamContext(array $context)
    {
        try {
            if (!$context) return null;

            if (!is_array($context)) {
                throw new FilesystemException(tr('File::createStreamContext(): Specified context is invalid, should be an array but is an ":type"', array(':type' => gettype($context))), 'invalid');
            }

            return stream_context_create($context);

        }catch(Exception $e) {
            throw new FilesystemException(tr('File::createStreamContext(): Failed'), $e);
        }
    }



    /*
     * Perform a "sed" action on the specified file
     *
     * @see safe_exec()
     * @version 2.4.22: Added function and documentation
     * @param params $params The parameters for sed
     * @param null mixed $params[ok_exitcodes]
     * @param null boolean $params[sudo] If set to true, the sed command will be executed using sudo
     * @param null mixed $params[function]
     * @param null mixed $params[background]
     * @return void()
     */
    public static function sed($params) {
        try {
            Arrays::ensure($params, 'ok_exitcodes,function,sudo,background,domain');

            if (empty($params['source'])) {
                throw new FilesystemException(tr('file_sed(): No source file specified'), 'not-specified');
            }

            if (empty($params['regex'])) {
                throw new FilesystemException(tr('file_sed(): No regex specified'), 'not-specified');
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

            if (!empty($sudo)) {
                $arguments['sudo'] = $sudo;
            }

            safe_exec(array('domain'       => $params['domain'],
                'background'   => $params['background'],
                'function'     => $params['function'],
                'ok_exitcodes' => $params['ok_exitcodes'],
                'commands'     => array('sed' => $arguments)));

        }catch(Exception $e) {
            throw new FilesystemException('file_sed(): Failed', $e);
        }
    }



    /*
     * Cat the output from one file to another
     *
     * @see safe_exec()
     * @version 2.4.22: Added function and documentation
     * @param params $params The parameters for sed
     * @param null mixed $params[ok_exitcodes]
     * @param null boolean $params[sudo] If set to true, the sed command will be executed using sudo
     * @param null mixed $params[function]
     * @param null mixed $params[background]
     * @return void()
     */
    public static function cat($params) {
        try {
            Arrays::ensure($params, 'ok_exitcodes,function,sudo,background,domain');

            if (empty($params['source'])) {
                throw new FilesystemException(tr('file_cat(): No source file specified'), 'not-specified');
            }

            if (empty($params['target'])) {
                throw new FilesystemException(tr('file_cat(): No target file specified'), 'not-specified');
            }

            if (!empty($sudo)) {
                $arguments['sudo'] = $sudo;
            }

            safe_exec(array('domain'       => $params['domain'],
                'background'   => $params['background'],
                'function'     => $params['function'],
                'ok_exitcodes' => $params['ok_exitcodes'],
                'commands'     => array('cat' => $arguments)));

        }catch(Exception $e) {
            throw new FilesystemException('file_cat(): Failed', $e);
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
     * @version 2.4.24: Added function and documentation
     * @param mixed $params The parameters on which to restrict the specified file or path. May also simply be a file string, in which case the default parameters apply
     * @param null mixed $params[source]
     * @param null mixed $params[target]
     * @param null mixed $params[file]
     * @param null mixed $params[path]
     * @param null list $params[restrictions] list of paths to which the specified files must be restricted. This will only be used if $restrictions is NULL
     * @param null list $restrictions list of paths to which the specified files must be restricted
     * @return void
     */
    public static function restrict($params, &$restrictions = null) {
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
                if (is_array($params) and isset($restrictions)) {
                    $restrictions = $restrictions;
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
                    $restrictions = array(ROOT.'data/tmp', ROOT.'data/cache', '/tmp');
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
                foreach($restrictions as &$restriction) {
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
                throw new FilesystemException(tr('$restrictions->apply(): The specified file or path ":path" is outside of the authorized paths ":authorized"', array(':path' => $params, ':authorized' => $restrictions)), 'access-denied', $restrictions);
            }

            /*
             * Search for default fields
             */
            $keys = array('source', 'target', 'source_path', 'source_path', 'path');

            foreach($keys as $key) {
                if (isset($params[$key])) {
                    /*
                     * All these must be tested
                     */
                    try {
                        $restrictions->apply($params[$key], $restrictions);

                    }catch(Exception $e) {
                        throw new FilesystemException(tr('$restrictions->apply(): Failed for key ":key" test', array(':key' => $key)), $e);
                    }
                }
            }

        }catch(Exception $e) {
            throw new FilesystemException('$restrictions->apply(): Failed', $e);
        }
    }




    /*
     * Locates the specifed command and returns it path
     *
     * @version 2.0.5: Added function and documentation
     * @version 2.4.16: Added $whereis support
     * @param string $command The command searched for
     * @param boolean $whereis If set to true, instead of "which", "whereis" will be used
     * @return string The path of the specified file
     */
    public static function which($command, $whereis = false) {
        try {
            $result = safe_exec(array('ok_exitcodes' => '0,1',
                'commands'     => array(($whereis ? 'whereis' : 'which'), array($command))));

            $result = array_shift($result);

            return get_null($result);

        }catch(Exception $e) {
            throw new FilesystemException('file_which(): Failed', $e);
        }
    }






    /*
     * Search / replace the specified file
     *
     * @see file_copy_tree()
     * @param array string $search The key(s) for which should be searched
     * @param array string $replace The values that will replace the specified key(s)
     * @param string $file The file that needs to have the search / replace done
     * @return string The $file
     */
    public static function replace($search, $replace, $file) {
        try {
            $data = file_get_contents($file);
            $data = str_replace($search, $replace, $file);

            file_put_contents($file, $data);
            return $file;

        }catch(Exception $e) {
            throw new FilesystemException('file_replace(): Failed', $e);
        }
    }



    /*
     * DEPRECATED FUNCTIONS
     */
    public static function chmodTree($path, $filemode, $dirmode = 0770) {
        try {
            return file_chmod($path, $filemode, $dirmode = 0770);

        }catch(Exception $e) {
            throw new FilesystemException('file_chmod_tree(): Failed', $e);
        }
    }



    /*
     * Return a single file from the specified file section
     *
     * @param string $file
     * @return string The result
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
     */
    public static function fromPart($file) {
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
                            throw new FilesystemException(tr('file_from_path(): Found multiple files for specified file part ":file"', array(':file' => $file)), 'multiple');
                        }

                        $target = $path;
                    }
                }));

            return $target;

        }catch(Exception $e) {
            throw new FilesystemException(tr('file_from_part(): Failed'), $e);
        }
    }



}