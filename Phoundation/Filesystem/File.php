<?php

namespace Phoundation\Filesystem;

use Exception;
use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Date\Date;
use Phoundation\Debug\Php;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Processes\Commands;
use Phoundation\Processes\Exception\ProcessesException;
use Phoundation\Processes\Process;
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
     * File READ method
     */
    public const READ = 1;

    /**
     * File WRITE method
     */
    public const WRITE = 2;

    /**
     * The file access permissions
     *
     * @var Restrictions
     */
    protected Restrictions $restrictions;

    /**
     * The files for this File object
     *
     * @var array|null $file
     */
    protected array|null $file = null;



    /**
     * File class constructor
     *
     * @param array|string|null $file
     * @param Restrictions|array|string|null $restrictions
     */
    public function __construct(array|string|null $file = null, Restrictions|array|string|null $restrictions = null)
    {
        Filesystem::validateFilename($file);

        $this->file         = Arrays::force($file, null);
        $this->restrictions = Core::ensureRestrictions($restrictions);
    }



    /**
     * Returns a new File object with the specified restrictions
     *
     * @param array|string|null $file
     * @param Restrictions|array|string|null $restrictions
     * @return File
     */
    public static function new(array|string|null $file, Restrictions|array|string|null $restrictions = null): File
    {
        return new File($file, $restrictions);
    }



    /**
     * Sets the filesystem restrictions for this File object
     *
     * @param Restrictions|null $restrictions
     * @return void
     */
    public function setRestrictions(?Restrictions $restrictions): void
    {
        $this->restrictions = Core::ensureRestrictions($restrictions);
    }



    /**
     * Returns the filesystem restrictions for this File object
     *
     * @return Restrictions
     */
    public function getRestrictions(): Restrictions
    {
        return $this->restrictions;
    }



    /**
     * Returns the file mode for the object file
     *
     * @return int
     */
    public function mode(): int
    {
        return $this->stat()['mode'];
    }



    /**
     * Returns the stat data for the object file
     *
     * @return array
     */
    public function stat(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, false);

        try {
            $stat = stat($this->file);

            if ($stat) {
                return $stat;
            }
        } catch (Throwable $e) {
            $this->checkReadable(null, false, $e);
        }
    }



    /**
     * Append specified data string to the end of the object file
     *
     * @param string $data
     * @return void
     * @throws FilesystemException
     */
    public function appendData(string $data): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        // Make sure the file path exists
        Path::new(dirname($this->file), $this->restrictions)->ensure();

        $h = $this->open('a');
        fwrite($h, $data);
        fclose($h);
    }



    /**
     * Concatenates a list of files to a target file
     *
     * @param string|array $sources The source files
     */
    public function appendFiles(string|array $sources): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        // Ensure the target path exists
        Path::new(dirname($this->file), $this->restrictions)->ensure();

        // Open target file
        try {
            $target_h = $this->open('a', $this->file);
        } catch (Throwable $e) {
            // Failed to open the target file
            $this->checkReadable($this->file, 'target', $e);
        }

        // Open each source file
        foreach (Arrays::force($sources, null) as $source) {
            try {
                $source_h = $this->open('r', $source);
            } catch (Throwable $e) {
                // Failed to open one of the sources, get rid of the partial target file
                $this->delete();
                $this->checkReadable($source, 'source', $e);
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
    public function getUploaded(array|string $source): string
    {
        $destination = PATH_ROOT . 'data/uploads/';

        $this->checkRestrictions($source     , true);
        $this->checkRestrictions($destination, true);

        if (is_array($source)) {
            // Assume this is a PHP file upload array entry
            if (empty($source['tmp_name'])) {
                throw new FilesystemException(tr('Invalid source specified, must either be a string containing an absolute file path or a PHP $_FILES entry'));
            }

            $real = $source['name'];
            $source = $source['tmp_name'];

        } else {
            $real = basename($source);
        }

        is_file($source);
        Path::new($destination)->ensure();

        // Ensure we're not overwriting anything!
        if (file_exists($destination . $real)) {
            $real = Strings::untilReverse($real, '.') . '_' . substr(uniqid(), -8, 8) . '.' . Strings::fromReverse($real, '.');
        }

        if (!move_uploaded_file($source, $destination . $real)) {
            throw new FilesystemException(tr('Failed to move file ":source" to destination ":destination"', [
                ':source' => $source,
                ':destination' => $destination
            ]));
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
    public function assignTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return $this->moveToTarget('', $path, $extension, $singledir, $length);
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
    public function assignTargetClean(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return str_replace($extension, '', $this->moveToTarget('', $path, $extension, $singledir, $length));
    }



    /**
     * Copy object file, see file_move_to_target for implementation
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function copyToTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4): string
    {
        return $this->moveToTarget($path, $extension, $singledir, $length, true);
    }


    /**
     * Move object file (must be either file string or PHP uploaded file array) to a target and returns the target name
     *
     * IMPORTANT! Extension here is just "the rest of the filename", which may be _small.jpg, or just the extension, .jpg
     * If only an extension is desired, it is VERY important that its specified as ".jpg" and not "jpg"!!
     *
     * $pattern sets the base path for where the file should be stored
     * If $extension is false, the files original extension will be retained. If set to a value, the extension will be that value
     * If $singledir is set to false, the resulting file will be in a/b/c/d/e/, if its set to true, it will be in abcde
     * $length specifies howmany characters the subdir should have (4 will make a/b/c/d/ or abcd/)
     *
     * @param string $path
     * @param bool $extension
     * @param bool $singledir
     * @param int $length
     * @param bool $copy
     * @param string $context
     * @return string The target file
     * @throws Exception
     */
    public function moveToTarget(string $path, bool $extension = false, bool $singledir = false, int $length = 4, bool $copy = false, mixed $context = null): string
    {
        throw new UnderConstructionException();
        $this->checkRestrictions($this->file, false);
        $this->checkRestrictions($path, true);

        if (is_array($this->file)) {
            // Assume this is a PHP $_FILES array entry
            $upload     = $this->file;
            $this->file = $this->file['name'];
        }

        if (isset($upload) and $copy) {
            throw new FilesystemException(tr('Copy option has been set, but object file ":file" is an uploaded file, and uploaded files cannot be copied, only moved', [':file' => $this->file]));
        }

        $path     = Path::new($path, $this->restrictions)->ensure();
        $filename = basename($this->file);

        if (!$filename) {
            // We always MUST have a filename
            $filename = bin2hex(random_bytes(32));
        }

        // Ensure we have a local copy of the file to work with
        if ($this->file) {
            $this->file = \Phoundation\Web\Http\File::new($this->restrictions)->download($is_downloaded, $context);
        }

        if (!$extension) {
            $extension = Filesystem::getExtension($filename);
        }

        if ($length) {
            $targetpath = Strings::slash(file_create_target_path($path, $singledir, $length));

        } else {
            $targetpath = Strings::slash($path);
        }

        $target = $targetpath . strtolower(Strings::convertAccents(Strings::untilReverse($filename, '.'), '-'));

        // Check if there is a "point" already in the extension not obligatory at the start of the string
        if ($extension) {
            if (!str_contains($extension, '.')) {
                $target .= '.' . $extension;

            } else {
                $target .= $extension;
            }
        }

        // Only move file is target does not yet exist
        if (file_exists($target)) {
            if (isset($upload)) {
                // File was specified as an upload array
                return $this->moveToTarget($upload, $path, $extension, $singledir, $length, $copy);
            }

            return $this->moveToTarget($path, $extension, $singledir, $length, $copy);
        }

        // Only move if file was specified. If no file specified, then we will only return the available path
        if ($this->file) {
            if (isset($upload)) {
                // This is an uploaded file
                $this->moveToTarget($upload['tmp_name'], $target);

            } else {
                // This is a normal file
                if ($copy and !$is_downloaded) {
                    copy($this->file, $target);

                } else {
                    rename($target);
                    $this->path(dirname($this->file))->clear(false);
                }
            }
        }

        return Strings::from($target, $path);
    }



    /**
     * Ensure that the object file exists in the specified path
     *
     * @note Will log to the console in case the file was created
     * @version 2.4.16: Added documentation, improved log output
     *
     * @param null $mode If the specified $this->file does not exist, it will be created with this file mode. Defaults to $_CONFIG[fs][file_mode]
     * @param null $pattern_mode If parts of the path for the file do not exist, these will be created as well with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @return string The object file
     */
    public function ensureFile($mode = null, $pattern_mode = null): string
    {
        $mode = Config::get('filesystem.modes.defaults.file', 0640, $mode);

        $this->checkRestrictions(dirname($this->file), true);
        Path::new(dirname($this->file))->ensure($pattern_mode);

        if (!file_exists($this->file)) {
            // Create the file
            Path::new(dirname($this->file), $this->restrictions)->each()
                ->setPathMode(0770)
                ->execute(function() use ($mode) {
                    Log::warning(tr('File ":file" did not exist and was created empty to ensure system stability, but information may be missing', [
                        ':file' => $this->file
                    ]));

                    touch($this->file);

                    if ($mode) {
                        $this->chmod($mode);
                    }
                });
        }

        return $this->file;
    }



    /**
     * Return a file path for a temporary file
     *
     * @param bool|string $create    If set to false, only the file path will be returned, the temporary file will NOT
     *                               be created. If set to true, the file will be created. If set to a string, the temp
     *                               file will be created with as contents the $create string
     * @param bool $extension        If specified, use PATH_ROOT/data/tmp/$name instead of a randomly generated filename
     * @param bool $limit_to_session
     * @param string|null $path      If specified, make the temporary not in PATH_TMP but in $pattern instead
     * @return string The filename for the temp file
     *
     * @note: If the resolved temp file path already exist, it will be deleted!
     * @example
     * code
     * $result = $this->temp('This is temporary data!');
     * showdie(file_get_contents($result));
     * /code
     *
     * This would return
     * code
     * This is temporary data!
     * /code
     */
    public function temp(bool|string $create = true, bool $extension = null, bool $limit_to_session = true, ?string $path = null) : string
    {
        if (!$path) {
            $path = PATH_TMP;
        }

        // Get temp path. Path class will process filesystem restrictions, no need to redo that here
        $path = $this->path($path)->ensure();

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

        if ($extension) {
            // Temp file will have specified extension
            $name .= '.' . $extension;
        }

        $this->file = $path.$name;

        // Temp file can not exist
        if (file_exists($this->file)) {
            $this->delete();
        }

        if ($create) {
            if ($create === true) {
                touch($this->file);

            } else {
                if (!is_string($create)) {
                    throw new FilesystemException(tr('Specified $create variable is of datatype ":type" but should be either false, true, or a data string that should be written to the temp file', [
                        ':type' => gettype($create)
                    ]));
                }

                file_put_contents($create);
            }
        }

        return $this->file;
    }



    /**
     * Returns the mimetype data for the object file
     *
     * @version 2.4: Added documentation
     * @return string The mimetype data for the object file
     */
    public function mimetype(): string
    {
        static $finfo = false;

        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        // Check the object file
        if (!$this->file) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        try {
            if (!$finfo) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            }

            $mimetype = finfo_file($finfo, $this->file);
            return $mimetype;
        } catch (Exception $e) {
            // We failed to get mimetype data. Find out why and throw exception
            $this->checkReadable('', true, new FilesystemException(tr('Failed to get mimetype information for file ":file"', [
                ':file' => $this->file
            ]), previous: $e));
        }
    }



    /**
     * Returns true or false if file is ASCII or not
     *
     * @version 2.4: Added documentation
     * @return bool True if the file is a text file, false if not
     */
    public function isText(): bool
    {
        $mimetype = $this->mimetype();

        if (Strings::until($mimetype, '/') == 'text') {
            return true;
        }

        if (Strings::from($mimetype, '/') == 'xml' ) {
            return true;
        }

// TODO There is more to this
        return false;
    }



    /**
     * Return true if the specified mimetype is for a binary file or false if it is for a text file
     *
     * @version 2.5.90: Added function and documentation
     * @param string $primary        The primary mimetype section to check. If the mimetype is "text/plain", this
     *                               variable would receive "text". You can also leave $secondary empty and specify the
     *                               complete mimetype "text/plain" here, both will work
     * @param string|null $secondary The secondary mimetype section to check. If the mimetype is "text/plain", this
     *                               variable would receive "plain". If the complete mimetype is specified in $primary,
    you can leave this one empty
     * @return boolean True if the specified mimetype is for a binary file, false if it is a text file
     */
    public function isBinary(string $primary, ?string $secondary = null): bool
    {
// TODO So isText() works on a file and this works on mimetype strings? Fix this!
// TODO There is more to this
// :TODO: IMPROVE THIS! Loads of files that are not text/ are still not binary
        // Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
        if (!$secondary) {
            if (!str_contains($primary, '/')) {
                throw new FilesystemException(tr('Invalid primary mimetype data ":primary" specified. Either specify the complete mimetype in $primary, or specify the independent primary and secondary sections in $primary and $secondary', [':primary' => $primary]));
            }

            $secondary = Strings::from($primary , '/');
            $primary   = Strings::until($primary, '/');
        }

        // Check the mimetype data
        switch ($primary) {
            case 'text':
                // Plain text
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
                    case 'vnd.mozilla.xul+xml':
                        // This is all text
                        return false;
                }
        }

        // This is binary
        return true;
    }



    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @see Restrictions::check() This function uses file location restrictions
     *
     * @param boolean $clean_path If specified true, all directories above each specified pattern will be deleted as well as long as they are empty. This way, no empty directories will be left laying around
     * @param boolean $sudo If specified true, the rm command will be executed using sudo
     * @return void
     */
    public function delete(bool $clean_path = true, bool $sudo = false): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        // Delete all specified patterns
        foreach ($this->file as $file) {
            // Execute the rm command
            Process::new('rm')
                ->setSudo($sudo)
                ->setTimeout(10)
                ->addArgument($file)
                ->addArgument('-rf')
                ->executeReturnArray();

            // If specified to do so, clear the path upwards from the specified pattern
            if ($clean_path) {
                Path::new(dirname($file))->clear($sudo);
            }
        }
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
    public function copyTree(string $source, string $destination, array $search = null, array $replace = null, string|array $extensions = null, mixed $mode = true, bool $novalidate = false): string
    {
        throw new UnderConstructionException('$this->copyTree() is under construction');

        // Check filesystem restrictions
        $this->checkRestrictions($source, false);
        $this->checkRestrictions($destination, true);

        // Choose between copy filemode (mode is null), set filemode ($mode is a string or octal number) or preset
        // filemode (take from config, TRUE)
        if (!is_bool($mode) and !is_null($mode)) {
            if (is_string($mode)) {
                $mode = intval($mode, 8);
            }

            $filemode = $mode;
        }

        if (substr($destination, 0, 1) != '/') {
            // This is not an absolute path
            $destination = PWD.$destination;
        }

        // Validations
        if (!$novalidate) {
            // Prepare search / replace
            if (!$search) {
                // We can only replace if we search
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
                    throw new FilesystemException(tr('The search parameters count ":search" and replace parameters count ":replace" do not match', [
                        ':search'  => count($search),
                        ':replace' => count($replace)
                    ]));
                }
            }

            if (!file_exists($source)) {
                throw new FilesystemException(tr('Specified source ":source" does not exist', [
                    ':source' => $source
                ]));
            }

            $destination = Strings::unslash($destination);

            if (!file_exists($destination)) {
// :TODO: Check if dirname($this->file) here is correct? It somehow does not make sense
                if (!file_exists(dirname($destination))) {
                    throw new FilesystemException(tr('Specified destination ":destination" does not exist', [
                        ':destination' => dirname($destination)
                    ]));
                }

                if (!is_dir(dirname($destination))) {
                    throw new FilesystemException(tr('Specified destination ":destination" is not a directory', [
                        ':destination' => dirname($destination)
                    ]));
                }

                if (is_dir($source)) {
                    // We are copying a directory, destination dir does not yet exist
                    mkdir($destination);

                } else {
                    // We are copying just one file
                }

            } else {
                // Destination already exists,
                if (is_dir($source)) {
                    if (!is_dir($destination)) {
                        throw new FilesystemException(tr('Cannot copy source directory ":source" into destination file ":destination"', [
                            ':source'      => $source,
                            ':destination' => $destination
                        ]));
                    }

                } else {
                    // Source is a file
                    if (!is_dir($destination)) {
                        // Remove destination file since it would be overwritten
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
                    // Only replacing down
                    continue;
                }

                if (is_null($mode)) {
                    $filemode = Config::get('filesystem.modes.defaults.directories', 0640, $mode);

                } elseif (is_link($source . $file)) {
                    // No file permissions for symlinks
                    $filemode = false;

                } else {
                    $filemode = fileperms($source . $file);
                }

                if (is_dir($source . $file)) {
                    // Recurse
                    if (file_exists($destination . $file)) {
                        // Destination path already exists. This -by the way- means that the destination tree was not
                        // clean
                        if (!is_dir($destination . $file)) {
                            // Were overwriting here!
                            file_delete($destination . $file, $restrictions);
                        }
                    }

                    $this->path($destination . $file)->ensure($filemode);
                }

                file_copy_tree($source . $this->file, $destination . $file, $search, $replace, $extensions, $mode, true);
            }

        } else {
            if (is_link($source)) {
                $link = readlink($source);

                if (str_starts_with($link, '/')) {
                    // Absolute link, this is ok
                    $reallink = $link;

                } else {
                    // Relative link, get the absolute path
                    $reallink = Strings::slash(dirname($source)).$link;
                }

                if (!file_exists($reallink)) {
                    // This symlink points to no file, its dead
                    Log::warning(tr('Encountered dead symlink ":source", copying anyway...', [
                        ':source' => $source
                    ]));
                }

                // This is a symlink. Just create a new symlink that points to the same path
                return symlink($link, $destination);
            }

            // Determine mode
            if ($mode === null) {
                $filemode = $_CONFIG['file']['file_mode'];

            } elseif ($mode === true) {
                $filemode = fileperms($source);
            }

            // Check if the file requires search / replace
            if (!$search) {
                // No search specified, just copy tree
                $doreplace = false;

            } elseif (!$extensions) {
                // No extensions specified, search / replace all files in tree
                $doreplace = true;

            } else {
                // Check extension if we should search / replace
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
                // Just a simple filecopy will suffice
                copy($source, $destination);

            } else {
                $data = file_get_contents($source);

                foreach ($search as $id => $svalue) {
                    if ((substr($svalue, 0, 1 == '/')) and (substr($svalue, -1, 1 == '/'))) {
                        // Do a regex search / replace
                        $data = preg_replace($svalue, $replace[$id], $data);

                    } else {
                        // Do a normal search / replace
                        $data = str_replace($svalue, $replace[$id], $data);
                    }
                }

                // Done, now write to the target file!
                file_put_contents($destination, $data);
            }

            if ($mode) {
                // Update file mode
                try {
                    chmod($destination, $filemode);

                }catch(Exception $e) {
                    throw new FilesystemException(tr('Failed to set filemode for ":destination"', array(':destination' => $destination)), $e);
                }
            }
        }

        return $destination;
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
    public function rename(string $source, string $destination, $search, $rename): void
    {
        throw new UnderConstructionException('$this->rename() is under construction');

        // Check filesystem restrictions
        $this->checkRestrictions($source, false);
        $this->checkRestrictions($destination, true);

        // Validations
        if (!file_exists($source)) {
            throw new FilesystemException(tr('Specified source ":source" does not exist', [
                ':source' => $source
            ]), 'exists');
        }

        if (!file_exists($destination)) {
            throw new FilesystemException(tr('Specified destination ":destination" does not exist', [
                ':destination' => $destination
            ]));
        }

        if (!is_dir($destination)) {
            throw new FilesystemException(tr('Specified destination ":destination" is not a directory', [
                ':destination' => $destination
            ]));
        }

        if (is_file($source)) {
            // Rename just one file

        } else {
            // Rename all files in this directory

        }
    }



    /**
     * Change file mode, optionally recursively
     *
     * @param string|int $mode   The mode to apply to the specified path (and all files below if recursive is specified)
     * @param boolean $recursive If set to true, apply specified mode to the specified path and all files below by
     *                           recursion
     * @param bool $sudo
     * @param ?Restrictions $restrictions
     * @return void
     * @see $this->safePattern()
     * @version 2.6.30: Added function and documentation
     * @version 2.7.60: Fixed safe file pattern issues
     */
    public function chmod(string|int $mode, bool $recursive = false, bool $sudo = false, ?Restrictions $restrictions = null): void
    {
        if (!($mode)) {
            throw new OutOfBoundsException(tr('No file mode specified'));
        }

        if (!$this->file) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        foreach ($this->file as $pattern) {
            Process::new('chmod')
                ->setSudo($sudo)
                ->addArgument($mode)
                ->addArgument($pattern)
                ->addArgument($recursive ? '-R' :null)
                ->executeReturnArray();
        }
    }



    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
     * @return string
     */
    public function switchMode(string|int $mode): string
    {
        $old_mode = $this->mode();
        $this->chmod($mode);

        return $old_mode;
    }



    /**
     * Update the object file owner and group
     *
     * @param string|null $user
     * @param string|null $group
     * @param bool $recursive
     * @param bool $sudo
     * @return void
     */
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false, bool $sudo = false): void
    {
        if (!$user) {
            $user = posix_getpwuid(posix_getuid());
            $user = $user['name'];
        }

        if (!$group) {
            $group = posix_getpwuid(posix_getuid());
            $group = $group['name'];
        }

        if (!$this->file) {
            throw new OutOfBoundsException(tr('No path specified'));
        }

        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        foreach ($this->file as $pattern) {
            Process::new('chown')
                ->setSudo($sudo)
                ->addArgument($user.':' . $group)
                ->addArgument($pattern)
                ->addArgument($recursive ? '-R' :null)
                ->executeReturnArray();
        }
    }



    /**
     * Return a system path for the specified type
     *
     * @param string $type
     * @param string $path
     * @return string
     */
    public function systemPath(string $type, string $path = ''): string
    {
        switch ($type) {
            case 'img':
                // no-break
            case 'image':
                return '/pub/img/' . $path;

            case 'css':
                // no-break
            case 'style':
                return '/pub/css/' . $path;

            default:
                throw new OutOfBoundsException(tr('Unknown type ":type" specified', [':type' => $type]));
        }
    }



    /**
     * If the object file is an HTTP, HTTPS, or FTP URL, then get it locally as a temp file
     *
     * @param string $url
     * @param bool $is_downloaded
     * @param ?array $context
     * @return string
     */
    public function getLocal(string $url, bool &$is_downloaded = false, ?array $context = null): string
    {
        try {
            $context = $this->createStreamContext($context);
            $url     = trim($url);

            if (str_contains($url, 'http:') and str_contains($url, 'https:') and str_contains($url, 'ftp:')) {
                if (!file_exists($url)) {
                    throw new FileNotExistException(tr('Specified file ":file" does not exist', [':file' => $url]));
                }

                if (is_uploaded_file($url)) {
                    $tmp  = file_get_uploaded($url);
                    $this->file = $this->temp($url, null, false);

                    rename($tmp, $this->file);
                    return $this->file;
                }

                return $url;
            }

            // First download the file to a temporary location
            $this->file          = str_replace(array('://', '/'), '-', $url);
            $this->file          = $this->temp();
            $is_downloaded = true;

            $this->path()->ensure(dirname($this->file));
            file_put_contents(file_get_contents($url, false, $context));

            return $this->file;

        }catch(Exception $e) {
            $message = $e->getMessage();
            $message = strtolower($message);

            if (str_contains($message, '404 not found')) {
                throw new FilesystemException(tr('URL ":file" does not exist', [':file' => $url]));
            }

            if (str_contains($message, '400 bad request')) {
                throw new FilesystemException(tr('URL ":file" is invalid', [':file' => $url]));
            }

            throw new FilesystemException(tr('Failed for file ":file"', [':file' => $url]), $e);
        }
    }



    /*
     * Copy a file with progress notification
     *
     * @example:
     * function stream_notification_callback($notification_code, $severity, $message, $message_code, $bytes_transferred, $bytes_max) {
     *     if ($notification_code == STREAM_Notification_PROGRESS) {
     *         // save $bytes_transferred and $bytes_max to file or database
     *     }
     * }
     *
     * file_copy_progress($source, $target, 'stream_notification_callback');
     */
    public function copyProgress($source, $target, $callback) {
        $c = stream_context_create();
        stream_context_set_params($c, ['notification' => $callback]);
        copy($source, $target, $c);
    }



    /**
     * Returns the specified octal filemode into a text readable filemode (rwxrwxrwx)
     *
     * @param int $mode
     * @return string
     */
    public function readableFileMode(int $mode): string
    {
        $return = '';
        $mode   = substr(decoct($mode), -3, 3);

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
    }



    /**
     * This is an fopen() wrapper with some built-in error handling
     *
     * @param string $mode
     * @param string|null $file
     * @return mixed
     */
    public function open(#[ExpectedValues(values:['r', 'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'ce+'])] string $mode, ?string $file = null): mixed
    {
        if ($file === null) {
            $file = $this->file;
        }

        // Check filesystem restrictions
        $this->checkRestrictions($file, true);

        $handle = fopen($file, $mode);

        if (!$handle) {
            // Check if the mode is valid and if the file can be opened for the requested mode
            $method = match ($mode) {
                'r' => FILE::READ,
                'r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+', 'ce+' => FILE::WRITE,
                default => throw new FilesystemException(tr('Could not open file ":file"', [':file' => $this->file])),
            };

            // Mode is valid, check if file is accessible.
            switch ($method) {
                case FILE::READ:
                    $this->checkReadable();
                    break;

                case FILE::WRITE:
                    $this->checkWritable();
                    break;
            }

            throw new FilesystemException(tr('Failed to open file ":file"', [':file' => $this->file]));
        }

        return $handle;
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
     * @param string|null $type          This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param bool $no_directories       If true, the object file cannot be a directory
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return void
     */
    #[NoReturn] public function checkReadable(?string $type = null, bool $no_directories = false, ?Throwable $previous_e = null) : void
    {
        if (!file_exists($this->file)) {
            if (!file_exists(dirname($this->file))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be read because the directory ":path" does not exist', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $this->file,
                    ':path' => dirname($this->file)
                ]), previous: $previous_e);
            }

            throw new FilesystemException(tr('The:type file ":file" cannot be read because it does not exist', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if (!is_readable($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($no_directories and is_dir($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be read because it is a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;

//            // This method was called because a read action failed, throw an exception for it
//            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [
//                ':type' => ($type ? '' : ' ' . $type),
//                ':file' => $this->file
//            ]), previous: $previous_e);
        }
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
     * @param bool $no_directories       If true, the object file cannot be a directory
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return void
     */
    #[NoReturn] public function checkWritable(?string $type = null, bool $no_directories = false, ?Throwable $previous_e = null) : void
    {
        if (!file_exists($this->file)) {
            if (!file_exists(dirname($this->file))) {
                // The file doesn't exist and neither does its parent directory
                throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist and neither does the parent path ":path"', [
                    ':type' => ($type ? '' : ' ' . $type),
                    ':file' => $this->file,
                    ':path' => dirname($this->file)
                ]), previous: $previous_e);
            }

            throw new FilesystemException(tr('The:type file ":file" cannot be written because it does not exist', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if (!is_readable($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be written', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($no_directories and is_dir($this->file)) {
            throw new FilesystemException(tr('The:type file ":file" cannot be written because it is a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }
    }



    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $file_mode
     * @param int|null $directory_mode
     * @param string $type
     * @return string
     */
    public function ensureWritable(?int $file_mode = null, ?int $directory_mode = null, string $type = 'file'): string
    {
        // If the object file exists and is writable, then we're done.
        if (is_writable($this->file)) {
            return false;
        }

        // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...

        // Get configuration. We need file and directory default modes
        $this->file_mode      = Config::get('filesystem.mode.default.file'     , 0640, $this->file_mode);
        $directory_mode = Config::get('filesystem.mode.default.directory', 0750, $directory_mode);

        if (file_exists($this->file)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The object file ":file" (Realpath ":path") is not writable. Attempting to apply default file mode ":file_mode"', [':file' => $this->file, ':path' => realpath(), ':file_mode' => $this->file_mode]));
                $this->chmod('u+w');
                return $this->file;
            } catch (ProcessesException $e) {
                throw new FileNotWritableException(tr(
                    'The object file ":file" (Realpath ":path") is not writable, and could not be made writable',
                    [
                        ':file' => $this->file,
                        ':path' => realpath()
                    ]
                ));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
        $this->path()->ensure(dirname($this->file));
        Log::warning(tr('The object file ":file" (Realpath ":path") does not exist. Attempting to create it with file mode ":filemode"', [':filemode' => Strings::fromOctal(_mode), ':file' => $this->file, ':path' => realpath()]));

        switch ($type) {
            case 'file':
                touch($this->file);
                chmod(_mode);
                break;

            case 'directory':
                mkdir();
                chmod($directory_mode);
                break;

            default:
                throw new OutOfBoundsException(tr('The specified type ":type" is invalid, it should be one of "file" or "directory"', [':type' => $type]));
        }

        return realpath();
    }



    /**
     * Returns array with all permission information about the object file.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return string
     */
    public function type(): string
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        $perms     = fileperms();
        $socket    = (($perms & 0xC000) == 0xC000);
        $symlink   = (($perms & 0xA000) == 0xA000);
        $regular   = (($perms & 0x8000) == 0x8000);
        $bdevice   = (($perms & 0x6000) == 0x6000);
        $cdevice   = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe  = (($perms & 0x1000) == 0x1000);

        if ($socket) {
            // This file is a socket
            return 'socket';

        } elseif ($symlink) {
            // This file is a symbolic link
            return 'symbolic link';

        } elseif ($regular) {
            // This file is a regular file
            return 'regular file';

        } elseif ($bdevice) {
            // This file is a block device
            return 'block device';

        } elseif ($directory) {
            // This file is a directory
            return 'directory';

        } elseif ($cdevice) {
            // This file is a character device
            return 'character device';

        } elseif ($fifopipe) {
            // This file is a FIFO pipe
            return 'fifo pipe';
        }

        // This file is an unknown type
        return 'unknown';
    }


    
    /**
     * Returns array with all permission information about the object file.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileMode(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        $perms  = fileperms();
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
            'r' =>  ($perms & 0x0100),
            'w' =>  ($perms & 0x0080),
            'x' => (($perms & 0x0040) and !($perms & 0x0800)),
            's' => (($perms & 0x0040) and  ($perms & 0x0800)),
            'S' =>  ($perms & 0x0800)
        ];

        $return['group'] = [
            'r' =>  ($perms & 0x0020),
            'w' =>  ($perms & 0x0010),
            'x' => (($perms & 0x0008) and !($perms & 0x0400)),
            's' => (($perms & 0x0008) and  ($perms & 0x0400)),
            'S' =>  ($perms & 0x0400)
        ];

        $return['other'] = [
            'r' =>  ($perms & 0x0004),
            'w' =>  ($perms & 0x0002),
            'x' => (($perms & 0x0001) and !($perms & 0x0200)),
            't' => (($perms & 0x0001) and  ($perms & 0x0200)),
            'T' =>  ($perms & 0x0200)
        ];

        // Owner
        $return['mode'] .= (($perms & 0x0100) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0080) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0040) ?
            (($perms & 0x0800) ? 's' : 'x' ) :
            (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $return['mode'] .= (($perms & 0x0020) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0010) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0008) ?
            (($perms & 0x0400) ? 's' : 'x' ) :
            (($perms & 0x0400) ? 'S' : '-'));

        // Other
        $return['mode'] .= (($perms & 0x0004) ? 'r' : '-');
        $return['mode'] .= (($perms & 0x0002) ? 'w' : '-');
        $return['mode'] .= (($perms & 0x0001) ?
            (($perms & 0x0200) ? 't' : 'x' ) :
            (($perms & 0x0200) ? 'T' : '-'));

        return $return;
    }



    /**
     * Execute the specified callback on all files in the specified tree
     *
     * @param $params
     * @return int
     */
    public function treeExecute($params): int
    {
        throw new UnderConstructionException();
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        Arrays::ensure($params);
        Arrays::default($params, 'ignore_exceptions', true);
        Arrays::default($params, 'path'             , null);
        Arrays::default($params, 'filters'          , null);
        Arrays::default($params, 'follow_symlinks'  , false);
        Arrays::default($params, 'follow_hidden'    , false);
        Arrays::default($params, 'recursive'        , false);
        Arrays::default($params, 'execute_directory', false);
        Arrays::default($params, 'params'           , null);

        // Validate data
        if (empty($params['callback'])) {
            throw new FilesystemException(tr('No callback function specified'));
        }

        if (!is_callable($params['callback'])) {
            throw new FilesystemException(tr('Specified callback is not a function'));
        }

        if (!$pattern) {
            throw new FilesystemException(tr('No path specified'));
        }

        if (!str_starts_with($pattern, '/')) {
            throw new FilesystemException(tr('No absolute path specified'));
        }

        if (!file_exists($pattern)) {
            throw new FilesystemException(tr('Specified path ":path" does not exist', [':path' => $pattern]));
        }

        // Follow hidden files?
        if ((str_starts_with(basename($pattern), '.')) and !$params['follow_hidden']) {
            if (Debug::enabled() and PLATFORM_CLI) {
                Log::warning(tr('Skipping file ":file" because its hidden', [':file' => $pattern]));
            }

            return 0;
        }

        // Filter this path?
        foreach (Arrays::force($params['filters']) as $filter) {
            if (preg_match($filter, $pattern)) {
                if (Debug::enabled() and PLATFORM_CLI) {
                    Log::warning(tr('Skipping file ":file" because of filter ":filter"', [
                        ':file'   => $pattern,
                        ':filter' => $filter
                    ]));
                }

                return 0;
            }
        }

        $count = 0;
        $type  = file_type($pattern);

        switch ($type) {
            case 'regular file':
                $params['callback']($pattern);
                $count++;

                Log::success(tr('Executed code for file ":file"', [':file' => $pattern]));
                break;

            case 'symlink':
                if ($params['follow_symlinks']) {
                    $pattern = readlink($pattern);
                    $count += file_tree_execute($params);
                }

                break;

            case 'directory':
                $h    = opendir($pattern);
                $pattern = Strings::slash($pattern);

                while (( = readdir($h)) !== false) {
                    try {
                        if (( == '.') or ( == '..')) continue;

                        if ((str_starts_with(basename($this->file), '.')) and !$params['follow_hidden']) {
                            if (Debug::enabled() and PLATFORM_CLI) {
                                Log::warning(tr('Skipping file ":file" because its hidden', [
                                    ':file' => $this->file
                                ]));
                            }

                            continue;
                        }

                        $this->file = $pattern.$this->file;

                        if (!file_exists($this->file)) {
                            throw new FilesystemException(tr('Specified path ":path" does not exist', [
                                ':path' => $this->file
                            ]));
                        }

                        $type = file_type();

                        switch ($type) {
                            case 'link':
                                if (!$params['follow_symlinks']) {
                                    continue 2;
                                }

                                $this->file = readlink();

                            // We got the target file, but we don't know what it is. Restart the process recursively
                            // to process this file

                            // no-break

                            case 'directory':
                                // no-break
                            case 'regular file':
                                if (($type != 'directory') or $params['execute_directory']) {
                                    // Filter this path?
                                    $skip = false;

                                    foreach (Arrays::force($params['filters']) as $filter) {
                                        if (preg_match($filter, $this->file)) {
                                            if (Debug::enabled() and PLATFORM_CLI) {
                                                Log::warning(tr('Skipping file ":file" because of filter ":filter"', [
                                                    ':file' => $pattern,
                                                    ':filter' => $filter
                                                ]));
                                            }

                                            $skip = true;
                                        }
                                    }

                                    if (!$skip) {
                                        $result = $params['callback']($type, $params['params']);
                                        $count++;

                                        if ($result === false) {
                                            // When the callback returned boolean false, cancel all other files
                                            Log::warning(tr('callback returned FALSE for file ":file", skipping rest of directory contents!', [
                                                ':file' => $this->file
                                            ]));

                                            goto end;
                                        }

                                        Log::success(tr('Executed code for file ":file"', [':file' => $this->file]));
                                    }
                                }

                                if (($type == 'directory') and $recursive) {
                                    $pattern = $this->file;
                                    $count         += file_tree_execute($params);
                                }

                                break;

                            default:
                                // Skip this unsupported file type
                                if (Debug::enabled() and PLATFORM_CLI) {
                                    Log::success(tr('Skipping file ":file" with unsupported file type ":type"', [
                                        ':file' => $this->file,
                                        ':type' => $type
                                    ]));
                                }
                        }

                    }catch(Exception $e) {
                        if (!$params['ignore_exceptions']) {
                            throw $e;
                        }

                        if ($e->getCode() === 'not-exists') {
                            Log::warning(tr('Skipping file ":file", it does not exist (in case of a symlink, it may be that the target does not exist)', [
                                ':file' => $this->file
                            ]));

                        } else {
                            Log::error($e);
                        }
                    }
                }

                end:
                closedir($h);

                break;

            default:
                // Skip this unsupported file type
                if (Debug::enabled() and PLATFORM_CLI) {
                    Log::warning(tr('Skipping file ":file" with unsupported file type ":type"', [
                        ':file' => $this->file,
                        ':type' => $pattern
                    ]));
                }
        }

        return $count;
    }



    /**
     * Returns a Php object
     *
     * @param array|string|null $paths
     * @return Php
     */
    public function php(array|string|null $paths = null): Php
    {
        return new Php($this, $paths);
    }



//    /**
//     * Execute the specified callback after setting the specified mode on the specified path. Once the callback has
//     * finished, the path will have its original file mode applied again
//     *
//     * @see
//     * @note If the specified path has an asterix (*) in front of it, ALL subdirectories will be updated with the
//     *       specified mode, and each will have their original file mode restored after
//     * @param string|array $pattern The path that will have its mode updated. When * is added in front of the path, ALL
//     *                           subdirectories will be updated with the new mode as well, and placed back with their
//     *                           old modes after the command has executed
//     * @param string|int $mode   The mode to which the specified directory should be set during execution
//     * @param callable $callback The function to be executed after the file mode of the specified path has been updated
//     * @return mixed             The result from the callback function
//     */
//    public function executeMode(string|array $pattern, string|int $mode, callable $callback, array $params = null): mixed
//    {
//        // Apply to all directories below?
//        if ($pattern[0] === '*') {
//            $pattern  = substr($pattern, 1);
//            $multi = true;
//
//        } else {
//            $multi = false;
//        }
//
//        if (!file_exists($pattern)) {
//            throw new FilesystemException(tr('Specified path ":path" does not exist', [
//                ':path' => $pattern
//            ]));
//        }
//
//        if (!is_string($callback) and !is_callable($callback)) {
//            throw new FilesystemException(tr('Specified callback ":callback" is invalid, it should be a string or a callable function', [
//                ':callback' => $callback
//            ]));
//        }
//
//        // Set the requested mode
//        try {
//            if (is_dir($pattern) and $multi) {
//                $this->file = Cli::find([
//                    'type'  => 'd',
//                    'start' => $pattern
//                ]);
//
//                foreach ($this->file as $subpath) {
//                    $modes[$subpath] = fileperms($subpath);
//                    chmod($subpath, $mode);
//                }
//
//            } else {
//                if ($mode) {
//                    $original_mode = fileperms($pattern);
//                    chmod($pattern, $mode);
//                }
//            }
//
//        }catch(Exception $e) {
//            if (empty($subpath)) {
//                if (!is_writable($pattern)) {
//                    throw new FilesystemException(tr('Failed to set mode "0:mode" to specified path ":path", access denied', [
//                        ':mode' => decoct($mode),
//                        ':path' => $pattern
//                    ]), $e);
//                }
//
//            } else {
//                if (!is_writable($subpath)) {
//                    throw new FilesystemException(tr('Failed to set mode "0:mode" to specified subpath ":path", access denied', [
//                        ':mode' => decoct($mode),
//                        ':path' => $subpath
//                    ]), $e);
//                }
//            }
//
//            $message = $e->getmessages();
//            $message = array_shift($message);
//            $message = strtolower($message);
//
//            if (str_contains($message, 'operation not permitted')) {
//                throw new FilesystemException(tr('Failed to set mode "0:mode" to specified path ":path", operation not permitted', [
//                    ':mode' => decoct($mode),
//                    ':path' => $pattern
//                ]), $e);
//            }
//
//            throw $e;
//        }
//
//        $return = $callback($pattern, $params, $mode);
//
//        // Return the original mode
//        if ($mode) {
//            if ($multi) {
//                foreach ($modes as $subpath => $mode) {
//                    // Path may have been deleted by the callback (for example, a file_delete() call may have
//                    // cleaned up the path) so ensure the path still exists
//                    if (file_exists($subpath)) {
//                        chmod($subpath, $mode);
//                    }
//                }
//
//            } else {
//                // Path may have been deleted by the callback (for example, a file_delete() call may have cleaned up
//                // the path) so ensure the path still exists
//                if (file_exists($pattern)) {
//                    chmod($pattern, $original_mode);
//                }
//            }
//        }
//
//        return $return;
//    }


    /**
     * Returns if the link target exists or not
     *
     * @return bool
     */
    public function linkTargetExists(): bool
    {
        throw new UnderConstructionException();
        if (file_exists($this->file)) {
            return false;
        }

        if (is_link()) {
            throw new FilesystemException(tr('Symlink ":source" has non existing target ":target"', [
                'source' => $this->file,
                ':target' => readlink()
            ]));
        }

        throw new FilesystemException(tr('Symlink ":source" has non existing target ":target"', [
            'source' => $this->file,
            ':target' => readlink()
        ]));
    }



    /**
     * Open the specified source, read the contents, and replace $search with $replace. Write results in $target
     * $replaces should be a $search => $replace key value array, where the $search values are regex expressions
     *
     * @param string $source
     * @param string $target
     * @param array $replaces
     * @return void
     */
    public function searchReplace(string $source, string $target, array $replaces): void
    {
        // Check filesystem restrictions
        $this->checkRestrictions($source, false);
        $this->checkRestrictions($target, true);

        if (!file_exists($source)) {
            throw new FilesystemException(tr('Specified source file ":source" does not exist', [
                ':source' => $source
            ]));
        }

        if (!file_exists(dirname($target))) {
            throw new FilesystemException(tr('Specified target path ":target" does not exist', [
                ':target' => $target
            ]));
        }

        $fs       = fopen($source, 'r');
        $ft       = fopen($target, 'w');
        $position = 0;
        $length   = 8192;
        $this->filesize = filesize($source);

        while ($position < $this->filesize) {
            $data      = fread($fs, $length);
            $position += $length;
            fseek($fs, $position);

            // Execute search / replaces
            foreach ($replaces as $search => $replace) {
                $data = preg_replace($search, $replace, $data);
            }

            fwrite($ft, $data, strlen($data));
        }

        fclose($fs);
        fclose($ft);
    }



    /**
     * Return line count for the specified text file
     *
     * @param string $source
     * @return int
     */
    public function lineCount(string $source): int
    {
        throw new UnderConstructionException();
        $this->isText($source);
    }



    /**
     * Return word count for the specified text file
     *
     * @param string $source
     * @return int
     */
    public function wordCount(string $source): int
    {
        throw new UnderConstructionException();
        $this->isText($source);
    }



    /**
     * Move specified path to a backup
     *
     * @param string $path
     * @param string $name
     * @return bool
     */
    public function moveToBackup(string $path, string $name): bool
    {
        throw new UnderConstructionException();
        // Check filesystem restrictions
        $this->checkRestrictions($path, false);

        if (!file_exists($path)) {
            Log::warning(tr('Cannot move the specified path ":path" to backup, it does not exist', [
                ':path' => $path
            ]));

            return false;
        }

        $backup_path = $path . '~' . Date::convert(null, 'Ymd-His');

        //
        if (file_exists($backup_path)) {
            // Backup already exists as well, script run twice in under a second. Delete the current one as the backup
            // was generated less than a second ago
            $this->delete($path, PATH_ROOT.'data/backups/' . $name . '/');
            return true;
        }

        rename($path, $backup_path);
        return true;
    }



    /**
     * ???
     *
     * @param string $path
     * @param string|null $prefix
     * @return boolean True if the specified $pattern (optionally prefixed by $prefix) contains a symlink, false if not
     */
    public function pathContainsSymlink(string $path, ?string $prefix = null): bool
    {
        if (!$path) {
            throw new FilesystemException(tr('No path specified'));
        }

        if (str_starts_with($path, '/')) {
            if ($prefix) {
                throw new FilesystemException(tr('The specified path ":path" is absolute, which requires $prefix to be null, but it is ":prefix"', [
                    ':path'   => $path,
                    ':prefix' => $prefix
                ]));
            }

            $location = '/';

        } else {
            // Specified $pattern is relative, so prefix it with $prefix
            if (!str_starts_with($prefix, '/')) {
                throw new FilesystemException(tr('The specified path ":path" is relative, which requires an absolute $prefix but it is ":prefix"', [
                    ':path'   => $path,
                    ':prefix' => $prefix
                ]));
            }

            $location = Strings::endsWith($prefix, '/');
        }

        $path = Strings::endsNotWith(Strings::startsNotWith($path, '/'), '/');

        // Check filesystem restrictions
        $this->checkRestrictions($path, false);

        foreach (explode('/', $path) as $section) {
            $location .= $section;

            if (!file_exists($location)) {
                throw new FilesystemException(tr('The specified path ":path" with prefix ":prefix" leads to ":location" which does not exist', [
                    ':path'     => $path,
                    ':prefix'   => $prefix,
                    ':location' => $location
                ]));
            }

            if (is_link($location)) {
                return true;
            }

            $location .= '/';
        }

        return false;
    }



    /**
     * ???
     *
     * @see https://secure.php.net/manual/en/migration56.openssl.php
     * @see https://secure.php.net/manual/en/function.stream-context-create.php
     * @see https://secure.php.net/manual/en/wrappers.php
     * @see https://secure.php.net/manual/en/context.php
     *
     * @param array $context
     * @return resource|null
     */
    public function createStreamContext(array $context)
    {
        if (!$context) return null;

        return stream_context_create($context);
    }



    /**
     * Perform a "sed" action on the object file
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
    public function sed($params)
    {
        throw new UnderConstructionException();
        Arrays::ensure($params, 'ok_exitcodes,function,sudo,background,domain');

        if (empty($params['source'])) {
            throw new FilesystemException(tr('file_sed(): No source file specified'));
        }

        if (empty($params['regex'])) {
            throw new FilesystemException(tr('file_sed(): No regex specified'));
        }

        if (empty($params['target'])) {
            $arguments[] = 'i';
            $arguments[] = $params['regex'];
            $arguments[] = $params['source'];

        } else {
            $arguments[] = $params['regex'];
            $arguments[] = $params['source'];
            $arguments['redirect'] = '> ' . $params['target'];
        }

        if (!empty($sudo)) {
            $arguments['sudo'] = $sudo;
        }

        safe_exec(array('domain'       => $params['domain'],
            'background'   => $params['background'],
            'function'     => $params['function'],
            'ok_exitcodes' => $params['ok_exitcodes'],
            'commands'     => array('sed' => $arguments)));
    }



    /**
     * Cat the output from one file to another
     *
     * @see safe_exec()
     * @version 2.4.22: Added function and documentation
     * @param params $params The parameters for sed
     * @param null mixed $params[ok_exitcodes]
     * @param null boolean $params[sudo] If set to true, the sed command will be executed using sudo
     * @param null mixed $params[function]
     * @param null mixed $params[background]
     * @return void
     */
    public function cat($params) {
        throw new UnderConstructionException();
        // Check filesystem restrictions
        $this->checkRestrictions($path, false);

        Arrays::ensure($params, 'ok_exitcodes,function,sudo,background,domain');

        if (empty($params['source'])) {
            throw new FilesystemException(tr('file_cat(): No source file specified'));
        }

        if (empty($params['target'])) {
            throw new FilesystemException(tr('file_cat(): No target file specified'));
        }

        if (!empty($sudo)) {
            $arguments['sudo'] = $sudo;
        }

        safe_exec(array('domain'       => $params['domain'],
            'background'   => $params['background'],
            'function'     => $params['function'],
            'ok_exitcodes' => $params['ok_exitcodes'],
            'commands'     => array('cat' => $arguments)));
    }



    /**
     * Locates the specifed command and returns it path
     *
     * @param string $command
     * @return string The path of the object file
     */
    public function which(string $command): string
    {
        return Commands::local()->which($command);
    }



    /**
     * Search / replace the object file
     *
     * @see file_copy_tree()
     * @param string $this->file The file that needs to have the search / replace done
     * @param array $replace The list of keys that will be replaced by values
     * @return string The $this->file
     */
    public function replace(array $replace): string
    {
        // Check filesystem restrictions
        $this->checkRestrictions($path, true);

        $data = file_get_contents();
        $data = str_replace(array_keys($replace), $replace, $data);

        file_put_contents($data);
        return $this->file;
    }



    /**
     * Filter out the lines that contain the specified filters
     *
     * @note Only supports line of up to 8KB which should be WAY more than enough, but still important to know
     * @param string|array $filters
     * @param int|null $until_line
     * @return array
     */
    public function grep(string|array $filters, ?int $until_line = null): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, false);

        $return = [];

        // Validate filters
        foreach (Arrays::force($filters, null) as $filter) {
            if (!is_scalar($filter)) {
                throw new OutOfBoundsException(tr('The filter ":filter" is invalid, only string filters are allowed', [
                    ':filter' => $filter
                ]));
            }

            // Libraries the return array
            $return[$filter] = [];
        }

        // Open the file and start scanning each line
        $count  = 0;

        foreach ($this->file as $file) {
            $handle = $this->open($this->file, 'r');

            while (($line = fgets($handle, 8096)) !== false) {
                foreach ($filters as $filter) {
                    if (str_contains($line, $filter)) {
                        $return[$file][$filter][] = $line;
                    }
                }

                if ($until_line and (++$count >= $until_line)) {
                    // We're done, get out
                    break;
                }
            }
        }

        fclose($handle);
        return $return;
    }



    /**
     * Returns true if the object file is a PHP file
     *
     * @return bool
     */
    public function isPhp(): bool
    {
        if (str_ends_with($this->file, '.php')) {
            if ($this->isText()) {
                return true;
            }
        }

        return false;
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
        $this->checkRestrictions($path, $write);
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
}