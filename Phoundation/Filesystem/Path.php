<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\PathNotDirectoryException;
use Phoundation\Filesystem\Exception\RestrictionsException;
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
class Path extends FileBasics
{
    /**
     * Returns an Execute object to execute callbacks on each file in specified paths
     *
     * @return Execute
     */
    public function execute(): Execute
    {
        return new Execute($this->file, $this->server);
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
        parent::checkReadable($type, $previous_e);

        if (!is_dir($this->file)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be read because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
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
        parent::checkWritable($type, $previous_e);

        if (is_dir($this->file)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be written because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->file
            ]), previous: $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

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
     * @param string|null $mode octal $mode If the specified $this->path does not exist, it will be created with this directory mode. Defaults to $_CONFIG[fs][dir_mode]
     * @param boolean $clear If set to true, and the specified path already exists, it will be deleted and then re-created
     * @return string The specified file
     */
    public function ensure(?string $mode = null, ?bool $clear = false, bool $sudo = false): string
    {
        Filesystem::validateFilename($this->file);

        $mode = Config::get('filesystem.mode.directories', 0750, $mode);

        if ($clear) {
            // Delete the currently existing path, so we can  be sure we have a clean path to work with
            File::new($this->file, $this->server)->delete(false, $sudo);
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
                        File::new($this->file, $this->server)->delete(false, $sudo);
                        return $this->ensure($mode, $clear, $sudo);
                    }

                    continue;

                } elseif (is_link($this->file)) {
                    // This is a dead symlink, delete it
                    File::new($this->file, $this->server)->delete(false, $sudo);
                }

                try {
                    // Make sure that the parent path is writable when creating the directory
                    Path::new(dirname($this->file), $this->server)->execute()
                        ->setMode(0770)
                        ->onPathOnly(function() use ($mode) {
                            mkdir($this->file, $mode);
                        });

                }catch(Exception $e) {
                    // It sometimes happens that the specified path was created just in between the file_exists and
                    // mkdir
                    if (!file_exists($this->file)) {
                        throw $e;
                    }
                }
            }

        } elseif (!is_dir($this->file)) {
            // Some other file is in the way. Delete the file, and retry.
            // Ensure that the "file" is not accidentally specified as a directory ending in a /
            File::new(Strings::endsNotWith($this->file, '/'), $this->server)->delete(false, $sudo);
            return $this->ensure($mode, $clear, $sudo);
        }

        $this->file = Strings::slash(realpath($this->file));
        return $this->file;
    }



    /**
     * Returns true if the object paths are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        foreach ($this->file as $this->file) {
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
        $this->checkRestrictions($this->file, true);

        foreach ($this->file as $this->file) {
            while ($this->file) {
                // Restrict location access
                try {
                    $this->checkRestrictions($this->file, true);
                } catch (RestrictionsException) {
                    // We're out of our territory, stop scanning!
                    break;
                }

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

                if (!Path::new($this->file, $this->server)->isEmpty()) {
                    // Do not remove anything more, there is contents here!
                    break;
                }

                // Remove this entry and continue;
                try {
                    File::new($this->file, $this->server)->delete(false, $sudo);

                }catch(Exception $e) {
                    /*
                     * The directory WAS empty, but cannot be removed
                     *
                     * In all probability, a parrallel process added a new content in this directory, so it's no longer empty.
                     * Just register the event and leave it be.
                     */
                    Log::warning(tr('Failed to remove empty pattern ":pattern" with exception ":e"', [
                        ':pattern' => $this->file,
                        ':e'       => $e
                    ]));

                    break;
                }

                // Go one entry up, check if we're still within restrictions, and continue deleting
                $this->file = dirname($this->file);
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
        $this->checkRestrictions($this->file, true);
        $this->exists();

        // Check configuration
        if (!$length) {
            $length = Config::getInteger('filesystem.target-path.size', 8);
        }

        if ($single === null) {
            $single = Config::getBoolean('filesystem.target-path.single', false);
        }

        $this->file = Strings::unslash(Path::new($this->file, $this->server)->ensure());

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
        return Strings::slash(Path::new($this->file, $this->server)->ensure());
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
        $this->checkRestrictions($this->file, false);

        $return = [];

        foreach ($this->file as $this->file) {
            $this->exists();
            $fh = opendir($this->file);

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
                    $return = array_merge($return, Path::new($file, $this->server)->listTree());

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
        $this->checkRestrictions($this->file, false);

        $this->file = Arrays::getRandomValue($this->file);
        $this->exists();

        $files = scandir($this->file);

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
        $this->checkRestrictions($this->file, false);

        foreach ($this->file as $this->file) {
            $this->exists();

            while (strlen($this->file) > 1) {
                $this->file = Strings::slash($this->file);

                if (file_exists($this->file . $filename)) {
                    // The requested file is found! Return the path where it was found
                    return $this->file;
                }

                $this->file = dirname($this->file);
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
        $this->checkRestrictions($this->file, false);

        $return = 0;

        foreach ($this->file as $this->file) {
            $this->exists();

            foreach (scandir($this->file) as $file) {
                if (($file == '.') or ($file == '..')) continue;

                if (is_dir($this->file . $file)) {
                    // Recurse
                    $return += Path::new($this->file . $file, $this->server)->treeFileSize();

                } else {
                    $return += filesize($this->file . $file);
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
        $this->checkRestrictions($this->file, false);

        $return = 0;

        $this->exists();

        foreach (scandir($this->file) as $file) {
            if (($file == '.') or ($file == '..')) continue;

            if (is_dir($this->file . $file)) {
                $return += Path::new($this->file . $file, $this->server)->treeFileCount();

            } else {
                $return++;
            }
        }

        return $return;
    }
}