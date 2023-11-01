<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\PathException;
use Phoundation\Filesystem\Exception\PathNotDirectoryException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Interfaces\ExecuteInterface;
use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Os\Processes\Commands\Tar;
use Stringable;
use Throwable;


/**
 * Path class
 *
 * This library contains various filesystem path related functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Path extends FileBasics implements PathInterface
{
    /**
     * Temporary path (public data), if set
     *
     * @var PathInterface|null $temp_path_private
     */
    protected static ?PathInterface $temp_path_private = null;
    /**
     * Temporary path (private data), if set
     *
     * @var PathInterface|null $temp_path_public
     */
    protected static ?PathInterface $temp_path_public = null;


    /**
     * Path class constructor
     *
     * @param FileBasics|string|null $file
     * @param array|string|Restrictions|null $restrictions
     */
    public function __construct(FileBasics|string|null $file = null, array|string|Restrictions|null $restrictions = null)
    {
        parent::__construct($file, $restrictions);

        $this->file = Strings::slash($this->file);

        if ($this->real_file) {
            $this->real_file = Strings::slash($this->real_file);
        }
    }


    /**
     * Returns an Execute object to execute callbacks on each file in specified paths
     *
     * @return ExecuteInterface
     */
    public function execute(): ExecuteInterface
    {
        $this->file = Strings::slash($this->file);
        return new Execute($this->file, $this->restrictions);
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
     * @param string|null $type             This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e    If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     * @return static
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static
    {
        $this->file = Strings::slash($this->file);
        parent::checkReadable($type, $previous_e);

        if (!is_dir($this->file)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be read because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
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
        $this->file = Strings::slash($this->file);
        parent::checkWritable($type, $previous_e);

        if (!is_dir($this->file)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be written because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

        return $this;
    }


    /**
     * Ensures existence of the specified path
     *
     * @param string|null $mode octal $mode If the specified $this->path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
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
        $this->file = Strings::slash($this->file);
        Filesystem::validateFilename($this->file);

        $mode = Config::get('filesystem.mode.directories', 0750, $mode);

        if ($clear) {
            // Delete the currently existing path, so we can  be sure we have a clean path to work with
            File::new($this->file, $this->restrictions)->delete(false, $sudo);
        }

        if (!file_exists(Strings::unslash($this->file))) {
            // The complete requested path doesn't exist. Try to create it, but directory by directory so that we can
            // correct issues as we run in to them
            $dirs = explode('/', Strings::startsNotWith($this->file, '/'));
            $this->file = '';

            foreach ($dirs as $dir) {
                $this->file .= '/' . $dir;

                if (file_exists($this->file)) {
                    if (!is_dir($this->file)) {
                        // Some normal file is in the way. Delete the file, and retry
                        File::new($this->file, $this->restrictions)->delete(false, $sudo);
                        return $this->ensure($mode, $clear, $sudo);
                    }

                    continue;

                } elseif (is_link($this->file)) {
                    // This is a dead symlink, delete it
                    File::new($this->file, $this->restrictions)->delete(false, $sudo);
                }

                try {
                    // Make sure that the parent path is writable when creating the directory
                    Path::new(dirname($this->file), $this->restrictions->getParent())->execute()
                        ->setMode(0770)
                        ->onPathOnly(function() use ($mode) {
                            mkdir($this->file, $mode);
                        });

                } catch(RestrictionsException $e) {
                    throw $e;

                } catch(Throwable $e) {
                    // It sometimes happens that the specified path was created just in between the file_exists and
                    // mkdir
                    if (!file_exists($this->file)) {
                        throw PathException::new(tr('Failed to create directory ":path"', [
                            ':path' => $this->file
                        ]), $e)->addData(['path' => $this->file]);
                    }
                }
            }

        } elseif (!is_dir($this->file)) {
            // Some other file is in the way. Delete the file, and retry.
            // Ensure that the "file" is not accidentally specified as a directory ending in a /
            File::new(Strings::endsNotWith($this->file, '/'), $this->restrictions)->delete(false, $sudo);
            return $this->ensure($mode, $clear, $sudo);
        }

        return $this;
    }


    /**
     * Returns true if the object paths are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        $this->file = Strings::slash($this->file);
        $this->exists();

        if (!is_dir($this->file)) {
            $this->checkReadable();

            throw new PathNotDirectoryException(tr('The specified path ":path" is not a directory', [
                ':path' => $this->file
            ]));
        }

        // Start reading the directory.
        $handle = opendir($this->file);

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
     * Delete the path, and each parent directory until a non-empty directory is encountered
     *
     * @param string|null $until_path If specified as a path, the method will stop deleting upwards when the specified
     *                                path is encountered as well. If specified as true, the method will continue
     *                                deleting until either Restrictions stops it, or a non empty directory has been
     *                                encountered
     * @param bool $sudo
     * @param bool $use_run_file
     * @return void
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more information
     *
     */
    public function clear(?string $until_path = null, bool $sudo = false, bool $use_run_file = true): void
    {
        $this->file = Strings::slash($this->file);

        try {
            while ($this->file) {
                // Restrict location access
                $this->restrictions->check($this->file, true);

                if (!file_exists($this->file)) {
                    // This section does not exist, jump up to the next section above
                    $this->file = dirname($this->file);
                    continue;
                }

                if (!is_dir($this->file)) {
                    // This is a normal file, we only delete directories here!
                    throw new OutOfBoundsException(tr('Not clearing path ":path", it is not a directory', [
                        ':path' => $this->file
                    ]));
                }

                if ($until_path and ($this->file === $until_path)){
                    // We've cleaned until the requested directory, so we're good!
                    break;
                }

                if (!Path::new($this->file, $this->restrictions)->isEmpty()) {
                    // Do not remove anything more, there is contents here!
                    break;
                }

                // Remove this entry and continue;
                try {
                    $this->delete(false, $sudo, use_run_file: $use_run_file);

                }catch(Exception $e) {
                    // The directory WAS empty, but cannot be removed

                    // In all probability, a parallel process added a new content in this directory, so it's no longer empty.
                    // Just register the event and leave it be.
                    Log::warning(tr('Failed to remove empty pattern ":pattern" with exception ":e"', [
                        ':pattern' => $this->file,
                        ':e'       => $e
                    ]));

                    break;
                }

                // Go one entry up, check if we're still within restrictions, and continue deleting
                $this->file = dirname($this->file) . '/';
            }
        } catch (RestrictionsException) {
            // We're out of our territory, stop scanning!
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
        $this->file = Strings::slash($this->file);
        $this->restrictions->check($this->file, true);
        $this->exists();

        // Check configuration
        if (!$length) {
            $length = Config::getInteger('filesystem.target-path.size', 8);
        }

        if ($single === null) {
            $single = Config::getBoolean('filesystem.target-path.single', false);
        }

        $this->file = Strings::unslash(Path::new($this->file, $this->restrictions)->ensure()->getFile());

        if ($single) {
            // Assign path in one dir, like abcde/
            $this->file = Strings::slash($this->file) . substr(uniqid(), -$length, $length);

        } else {
            // Assign path in multiple dirs, like a/b/c/d/e/
            foreach (str_split(substr(uniqid(), -$length, $length)) as $char) {
                $this->file .= DIRECTORY_SEPARATOR . $char;
            }
        }

        // Ensure again to be sure the target directories too have been created
        return Strings::slash(Path::new($this->file, $this->restrictions)->ensure()->getFile());
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
        $this->file = Strings::slash($this->file);
        $this->restrictions->check($this->file, false);
        $this->exists();

        $return = [];
        $fh     = opendir($this->file);

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
            $file = Strings::slash($this->file) . $filename;

            // Add the file to the list. If the file is a directory, then recurse instead. Do NOT add the directory
            // itself, only files!
            if (is_dir($file) and $recursive) {
                $return = array_merge($return, Path::new($file, $this->restrictions)->listTree());

            } else {
                $return[] = $file;
            }
        }

        closedir($fh);
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
        $this->file = Strings::slash($this->file);
        $this->restrictions->check($this->file, false);
        $this->exists();

        $this->file = Arrays::getRandomValue($this->file);
        $files      = scandir($this->file);

        Arrays::unsetValue($files, '.');
        Arrays::unsetValue($files, '..');

        if (!$files) {
            throw new FilesystemException(tr('The specified path ":path" contains no files', [
                ':path' => $this->file
            ]));
        }

        return Strings::slash($this->file) . Arrays::getRandomValue($files);
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
        $this->file = Strings::slash($this->file);
        $this->restrictions->check($this->file, false);
        $this->exists();

        while (strlen($this->file) > 1) {
            $this->file = Strings::slash($this->file);

            if (file_exists($this->file . $filename)) {
                // The requested file is found! Return the path where it was found
                return $this->file;
            }

            $this->file = dirname($this->file);
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
        $this->file = Strings::slash($this->file);
        $this->restrictions->check($this->file, false);
        $this->exists();

        $return = 0;

        foreach (scandir($this->file) as $file) {
            if (($file == '.') or ($file == '..')) continue;

            if (is_dir($this->file . $file)) {
                // Recurse
                $return += Path::new($this->file . $file, $this->restrictions)->treeFileSize();

            } else {
                $return += filesize($this->file . $file);
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
        $this->file = Strings::slash($this->file);
        $this->restrictions->check($this->file, false);
        $this->exists();

        $return = 0;

        foreach (scandir($this->file) as $file) {
            if (($file == '.') or ($file == '..')) continue;

            if (is_dir($this->file . $file)) {
                $return += Path::new($this->file . $file, $this->restrictions)->treeFileCount();

            } else {
                $return++;
            }
        }

        return $return;
    }


    /**
     * Returns PHP code statistics for this path
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
            Log::action(tr('Creating non existing path ":file" with file mode ":mode"', [
                ':mode' => Strings::fromOctal($mode),
                ':file' => $this->file
            ]), 3);

            mkdir($this->file, $mode);
        }

        return $this;
    }


    /**
     * Returns a temporary path specific for this process
     *
     * @param bool $public
     * @return PathInterface
     */
    public static function getTemporaryBase(bool $public): PathInterface
    {
        if ($public) {
            // Return public temp path
            if (!static::$temp_path_public) {
                static::$temp_path_public = static::new(PATH_PUBTMP . Session::getUUID(), Restrictions::new(PATH_PUBTMP, true, 'base public temporary path'))
                    ->delete()
                    ->ensure();

                // Put lock file to avoid delete cleanup removing this session directory
                touch(static::$temp_path_public->getFile() . '.lock');
            }

            return static::$temp_path_public;
        }

        if (!static::$temp_path_private) {
            // Return private temp path
            static::$temp_path_private = static::new(PATH_TMP . Session::getUUID(), Restrictions::new(PATH_TMP, true, 'base private temporary path'))
                ->delete()
                ->ensure();

            // Put lock file to avoid delete cleanup removing this session directory
            touch(static::$temp_path_private->getFile() . '.lock');
        }

        return static::$temp_path_private;
    }


    /**
     * Removes the temporary path specific for this process
     *
     * @note Will not delete temporary paths in debug mode as these paths may be required for debugging purposes
     * @return void
     */
    public static function removeTemporary(): void
    {
        Core::ExecuteNotInTestMode(function() {
            $action = false;

            if (static::$temp_path_private) {
                File::new(static::$temp_path_private, Restrictions::new(PATH_TMP, true))->delete();
                $action = true;
            }

            if (static::$temp_path_public) {
                File::new(static::$temp_path_public, Restrictions::new(PATH_PUBTMP, true))->delete();
                $action = true;
            }

            return $action;

        }, tr('Cleaned up temporary directories ":private, :public"', [
            ':private' => Strings::from(static::$temp_path_private, PATH_ROOT),
            ':public'  => Strings::from(static::$temp_path_public, PATH_ROOT)
        ]));
    }


    /**
     * Tars this path and returns a file object for the tar file
     *
     * @return FileInterface
     */
    public function tar(): FileInterface
    {
        return File::new(Tar::new($this->restrictions)->tar($this->file), $this->restrictions);
    }


    /**
     * Returns the single one file in this path IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return FileInterface
     */
    public function getSingleFile(?string $regex = null, bool $allow_multiple = false): FileInterface
    {
        return File::new($this->file . $this->getSingle($regex, false, $allow_multiple), $this->restrictions);
    }


    /**
     * Returns the single one directory in this path IF there is only one file
     *
     * @param string|null $regex
     * @param bool $allow_multiple
     * @return PathInterface
     */
    public function getSingleDirectory(?string $regex = null, bool $allow_multiple = false): PathInterface
    {
        return Path::new($this->file . $this->getSingle($regex, true, $allow_multiple), $this->restrictions);
    }


    /**
     * Returns the single one file in this path IF there is only one file
     *
     * @param string|null $regex
     * @param bool|null $directory
     * @param bool $allow_multiple
     * @return string
     */
    protected function getSingle(?string $regex = null, ?bool $directory = null, bool $allow_multiple = false): string
    {
        $files = scandir($this->file);

        if (!$files) {
            throw new FilesystemException(tr('Cannot get single file from path ":path", scandir failed', [
                ':path' => $this->file
            ]));
        }

        // Get rid of . and ..
        array_shift($files);
        array_shift($files);

        foreach ($files as $id => $file) {
            if (is_bool($directory)) {
                // Filter on directories or non directories
                if (is_dir($this->file . $file)) {
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

        // Ensure we have only 1 file. zero is less than one and shall not be accepted, as is two, which is more than
        // one and as such not equal an the same as one and therefor shall not be accepted.
        switch (count($files)) {
            case 0:
                throw new FilesystemException(tr('Cannot return a single file, the path ":path" matches no files', [
                    ':path'  => $this->file
                ]));

            case 1:
                break;

            default:
                if (!$allow_multiple) {
                    throw new FilesystemException(tr('Cannot return a single file, the path ":path" matches ":count" files', [
                        ':path'  => $this->file,
                        ':count' => count($files)
                    ]));

                }
        }

        return array_shift($files);
    }


    /**
     * Returns the amount of available files in the current file path
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

        // Return the amount of all files in this directory
        $files = scandir($this->file);
        $count = count($files);

        // Recurse?
        if ($recursive) {
            // Recurse!
            foreach ($files as $file) {
                if (($file === '.') or ($file === '..')) {
                    // Skip crap
                    continue;
                }

                // Filename must have complete absolute path
                $file = $this->file . $file;

                if (is_dir($file)) {
                    // Count all files in this sub directory, minus the directory itself
                    $count += Filesystem::get($file, $this->restrictions)->getCount($recursive) - 1;
                }
            }
        }

        return $count;
    }


    /**
     * Returns a list of all available files in this path matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_patterns The single or multiple pattern(s) that should be matched
     * @param int $glob_flags            Flags for the internal glob() call
     * @param int $match_flags           Flags for the internal fnmatch() call
     * @return array                     The resulting file paths
     */
    public function scan(?string $file_patterns = null, int $glob_flags = GLOB_MARK, int $match_flags = FNM_PERIOD|FNM_CASEFOLD): array
    {
        $this->restrictions->check($this->file, false);

        $return = [];

        // Get path pattern part and file pattern part
        if ($file_patterns) {
            $path_pattern  = dirname($file_patterns);
            $file_patterns = basename($file_patterns);

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

            // Fix path pattern
            if ($path_pattern === '.') {
                $path_pattern  = '';

            } else {
                $path_pattern .= '/';
            }

        } else {
            // All
            $path_pattern  =  '';
            $base_pattern  =  '';
            $file_patterns = [''];
        }

        // Get files
         $glob = glob($this->file . $path_pattern . '*', $glob_flags);

        if (empty($glob)) {
            // This path pattern search had no results
            return [];
        }

        // Check file patterns
        foreach ($glob as $file) {
            foreach ($file_patterns as $file_pattern) {
                $file_pattern = $base_pattern . $file_pattern;

                $file = Strings::from($file, $this->real_file);
                $test = Strings::fromReverse(Strings::endsNotWith($file, '/'), '/');

                if ($file_pattern){
                    if (is_dir($this->file . $file)) {
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
     * Returns a list of all available files in this path matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_pattern The single or multiple pattern(s) that should be matched
     * @param int $glob_flags           Flags for the internal glob() call
     * @return array                    The resulting file paths
     */
    public function scanRegex(?string $file_pattern = null, int $glob_flags = GLOB_MARK): array
    {
        $this->restrictions->check($this->file, false);

        // Get files
        $return = [];
        $glob   = glob($this->file . '*', $glob_flags);

        if (empty($glob)) {
            // This path pattern search had no results
            return [];
        }

        // Check file patterns
        foreach ($glob as $file) {
            $file = Strings::from($file, $this->real_file);
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
}
