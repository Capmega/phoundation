<?php

/**
 * Class PhoDirectoryCore
 *
 * This class represents a single directory and contains various methods to manipulate directories.
 *
 * It can rename, copy, traverse, mount, and much more
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Filesystem\Traits\TraitDataRestrictions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhoException;
use Phoundation\Exception\PhpException;
use Phoundation\Filesystem\Exception\DirectoryException;
use Phoundation\Filesystem\Exception\DirectoryNotMountedException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Exception\FilesystemInvalidPattern;
use Phoundation\Filesystem\Exception\PathNotDirectoryException;
use Phoundation\Filesystem\Exception\RestrictionsException;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\Interfaces\PhoDuplicatesInterface;
use Phoundation\Filesystem\Interfaces\PhoExecuteInterface;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoFilesInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\Mounts\PhoMounts;
use Phoundation\Os\Processes\Commands\Find;
use Phoundation\Os\Processes\Commands\Interfaces\FindInterface;
use Phoundation\Os\Processes\Commands\Tar;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Numbers;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


class PhoDirectoryCore extends PhoPathCore implements PhoDirectoryInterface
{
    use TraitDataRestrictions {
        setRestrictionsObject as protected __setRestrictions;
    }


    /**
     * Temporary process  directory (public data), if set
     *
     * @var PhoDirectoryInterface|null $process_temporary_private
     */
    protected static ?PhoDirectoryInterface $process_temporary_private = null;

    /**
     * Temporary process directory (private data), if set
     *
     * @var PhoDirectoryInterface|null $process_temporary_public
     */
    protected static ?PhoDirectoryInterface $process_temporary_public = null;

    /**
     * Temporary session directory (public data), if set
     *
     * @var PhoDirectoryInterface|null $session_temporary_private
     */
    protected static ?PhoDirectoryInterface $session_temporary_private = null;

    /**
     * Temporary session directory (private data), if set
     *
     * @var PhoDirectoryInterface|null $session_temporary_public
     */
    protected static ?PhoDirectoryInterface $session_temporary_public = null;


    /**
     * Returns a temporary directory specific for this process that will be removed once the process terminates
     *
     * The temporary directory returned will always be the same within one process, if per
     *
     * @param bool $public
     *
     * @return PhoDirectoryInterface
     */
    public static function getProcessTemporaryPath(bool $public = false): PhoDirectoryInterface
    {
        if ($public) {
            if (empty(static::$process_temporary_public)) {
                static::$process_temporary_public = static::getTemporaryPath(Core::getLocalId(), $public);
            }

            return static::$process_temporary_public;
        }

        if (empty(static::$process_temporary_private)) {
            static::$process_temporary_private = static::getTemporaryPath(Core::getLocalId(), $public);
        }

        return static::$process_temporary_private;
    }


    /**
     * Returns a temporary directory specific for this session that will be removed once the session terminates
     *
     * The temporary directory returned will always be the same within one session
     *
     * @param bool $public
     *
     * @return PhoDirectoryInterface
     */
    public static function getSessionTemporaryPath(bool $public = false): PhoDirectoryInterface
    {
        if ($public) {
            if (empty(static::$session_temporary_public)) {
                static::$session_temporary_public = static::getTemporaryPath(Session::getUUID(), $public);
            }

            return static::$session_temporary_public;
        }

        if (empty(static::$session_temporary_private)) {
            static::$session_temporary_private = static::getTemporaryPath(Session::getUUID(), $public);
        }

        return static::$session_temporary_private;
    }


    /**
     * Returns a temporary directory specific for this session that will be removed once the session terminates
     *
     * The temporary directory returned will always be the same within one session
     *
     * @param string $identifier
     * @param bool   $public
     *
     * @return PhoDirectoryInterface
     */
    protected static function getTemporaryPath(string $identifier, bool $public = false): PhoDirectoryInterface
    {
        // Initialize private temp directory and return
        $path = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP) . $identifier;
        $path = PhoDirectory::new($path, PhoRestrictions::newWritableObject($path))
                            ->delete()
                            ->ensure();

        // Put lock file to avoid delete directory auto cleanup removing this temporary directory
        $path->addFile('.lock')->touch();

        return $path;
    }


    /**
     * Removes the temporary directory specific for this process
     *
     * @note Will not delete temporary directories in debug mode as these directories may be required for debugging
     *       purposes
     * @return void
     */
    public static function removeTemporary(): void
    {
        Core::ExecuteIfNotInTestMode(function () {
            $action = false;

            if (static::$process_temporary_private) {
                PhoFile::new(static::$process_temporary_private, PhoRestrictions::new(DIRECTORY_TMP, true))
                      ->delete();

                $action = true;
            }

            if (static::$process_temporary_public) {
                PhoFile::new(static::$process_temporary_public, PhoRestrictions::new(DIRECTORY_PUBTMP, true))
                      ->delete();

                $action = true;
            }

            if (static::$session_temporary_public) {
                PhoFile::new(static::$session_temporary_public, PhoRestrictions::new(DIRECTORY_TMP, true))
                      ->delete();

                $action = true;
            }

            if (static::$session_temporary_private) {
                PhoFile::new(static::$session_temporary_private, PhoRestrictions::new(DIRECTORY_PUBTMP, true))
                      ->delete();

                $action = true;
            }

            return $action;

        }, tr('Cleaned up temporary directories: private ":process_private", ":session_private" and public ":process_public", ":session_public"', [
            ':process_private' => not_empty(Strings::from(static::$process_temporary_private, DIRECTORY_ROOT), '-'),
            ':process_public'  => not_empty(Strings::from(static::$process_temporary_public , DIRECTORY_ROOT), '-'),
            ':session_private' => not_empty(Strings::from(static::$session_temporary_private, DIRECTORY_ROOT), '-'),
            ':session_public'  => not_empty(Strings::from(static::$session_temporary_public , DIRECTORY_ROOT), '-'),
        ]));
    }


    /**
     * Return a system directory for the specified type
     *
     * @param string $type
     * @param string $directory
     *
     * @return string
     */
    public static function getSystem(string $type, string $directory = ''): string
    {
        switch ($type) {
            case 'img':
                // no break

            case 'image':
                return '/pub/img/' . $directory;

            case 'css':
                // no break

            case 'style':
                return '/pub/css/' . $directory;

            default:
                throw new OutOfBoundsException(tr('Unknown system directory type ":type" specified', [
                    ':type' => $type
                ]));
        }
    }


    /**
     * Make this (relative) path an absolute path
     *
     * @param Stringable|string|bool|null $absolute_prefix
     * @param bool                        $must_exist
     *
     * @return static
     */
    public function makeAbsolute(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = true): static
    {
        parent::makeAbsolute($absolute_prefix, $must_exist);

        if ($must_exist) {
            if (!$this->isDirectory()) {
                throw new PathNotDirectoryException(tr('The absolute path ":path" exists but is not a directory', [
                    ':path' => $this->source,
                ]));
            }
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
     * @param string|null    $type       This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     *
     * @return static
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null): static
    {
        $this->source = Strings::slash($this->source);
        parent::checkWritable($type, $previous_e);

        if (!is_dir($this->source)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be written because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->source,
            ]), $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

        return $this;
    }


    /**
     * Delete the directory, and each parent directory until a non-empty directory is encountered
     *
     * @param string|null $until_directory If specified, as a directory, the method will stop deleting upwards when the
     *                                     specified directory is encountered as well. If specified, as true, the method
     *                                     will continue deleting until either FsRestrictions stops it, or a non empty
     *                                     directory has been encountered
     * @param bool        $sudo
     * @param bool        $use_run_file
     *
     * @return void
     * @see Restrict::restrict() This function uses file location restrictions, see Restrict::restrict() for more
     *      information
     *
     */
    public function clearDirectory(?string $until_directory = null, bool $sudo = false, bool $use_run_file = true): void
    {
        $this->source = Strings::slash($this->source);

        while ($this->source) {
            // Restrict location access
            if ($this->o_restrictions->isRestricted($this->source, true)) {
                // We're out of our territory, stop scanning!
                break;
            }

            if (!file_exists($this->source)) {
                // This section does not exist, jump up to the next section above
                $this->source = dirname($this->source);
                continue;
            }

            if (!is_dir($this->source)) {
                // This is a normal file, we only delete directories here!
                throw new OutOfBoundsException(tr('Not clearing directory ":directory", it is not a directory', [
                    ':directory' => $this->source,
                ]));
            }

            if ($until_directory and ($this->source === $until_directory)) {
                // We've cleaned until the requested directory, so we're good!
                break;
            }

            if (!PhoDirectory::new($this->source, $this->o_restrictions)->isEmpty()) {
                // Do not remove anything more, there is contents here!
                break;
            }

            // Remove this entry and continue;
            try {
                $this->delete(false, $sudo, use_run_file: $use_run_file);
            } catch (PhoException $e) {
                // The directory WAS empty, but cannot be removed
                // In all probability, a parallel process added a new content in this directory, so it's no longer empty.
                // Just register the event and leave it be.
                Log::warning(ts('Failed to remove empty pattern ":pattern" with exception ":e"', [
                    ':pattern' => $this->source,
                    ':e'       => $e,
                ]));
                break;
            }

            // Go one entry up, check if we're still within restrictions, and continue deleting
            $this->source = dirname($this->source) . '/';
        }
    }


    /**
     * Returns true if the object directories are all empty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        $this->source = Strings::slash($this->source);
        $this->exists();

        if (!is_dir($this->source)) {
            $this->checkReadable();

            throw new PathNotDirectoryException(tr('The specified directory ":directory" is not a directory', [
                ':directory' => $this->source,
            ]));
        }

        // Start reading the directory.
        $handle = opendir($this->source);

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
     * Check if the object file exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null    $type          This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e    If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     *
     * @return static
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static
    {
        $this->source = Strings::slash($this->source);
        parent::checkReadable($type, $previous_e);

        if (!is_dir($this->source)) {
            throw new FilesystemException(tr('The:type directory ":file" cannot be read because it is not a directory', [
                ':type' => ($type ? '' : ' ' . $type),
                ':file' => $this->source,
            ]), $previous_e);
        }

        if ($previous_e) {
            throw $previous_e;
        }

        return $this;
    }


    /**
     * Creates a random directory in specified base directory (If it does not exist yet), and returns that directory
     *
     * @param bool $single
     * @param int  $length
     *
     * @return string
     */
    public function createTarget(?bool $single = null, int $length = 0): string
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);
        $this->o_restrictions->check($this->source, true);

        $this->exists();

        // Check configuration
        if (!$length) {
            $length = config()->getInteger('filesystem.target-directory.size', 8);
        }

        if ($single === null) {
            $single = config()->getBoolean('filesystem.target-directory.single', false);
        }

        $this->source = Strings::unslash(PhoDirectory::new($this->source, $this->o_restrictions)
                                                     ->ensure()
                                                     ->getSource());

        if ($single) {
            // Assign directory in one dir, like abcde/
            $this->source = Strings::slash($this->source) . substr(uniqid(), -$length, $length);
        } else {
            // Assign directory in multiple dirs, like a/b/c/d/e/
            foreach (str_split(substr(uniqid(), -$length, $length)) as $char) {
                $this->source .= DIRECTORY_SEPARATOR . $char;
            }
        }

        // Ensure again to be sure the target directories too have been created
        return Strings::slash(PhoDirectory::new($this->source, $this->o_restrictions)
                                          ->ensure()
                                          ->getSource());
    }


    /**
     * Returns the path
     *
     * @param PhoPathInterface|string|null $from
     * @param bool                         $from_required
     * @param bool                         $remove_terminating_slash
     *
     * @return string
     */
    public function getSource(PhoPathInterface|string|null $from = null, bool $from_required = false, bool $remove_terminating_slash = false): string
    {
        $path = parent::getSource($from, $from_required);

        if ($remove_terminating_slash) {
            if ($path === '/') {
                // Root path is just what it is, it is a slash, don't remove it!
                return '/';
            }

            return Strings::ensureEndsNotWith($path, '/');
        }

        return $path;
    }


    /**
     * Ensures the existence of the specified directory
     *
     * @param string|int|null             $mode            octal $mode If the specified $this->directory does not exist,
     *                                                     it will be created with this directory mode. Defaults to
     *                                                     configuration path filesystem.directories.mode
     * @param Stringable|string|bool|null $absolute_prefix
     * @param boolean                     $clear           If set to true, and the specified directory already exists,
     *                                                     it will be deleted and then re-created
     * @param bool                        $sudo
     *
     * @return static
     * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright © 2022 Sven Olaf Oostenbrink
     * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @package   file
     * @version   2.4.16: Added documentation
     */
    public function ensure(string|int|null $mode = null, Stringable|string|bool|null $absolute_prefix = null, ?bool $clear = false, bool $sudo = false): static
    {
        $this->checkRestrictions(true, absolute_prefix: $absolute_prefix);

        if ($clear) {
            // Delete the currently existing directory, so we can  be sure we have a clean directory to work with
            PhoFile::new($this->source, $this->o_restrictions)->delete(false, $sudo);
        }

        if (!file_exists(Strings::unslash($this->source))) {
            // The complete requested directory doesn't exist. Try to create it, but directory by directory so that we can
            // correct issues as we run in to them
            $dirs   = explode('/', Strings::ensureBeginsNotWith(static::realPath($this->source, $absolute_prefix), '/'));
            $count  = count($dirs);
            $source = '';

            foreach ($dirs as $id => $dir) {
                $source .= '/' . $dir;

                if (file_exists($source)) {
                    if (!is_dir($source)) {
                        // Some normal file is in the way. Move the file out of the way, and retry
                        PhoFile::new($source, $this->o_restrictions)->backup(move: true);
                        return $this->ensure(config()->get('filesystem.mode.directories', $mode ?? 0750), $clear, $sudo);
                    }

                    continue;

                } elseif (is_link($source)) {
                    // This is a dead symlink, delete it
                    PhoFile::new($source, $this->o_restrictions)->delete(false, $sudo);
                }

                try {
                    // Make sure that the parent directory is writable when creating the directory
                    // Since we're modifying the item $id of $count, be sure to get matching restrictions
                    $mode = config()->get('filesystem.mode.directories', $mode ?? 0750);

                    PhoDirectory::new(dirname($source), $this->o_restrictions->getParent($count - $id)->makeWritable())
                                ->execute()
                                    ->setMode(0770)
                                    ->onDirectoryOnly(function () use ($mode, $source) {
                                        mkdir($source, $mode);
                                    });

                } catch (RestrictionsException $e) {
                    throw $e;

                } catch (Throwable $e) {
                    // It sometimes happens that the specified directory was created just in between the file_exists and
                    // mkdir
                    if (!file_exists($source)) {
                        throw DirectoryException::new(tr('Failed to create directory ":directory"', [
                            ':directory' => $source,
                        ]), $e)->addData(
                            ['directory' => $source]
                        );
                    }

                    // We're okay, the directory already exists
                }
            }

        } elseif (!is_dir($this->source)) {
            // Some other file is in the way. Delete the file, and retry.
            // Ensure that the "file" is not accidentally specified as a directory ending in a /
            PhoFile::new(Strings::ensureEndsNotWith($this->source, '/'), $this->o_restrictions)
                   ->delete(false, $sudo);

            return $this->ensure(config()->get('filesystem.mode.directories', $mode ?? 0750), $clear, $sudo);
        }

        return $this;
    }


    /**
     * Returns an Execute object to execute callbacks on each file in specified directories
     *
     * @return PhoExecuteInterface
     */
    public function execute(): PhoExecuteInterface
    {
        $this->source = Strings::slash($this->source);
        return new PhoExecute($this->source, $this->o_restrictions);
    }


    /**
     * Return all files in this directory
     *
     * @return PhoFilesInterface The files
     * @todo Merge this with PhoDirectoryCore::scan()
     */
    public function list(): PhoFilesInterface
    {
        $return = [];
        $list   = Arrays::removeMatchingValues(scandir($this->source), [
            '.',
            '..',
        ]);

        foreach ($list as $value) {
            $value = $this->source . $value;
            $return[$value] = $value;
        }

        return new PhoFiles($this, $return, $this->o_restrictions);
    }


    /**
     * Return all files in a directory that match the specified pattern with optional recursion.
     *
     * @param array|string|null $filters   One or multiple regex filters
     * @param boolean           $recursive If set to true, return all files below the specified directory, including in
     *                                     subdirectories
     *
     * @return array The matched files
     */
    public function listTree(array|string|null $filters = null, bool $recursive = true): array
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);

        $this->o_restrictions->check($this->source, false);
        $this->exists();

        $return = [];
        $fh     = opendir($this->source);

        // Go over all files
        while (($filename = readdir($fh)) !== false) {
            // Loop through the files, skipping "." and ".." and recursing if necessary
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

            // Get the complete file directory
            $file = Strings::slash($this->source) . $filename;

            // Add the file to the list. If the file is a directory, then recurse instead. Do NOT add the directory
            // itself, only files!
            if (is_dir($file) and $recursive) {
                $return = array_merge($return, PhoDirectory::new($file, $this->o_restrictions)->listTree());

            } else {
                $return[] = $file;
            }
        }

        closedir($fh);

        return $return;
    }


    /**
     * Pick and return a random file name from the specified directory
     *
     * @note This function reads all files into memory, do NOT use with huge directory (> 10000 files) listings!
     *
     * @return string A random file from a random directory from the object directories
     */
    public function random(): string
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);

        $this->o_restrictions->check($this->source, false);
        $this->exists();

        $this->source = Arrays::getRandomValue($this->source);
        $files        = scandir($this->source);

        Arrays::unsetValue($files, '.');
        Arrays::unsetValue($files, '..');

        if (!$files) {
            throw new FilesystemException(tr('The specified directory ":directory" contains no files', [
                ':directory' => $this->source,
            ]));
        }

        return Strings::slash($this->source) . Arrays::getRandomValue($files);
    }


    /**
     * Scan the entire object directory STRING upward for the specified file.
     *
     * If the object file doesn't exist in the specified directory, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     *
     * @return string|null
     */
    public function scanUpwardsForFile(string $filename): ?string
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);
        $this->o_restrictions->check($this->source, false);
        $this->exists();

        while (strlen($this->source) > 1) {
            $this->source = Strings::slash($this->source);

            if (file_exists($this->source . $filename)) {
                // The requested file is found! Return the directory where it was found
                return $this->source;
            }

            $this->source = dirname($this->source);
        }

        return null;
    }


    /**
     * Returns the total size in bytes of the tree under the specified directory
     *
     * @return int The number of bytes this tree takes
     */
    public function treeFileSize(): int
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);
        $this->o_restrictions->check($this->source, false);
        $this->exists();

        $return = 0;

        foreach (scandir($this->source) as $file) {
            if (($file == '.') or ($file == '..')) {
                continue;
            }

            try {
                if (is_dir($this->source . $file)) {
                    // Recurse
                    $return += PhoDirectory::new($this->source . $file, $this->o_restrictions)
                                           ->treeFileSize();
                } else {
                    $return += filesize($this->source . $file);
                }

            } catch (PhpException) {
                Log::warning(ts('Ignoring file size for path ":path", it does not exist (path is likely a dead symlink)', [
                    ':path' => $this->source . $file,
                ]), 2);
            }
        }

        return $return;
    }


    /**
     * Returns the number of files under the object directory (directories not included in count)
     *
     * @return int The number of files
     */
    public function treeFileCount(): int
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);
        $this->o_restrictions->check($this->source, false);
        $this->exists();

        $return = 0;

        foreach (scandir($this->source) as $file) {
            if (($file == '.') or ($file == '..')) {
                continue;
            }

            try {
                if (is_dir($this->source . $file)) {
                        $return += PhoDirectory::new($this->source . $file, $this->o_restrictions)
                                               ->treeFileCount();
                } else {
                    $return++;
                }

            } catch (PhpException) {
                Log::warning(ts('Ignoring file count for directory ":path", it does not exist (path is likely a dead symlink)', [
                    ':path' => $this->source . $file,
                ]), 2);
            }
        }

        return $return;
    }


    /**
     * Returns PHP code statistics for this directory
     *
     * @param bool $recurse
     *
     * @return array
     */
    public function getPhpStatistics(bool $recurse = false): array
    {
        $return = [
            'files_statistics' => [],
            'total_statistics' => [],
            'file_types'       => [
                'css'     => 0,
                'ini'     => 0,
                'js'      => 0,
                'html'    => 0,
                'php'     => 0,
                'xml'     => 0,
                'yaml'    => 0,
                'unknown' => 0,
            ],
            'file_extensions'  => [
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
                'unknown' => 0,
            ],
        ];

        $this->execute()
             ->setRecurse($recurse)
             ->setWhitelistExtensions(array_keys($return['file_extensions']))
             ->onFiles(function (string $path) use (&$return) {
                try {
                    $file      = PhoFile::new($path, $this->o_restrictions);
                    $extension = $file->getExtension();

                    // Add file type and extension statistics
                    switch ($extension) {
                        case 'css':
                            // no break

                        case 'scss':
                            $return['file_types']['css']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'ini':
                            $return['file_types']['ini']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'js':
                            // no break

                        case 'json':
                            $return['file_types']['js']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'html':
                            // no break

                        case 'htm':
                            $return['file_types']['html']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'php':
                            // no break

                        case 'phps':
                            // no break

                        case 'phtml':
                            $return['file_types']['php']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'xml':
                            $return['file_types']['xml']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        case 'yaml':
                            // no break
                        case 'yml':
                            $return['file_types']['yaml']++;
                            $return['file_extensions'][$extension]++;
                            break;

                        default:
                            $return['file_extensions']['unknown']++;
                    }

                    // Add file statistics
                    $return['files_statistics'][$path] = $file->getPhpStatistics();
                    $return['total_statistics']        = Arrays::addValues(
                        $return['total_statistics'],
                        $return['files_statistics'][$path]
                    );

                } catch (FilesystemException $e) {
                    Log::warning(ts('Ignoring file ":file" due to exception ":e"', [
                        ':file' => $path,
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
     *
     * @return static
     */
    public function ensureWritable(?int $mode = null): static
    {
        // Get configuration. We need file and directory default modes
        $mode = config()->get('filesystem.mode.default.directory', $mode ?? 0750);

        if (!$this->ensureFileWritable($mode)) {
            Log::action(ts('Creating non existing directory ":file" with file mode ":mode"', [
                ':mode' => Strings::fromOctal($mode),
                ':file' => $this->source,
            ]), 3);

            mkdir($this->source, $mode);
        }

        return $this;
    }


    /**
     * Tars this directory and returns a file object for the tar file
     *
     * @param PhoFileInterface|null $target
     * @param bool                  $compression
     * @param int                   $timeout
     *
     * @return PhoFileInterface
     */
    public function tar(?PhoFileInterface $target = null, bool $compression = true, int $timeout = 600): PhoFileInterface
    {
        return Tar::new()->tar($this, $target, $compression);
    }


    /**
     * Returns the single one file in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool        $allow_multiple
     *
     * @return PhoFileInterface
     */
    public function getSingleFile(?string $regex = null, bool $allow_multiple = false): PhoFileInterface
    {
        return PhoFile::new($this->source . $this->getSingle($regex, false, $allow_multiple), $this->o_restrictions);
    }


    /**
     * Returns the single one file in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool|null   $directory
     * @param bool        $allow_multiple
     *
     * @return string
     */
    protected function getSingle(?string $regex = null, ?bool $directory = null, bool $allow_multiple = false): string
    {
        $files = scandir($this->source);

        if (!$files) {
            throw new FilesystemException(tr('Cannot get single file from directory ":directory", scandir failed', [
                ':directory' => $this->source,
            ]));
        }

        // Get rid of "." and ".."
        array_shift($files);
        array_shift($files);

        foreach ($files as $id => $file) {
            if (is_bool($directory)) {
                // Filter on directories or non directories
                if (is_dir($this->source . $file)) {
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

        // Ensure we have only 1 file. Zero is less than one and shall not be accepted, as is two, which is more than
        // one and as such not equal or the same as one, and therefore shall not be accepted.
        switch (count($files)) {
            case 0:
                throw new FilesystemException(tr('Cannot return a single file, the directory ":directory" matches no files', [
                    ':directory' => $this->source,
                ]));

            case 1:
                break;

            default:
                if (!$allow_multiple) {
                    throw new FilesystemException(tr('Cannot return a single file, the directory ":directory" matches ":count" files', [
                        ':directory' => $this->source,
                        ':count'     => count($files),
                    ]));
                }
        }

        return array_shift($files);
    }


    /**
     * Returns the single one directory in this directory IF there is only one file
     *
     * @param string|null $regex
     * @param bool        $allow_multiple
     *
     * @return PhoDirectoryInterface
     */
    public function getSingleDirectory(?string $regex = null, bool $allow_multiple = false): PhoDirectoryInterface
    {
        return PhoDirectory::new($this->source . $this->getSingle($regex, true, $allow_multiple), $this->o_restrictions);
    }


    /**
     * Returns the number of available files in the current file directory
     *
     * @param bool $recursive
     *
     * @return int
     */
    public function getCount(bool $recursive = true): int
    {
        if ($this instanceof PhoFileInterface) {
            if ($this->exists()) {
                // This is a single file!
                return 1;
            }

            return 0;
        }

        // Return the number of all files in this directory
        $files = scandir($this->source);
        $count = count($files);

        // Recurse?
        if ($recursive) {
            // Recurse!
            foreach ($files as $file) {
                if (($file === '.') or ($file === '..')) {
                    // Skip crap
                    continue;
                }

                // Filename must have complete absolute directory
                $file = $this->source . $file;

                if (is_dir($file)) {
                    // Count all files in this subdirectory, minus the directory itself
                    $count += static::new($file, $this->o_restrictions)
                                    ->getCount($recursive) - 1;
                }
            }
        }

        return $count;
    }


    /**
     * Checks if the  specified path matches the specified patterns
     *
     * @param string                         $path
     * @param IteratorInterface|array|string $patterns
     *
     * @return bool
     */
    protected function pathMatchesPatterns(string $path, IteratorInterface|array|string $patterns): bool
    {
        foreach (Arrays::force($patterns, null) as $pattern) {
            try {
                if (preg_match($pattern, $path)) {
                    return true;
                }

            } catch (Throwable $e) {
                throw FilesystemInvalidPattern::new(tr('Specified pattern ":pattern" from patterns list ":patterns" is invalid', [
                    ':patterns' => Strings::force($patterns, ', '),
                    ':pattern'  => $pattern,
                ]), $e)->setData([
                    'patterns' => Strings::force($patterns, ', '),
                    'pattern'  => $pattern,
                ]);
            }
        }

        return false;
    }


    /**
     * Returns a list of all available files in this directory matching the specified (multiple) regex pattern(s)
     *
     * @param Stringable|string|null         $path               The path to extend this directory with
     * @param IteratorInterface|array|string $file_patterns      The regex pattern(s) to match files
     * @param IteratorInterface|array|string $directory_patterns The regex pattern(s) to match directories
     * @param int                            $glob_flags         Flags for the internal glob() call
     *
     * @return PhoFilesInterface                                 The resulting directory files
     */
    public function scan(Stringable|string|null $path = null, IteratorInterface|array|string $file_patterns = '/.*/', IteratorInterface|array|string $directory_patterns = '/.*/', int $glob_flags = GLOB_MARK): PhoFilesInterface
    {
        $this->o_restrictions->check($this->source, false);

        $result = glob(Strings::ensureEndsWith($this->getRealPath(true), '/') . Strings::ensureBeginsNotWith($path, '/') . '*', $glob_flags);
        $return = [];

        // Apply file and directory patterns on each result
        if ($result) {
            foreach ($result as $path) {
                if (is_dir($path)) {
                    if ($this->pathMatchesPatterns($path, $directory_patterns)) {
                        $return[$path] = $path;
                    }

                } elseif ($this->pathMatchesPatterns($path, $file_patterns)) {
                    $return[$path] = $path;
                }
            }
        }

        return new PhoFiles($this, $return, $this->o_restrictions);
    }


    /**
     * @inheritDoc
     */
    public function getRealPath(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false, bool $resolve_basename = false): string
    {
        $path = parent::getRealPath($absolute_prefix, $must_exist, $resolve_basename);

        return Strings::slash($path);
    }


    /**
     * @inheritDoc
     */
    public function getReal(Stringable|string|bool|null $absolute_prefix = null, bool $must_exist = false): PhoDirectoryInterface
    {
        return PhoDirectory::new($this->getRealPath($absolute_prefix, $must_exist), $this->o_restrictions);
    }


    /**
     * Returns a list of all available files in this directory matching the specified (multiple) pattern(s)
     *
     * @param string|null $file_pattern The single or multiple pattern(s) that should be matched
     * @param int         $glob_flags   Flags for the internal glob() call
     *
     * @return PhoFilesInterface         The resulting directory files
     */
    public function scanRegex(?string $file_pattern = null, int $glob_flags = GLOB_MARK): PhoFilesInterface
    {
        $this->o_restrictions->check($this->source, false);

        // Get files
        $return = [];
        $glob   = glob($this->source . '*', $glob_flags);

        if ($glob) {
            // Check file patterns
            foreach ($glob as $file) {
                $file = Strings::from($file, $this->source);
                $test = Strings::fromReverse(Strings::ensureEndsNotWith($file, '/'), '/');

                if ($file_pattern) {
                    if (!preg_match($file_pattern, $test)) {
                        // This file doesn't match the test pattern
                        continue;
                    }
                }

                // Add the file for the found match and continue to the next file
                $return[$file] = $file;
                break;
            }
        }

        return new PhoFiles($this, $return, $this->o_restrictions);
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param array|Stringable|string|null $sources
     *
     * @return static
     * @throws DirectoryNotMountedException
     */
    public function checkMounted(array|Stringable|string|null $sources): static
    {
        $status = $this->isMounted($sources);

        if ($status === false) {
            throw new DirectoryNotMountedException(tr('The directory ":directory" should be mounted from any of the sources ":source" but it is not mounted', [
                ':directory' => $this->getSource(),
                ':source'    => $sources,
            ]));
        }

        if (!$status) {
            throw new DirectoryNotMountedException(tr('The directory ":directory" should be mounted from ":source" but has an unknown mount state', [
                ':directory' => $this->getSource(),
                ':source'    => $sources,
            ]));
        }

        // We're mounted and from the right source, yay!
        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false if not mounted, NULL if mounted, but
     * with issues
     *
     * Issues can be either that the .isnotmounted file is visible (which it should NOT be if mounted) or (if specified)
     * $source does not match the mounted source
     *
     * @param array|Stringable|string|null $sources
     *
     * @return bool|null
     */
    public function isMounted(array|Stringable|string|null $sources): ?bool
    {
        $mounted     = $this->hasFile('.ismounted');
        $not_mounted = $this->hasFile('.isnotmounted');

        if ($mounted and !$not_mounted) {
            // This directory is mounted, yay!
            if ($sources) {
                // But is it mounted at the right place?
                $mount = PhoMounts::getDirectoryMountInformation($this);

                foreach ($sources as $source) {
                    if ($mount['source'] == PhoDirectory::new($source)->getSource()) {
                        return true;
                    }
                }

                return false;
            }

            return true;
        }

        if (!$mounted and $not_mounted) {
            return false;
        }

        // Either none of the files are available, or both are. Either case is an "unknown" state
        return null;
    }


    /**
     * Returns true if the specified file exists in this directory
     *
     * If the object file doesn't exist in the specified directory, go one dir up,
     * all the way to root /
     *
     * @param string $filename
     *
     * @return bool
     */
    public function hasFile(string $filename): bool
    {
        // Check filesystem restrictions
        $this->source = Strings::slash($this->source);
        $this->o_restrictions->check($this->source, false);
        $this->exists();

        return file_exists($this->source . Strings::ensureBeginsNotWith($filename, '/'));
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param array|Stringable|string|null $sources
     * @param array|null                   $options
     * @param string|null                  $filesystem
     *
     * @return static
     */
    public function ensureMounted(array|Stringable|string|null $sources, ?array $options = null, ?string $filesystem = null): static
    {
        if (!$this->isMounted($source)) {
            $this->mount($source, $options, $filesystem);
        }

        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param Stringable|string|null $source
     * @param string|null            $filesystem
     * @param array|null             $options
     *
     * @return static
     */
    public function mount(Stringable|string|null $source, ?string $filesystem = null, ?array $options = null): static
    {
        PhoMounts::mount(PhoFile::new($source, PhoRestrictions::newReadonlyObject($source)), $this,
                        $filesystem, $options);

        return $this;
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @param Stringable|string|null $source
     * @param array|null             $options
     *
     * @return static
     */
    public function bind(Stringable|string|null $source, ?array $options = null): static
    {
        // Add the required bind option
        $options[] = '--bind';

        // Source must be a directory
        return $this->mount(PhoDirectory::new($source), $options);
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @return static
     */
    public function unbind(): static
    {
        return $this->unmount();
    }


    /**
     * Returns true if this specific directory is mounted from somewhere, false otherwise
     *
     * @return static
     */
    public function unmount(): static
    {
        PhoMounts::unmount($this);

        return $this;
    }


    /**
     * Copy this directory with progress notification
     *
     * @param PhoPathInterface|string       $target
     * @param PhoRestrictionsInterface|null $o_restrictions
     * @param callable|null                 $callback
     * @param mixed|null                    $context
     * @param bool                          $recursive
     * @param bool                          $ignore_fails
     *
     * @return static
     * @example:
     * PhoFile::new($source)->copy($target, $restrictions, function ($notification_code, $severity, $message,
     * $message_code, $bytes_transferred, $bytes_max) { if ($notification_code == STREAM_Notification_PROGRESS) {
     *          // save $bytes_transferred and $bytes_max to file or database
     *      }
     *  });
     */
    public function copy(PhoPathInterface|string $target, ?PhoRestrictionsInterface $o_restrictions = null, ?callable $callback = null, mixed $context = null, bool $recursive = true, bool $ignore_fails = false): static
    {
        $context        = $context ?? stream_context_create();
        $o_restrictions = $this->ensureRestrictionsObject($o_restrictions);
        $o_target       = PhoDirectory::new($target, $o_restrictions)->ensure();

        $this->checkRestrictions(false);
        $o_target->checkRestrictions(true);

        stream_context_set_params($context, [
            'notification' => $callback,
        ]);

        // Copy the contents
        if ($recursive) {
            foreach ($this->getFilesObject() as $o_path) {
                $basename = $o_path->getBasename();

                if ($o_path->isDirectory()) {
                    $o_path->copy($o_target->addDirectory($basename), $o_target->getRestrictionsObject(), $callback, $context, $recursive, $ignore_fails);

                } elseif ($o_path->isLink()) {
                    symlink($this->addFile($basename)->getLinkTarget()->getSource(),
                            $o_target->addFile($basename)->getSource());

                } else {
                    try {
                        copy($this->addFile($basename)->getSource(),
                            $o_target->addFile($basename)->getSource(), $context);

                    } catch (PhpException $e) {
                        if ($this->addFile($basename)->isLink() and !$this->addFile($basename)->isLinkAndTargetExists()) {
                            // This is a broken symlink, PHP copy() chokes on that. Just create the symlink manually
                            symlink($this->addFile($basename)->getSource(),
                                    $o_target->addFile($basename)->getSource());

                            continue;
                        }

                        if (!$ignore_fails) {
                            Log::warning($e->getMessage());
                            continue;
                        }

                        throw $e;
                    }
                }
            }
        }

        return new static($o_target, $this->o_restrictions);
    }


    /**
     * Returns the specified directory added to this directory
     *
     * @param PhoPathInterface|string|int $directory
     *
     * @return PhoDirectoryInterface
     */
    public function addDirectory(PhoPathInterface|string|int $directory): PhoDirectoryInterface
    {
        $directory = $this->getSource() . Strings::ensureBeginsNotWith((string) $directory, '/');

        return PhoDirectory::new($directory, $this->o_restrictions)
                           ->setAutoMount($this->auto_mount);
    }


    /**
     * Returns the specified directory added to this directory
     *
     * @param PhoPathInterface|string $file
     *
     * @return PhoFileInterface
     */
    public function addFile(PhoPathInterface|string $file): PhoFileInterface
    {
        $file = $this->getSource() . Strings::ensureBeginsNotWith((string) $file, '/');

        return PhoFile::new($file, $this->o_restrictions)
                      ->setAutoMount($this->auto_mount);
    }


    /**
     * Returns a new FindInterface object
     *
     * @return FindInterface
     */
    public function find(): FindInterface
    {
        return Find::new($this);
    }


    /**
     * Returns true if this path contains any files
     *
     * @return bool
     */
    public function containFiles(): bool
    {
        return Find::new()
                   ->setPathObject($this)
                   ->setType('f')
                   ->executeReturnIterator()
                   ->isNotEmpty();
    }


    /**
     * Scans for duplicate files in this directory and optionally recurses
     *
     * Files are checked by size, and if that match, a hash comparison will be executed. If the hash matches, the file
     * is considered duplicate
     *
     * @param int|null $recurse_levels
     * @param int      $max_size
     *
     * @return PhoDuplicatesInterface
     */
    public function getDuplicateFiles(?int $recurse_levels = 1_000_000, int $max_size = 1_073_741_824): PhoDuplicatesInterface
    {
        Log::action(ts('Scanning path ":path" for duplicate files', [
            ':path' => $this->source
        ]));

        $max_size   = $max_size ?? 1_073_741_824;
        $sizes      = $this->getSizesTable($this, $recurse_levels, $max_size);
        $duplicates = [];

        Log::action(ts('Found ":count" potential duplicates, hash checking each', [
            ':count' => count($sizes)
        ]));

        // Scan the size list for duplicates
        foreach ($sizes as $files) {
            if (count($files) < 2) {
                continue;
            }

            // Possible duplicates be here
            $files = $this->getHashDuplicates($files);

            if ($files) {
                foreach ($files as $file => $hash) {
                    if (array_key_exists($hash, $duplicates)) {
                        // Merge the recursive files with the already existing files for this size
                        $duplicates[$hash]->add(new PhoFile($file, $this->o_restrictions), $file);

                    } else {
                        $duplicates[$hash]   = new PhoFiles($this, [$file => new PhoFile($file, $this->o_restrictions)]);
                    }
                }
            }
        }

        return new PhoDuplicates($this, $duplicates);
    }


    /**
     * Scans for and returns a list of file sizes with the files matching that size
     *
     * Used by PhoDirectoryCore::getDuplicateFiles()
     *
     * @param PhoDirectoryInterface $path
     * @param int|null              $recurse_levels
     * @param int                   $max_size
     *
     * @return array
     */
    protected function getSizesTable(PhoDirectoryInterface $path, ?int $recurse_levels, int $max_size): array
    {
        $sizes  = [];

        // Build up a list of file sizes
        foreach ($path->scan('*') as $file) {
            if ($file->isDirectory()) {
                // Recurse? Then get a sizes table from this sub directory and merge it with the current table
                if ($recurse_levels) {
                    $sub_sizes = $this->getSizesTable($file, $recurse_levels, $max_size);

                    foreach ($sub_sizes as $size => $files) {
                        if (array_key_exists($size, $sizes)) {
                            // Merge the recursive files with the already existing files for this size
                            $sizes[$size] = array_merge($sizes[$size], $files);
                        } else {
                            $sizes[$size] = $files;
                        }
                    }
                }

                continue;
            }

            $size = $file->getSize();

            if ($size > $max_size) {
                // Ignore this file, its too large
                Log::warning(ts('Ignoring file ":file" with size ":size", its larger than the specified maximum of ":maximum"', [
                    ':file'    => $file,
                    ':size'    => Numbers::getHumanReadableBytes($size) . ' (' . $size . ' bytes)',
                    ':maximum' => Numbers::getHumanReadableBytes($max_size) . ' (' . $max_size . ' bytes)'
                ]));
                continue;
            }

            if (array_key_exists($size, $sizes)) {
                // This file size already exists, is it a duplicate?
                $sizes[$size][$file->getSource()] = $file->getSource();

            } else {
                $sizes[$size] = [$file->getSource() => $file->getSource()];
            }
        }

        return $sizes;
    }


    /**
     * Returns a PhoFileInterface object for the specified file in this directory,
     *
     * @param  string $file
     * @param  bool $exception
     * @return PhoDirectoryInterface|PhoFileInterface|null
     */
    public function getFileObject(string $file, bool $exception = false): PhoDirectoryInterface|PhoFileInterface|null
    {
        $file = $this->source . Strings::ensureBeginsNotWith($file, '/');

        if (file_exists($file)) {
            if (is_dir($file)) {
                return new PhoDirectory($file, $this->o_restrictions);
            }

            return new PhoFile($file, $this->o_restrictions);
        }

        if ($exception) {
            throw new FileNotExistException(tr('Cannot return a FileObject for the specified file ":file" in this directory ":directory", the file does not exist', [
                ':file'      => $file,
                ':directory' => $this->source
            ]));
        }

        return null;
    }


    /**
     * Checks all specified files against each other to see if they match by hash
     *
     * @param array $files
     *
     * @return array
     */
    protected function getHashDuplicates(array $files): array
    {
        // First get a hash of each file
        foreach ($files as $path => &$hash) {
            $hash = sha1_file($path);
        }

        unset($hash);

        $duplicates = array_count_values($files);

        foreach ($duplicates as $hash => $count) {
            if ($count <= 1) {
                Arrays::removeValues($files, $hash);
            }
        }

        return Arrays::keepValues($files, array_keys($duplicates));
    }


    /**
     * Returns the size in bytes of this file or directory
     *
     * @param bool $recursive
     *
     * @return int
     */
    public function getSize(bool $recursive = true): int
    {
        // Return the number of all files in this directory
        $files = scandir($this->source);
        $size  = 0;

        foreach ($files as $file) {
            if (($file === '.') or ($file === '..')) {
                // Skip crap
                continue;
            }

            // Filename must have the complete absolute path
            $file = $this->source . $file;

            if (is_dir($file)) {
                if ($recursive) {
                    // Get filesize of this entire directory
                    $size += PhoPath::new($file, $this->o_restrictions)
                                    ->getSize($recursive);
                }
            } else {
                // Get file size of this file
                try {
                    $size += filesize($file);
                } catch (Throwable $e) {
                    if (file_exists($file)) {
                        throw $e;
                    }

                    // This is likely a dead soft symlink, we can simply ignore it.
                }
            }
        }

        return $size;
    }


    /**
     * Executes the specified callback on each file
     *
     * @param callable $callback
     * @return static
     */
    public function each(callable $callback): static
    {
        foreach ($this->scan() as $file) {
            $callback($file);
        }

        return $this;
    }
}
