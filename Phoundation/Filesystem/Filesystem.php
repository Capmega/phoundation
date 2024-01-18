<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Exception\FileNotExistException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Interfaces\RestrictionsInterface;
use Phoundation\Utils\Strings;
use Stringable;
use Throwable;


/**
 * Filesystem class
 *
 * This library contains various filesystem file related functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Filesystem
{
    const DIRECTORY_SEPARATOR = '/';


    /**
     * Ensures that the object file name is valid
     *
     * @param string|null $file
     * @return void
     */
    public static function validateFilename(?string $file = null): void
    {
        if ($file === null) {
            return;
        }

        $file = trim($file);

        if (!$file) {
            throw new OutOfBoundsException(tr('No file specified'));
        }

        if (strlen($file) > 4096) {
            throw new OutOfBoundsException(tr('The object filename is too large with ":size" bytes', [
                ':size' => strlen($file)
            ]));
        }
    }


    /**
     * Return the extension of the object filename
     *
     * @param string $filename
     * @return string
     */
    public static function getExtension(string $filename): string
    {
        return Strings::fromReverse($filename, '.');
    }


    /**
     * Return true if the specified mimetype is for a compressed file, false if not
     *
     * This function will check the primary and secondary sections of the mimetype and depending on their values,
     * determine if the file format should use compression or not
     *
     * @version 2.5.90: Added function and documentation
     * @param string $primary        The primary mimetype section to check. If the mimetype is "text/plain", this
     *                               variable would receive "text". You can also leave $secondary empty and specify the
     *                               complete mimetype "text/plain" here, both will work
     * @param string|null $secondary The secondary mimetype section to check. If the mimetype is "text/plain", this
     *                               variable would receive "plain". If the complete mimetype is specified in $primary,
     *                               you can leave this one empty
     * @return boolean True if the specified mimetype is for a compressed file, false if not
     */
    public static function isCompressed(string $primary, ?string $secondary = null): bool
    {
// :TODO: IMPROVE THIS! Loads of files that may be mis detected
        // Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
        if (!$secondary) {
            if (!str_contains($primary, '/')) {
                throw new FilesystemException(tr('Invalid primary mimetype data ":primary" specified. Either specify the complete mimetype in $primary, or specify the independent primary and secondary sections in $primary and $secondary', [
                    ':primary' => $primary
                ]));
            }

            $secondary = Strings::from($primary , '/');
            $primary   = Strings::until($primary, '/');
        }

        // Check the mimetype data
        if (str_contains($secondary, 'compressed')) {
            // This file is already compressed
            return true;

        } elseif (str_contains($secondary, 'zip')) {
            // This file is already compressed
            return true;

        } else {
            switch ($secondary) {
                case 'jpeg':
                    // no-break
                case 'mpeg':
                    // no-break
                case 'ogg':
                    // This file is already compressed
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
                            // This file probably is not compressed
                            return false;
                    }
            }
        }

        throw new FilesystemException(tr('Unable to determine if mimetype ":primary/:secondary" is compressed or not', [
            ':primary'   => $primary,
            ':secondary' => $secondary
        ]));
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
    public static function isBinary(string $primary, ?string $secondary = null): bool
    {
// TODO So isText() works on a file and this works on mimetype strings? Fix this!
// TODO There is more to this
// :TODO: IMPROVE THIS! Loads of files that are not text/ are still not binary
        // Check if we received independent primary and secondary mimetype sections, or if we have to cut them ourselves
        if (!$secondary) {
            if (!str_contains($primary, '/')) {
                throw new FilesystemException(tr('Invalid primary mimetype data ":primary" specified. Either specify the complete mimetype in $primary, or specify the independent primary and secondary sections in $primary and $secondary', [
                    ':primary' => $primary
                ]));
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
     * Return the absolute directory for the specified directory
     *
     * @note If the specified directory exists, and it is a directory, this function will automatically add a trailing / to
     *       the directory name
     * @param Stringable|string|null $directory
     * @param Stringable|string|null $prefix
     * @param bool $must_exist
     * @return string The absolute directory
     */
    public static function absolute(Stringable|string|null $directory = null, Stringable|string|null $prefix = null, bool $must_exist = true): string
    {
        $directory = trim((string) $directory);

        if (!$directory) {
            return DIRECTORY_ROOT;
        }

        Filesystem::validateFilename($directory);

        if (str_starts_with($directory, '/')) {
        // This is already an absolute directory
            $return = $directory;

        } elseif (str_starts_with($directory, '~')) {
            // This is a user home directory
            $return = Strings::unslash($_SERVER['HOME']) . Strings::startsWith(substr($directory, 1), '/');

        } elseif (str_starts_with($directory, './')) {
            // This is a user home directory
            $return = STARTDIR . substr($directory, 2);

        } else {
            // This is not an absolute directory, make it an absolute directory
            $prefix = trim((string) $prefix);

            if (!$prefix) {
                $prefix = DIRECTORY_ROOT;
            } else {
                switch ($prefix) {
                    case 'css':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/css/';
                        break;

                    case 'js':
                        // no-break
                    case 'javascript':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/js/';
                        break;

                    case 'img':
                        // no-break
                    case 'image':
                        // no-break
                    case 'images':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/img/';
                        break;

                    case 'font':
                        // no-break
                    case 'fonts':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/fonts/';
                        break;

                    case 'video':
                        // no-break
                    case 'videos':
                        $prefix = DIRECTORY_CDN . LANGUAGE . '/video/';
                        break;
                }
            }

            $return = Strings::slash($prefix) . Strings::unslash($directory);
        }

        // If this is a directory, make sure it has a slash suffix
        if (file_exists($return)) {
            if (is_dir($return)) {
                $return = Strings::slash($return);
            }
        } else {
            if ($must_exist) {
                throw new FileNotExistException(tr('The resolved path ":resolved" for the specified path ":directory" with prefix ":prefix" does not exist', [
                    ':prefix'    => $prefix,
                    ':directory' => $directory,
                    ':resolved'  => $return
                ]));
            }

            // The path doesn't exist, but apparently that's okay! Continue!
        }

        return $return;
    }


    /**
     * realdirectory() wrapper that won't crash with an exception if the specified string is not a real directory
     *
     * @param string $directory
     * @return ?string string The real directory extrapolated from the specified $directory, if exists. False if whatever was
     *                 specified does not exist.
     *
     * @example
     * code
     * show(is_directory('test'));
     * showdie(is_directory('/bin'));
     * /code
     *
     * This would return
     * code
     * false
     * /bin
     * /code
     *
     */
    public static function real(string $directory): ?string
    {
        try {
            return realdirectory($directory);

        }catch(Throwable $e) {
            // If PHP threw an error for the directory not being a directory at all, just return false
            $message = $e->getMessage();

            if (str_contains($message, 'expects parameter 1 to be a valid directory')) {
                return null;
            }

            // This is some other error, keep throwing
            throw new FilesystemException(tr('Failed'), $e);
        }
    }


    /**
     * Creates a temporary directory
     *
     * @param bool $public
     * @return Directory A Directory object with the temp directory
     */
    public static function createTempDirectory(bool $public = true) : Directory
    {
        // Public or private TMP?
        $tmp_directory = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $directory     = static::createTemp($tmp_directory);

        mkdir($directory);

        return new Directory($directory, Restrictions::new($tmp_directory, true));
    }


    /**
     * Creates a temporary directory
     *
     * @param bool $public
     * @param string|null $extension
     * @return File A File object with the temp directory
     * @deprecated
     */
    public static function createTempFile(bool $public = true, ?string $extension = null) : File
    {
        // Public or private TMP?
        $tmp_directory = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $file     = static::createTemp($tmp_directory, $extension);

        touch($file);

        return new File($file, Restrictions::new($tmp_directory, true));
    }


    /**
     * Returns the specified octal filemode into a text readable filemode (rwxrwxrwx)
     *
     * @param int $mode
     * @return string
     */
    public static function readableFileMode(int $mode): string
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
     * Return a system directory for the specified type
     *
     * @param string $type
     * @param string $directory
     * @return string
     */
    public static function systemDirectory(string $type, string $directory = ''): string
    {
        switch ($type) {
            case 'img':
                // no-break
            case 'image':
                return '/pub/img/' . $directory;

            case 'css':
                // no-break
            case 'style':
                return '/pub/css/' . $directory;

            default:
                throw new OutOfBoundsException(tr('Unknown type ":type" specified', [':type' => $type]));
        }
    }


    /**
     * Creates a directory to a temporary directory or file
     *
     * @param string $tmp_directory
     * @param string|null $extension
     * @return string Directory to the tmp file or directory
     */
    protected static function createTemp(string $tmp_directory, ?string $extension = null) : string
    {
        // Ensure that the TMP directory exists
        Directory::new($tmp_directory, $tmp_directory)->ensure();

        // All temp files and directories are limited to their sessions
        $session_id = session_id();
        $file       = substr(hash('sha1', uniqid() . microtime()), 0, 12);

        if ($session_id) {
            $file = $session_id . '/' . $file;
        }

        if ($extension) {
            $file .= '.' . $extension;
        }

        $file = $tmp_directory . $file;

        // Temp directory can not exist yet
        if (file_exists($file)) {
            File::new($file, Restrictions::new($tmp_directory, true, 'temporary'))->delete();
        }

        return $file;
    }
}
