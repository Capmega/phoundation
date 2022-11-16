<?php

namespace Phoundation\Filesystem;

use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Core\Strings;
use Phoundation\Filesystem\Exception\FileNotWritableException;
use Phoundation\Filesystem\Exception\FilesystemException;
use Phoundation\Processes\Exception\ProcessesException;
use Phoundation\Servers\Server;
use Phoundation\Servers\ServerUser;
use Throwable;


/**
 * FileVariables class
 *
 * This library contains the variables used in the File class
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class FileBasics extends ServerUser
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
     * The file for this File object
     *
     * @var string|null $file
     */
    protected string|null $file = null;

    /**
     * The target file name in case operations create copies of this file
     *
     * @var string|null $target
     */
    protected string|null $target = null;



    /**
     * File class constructor
     *
     * @param array|string|null $file
     * @param Server|array|string|null $server
     */
    public function __construct(array|string|null $file = null, Server|array|string|null $server = null)
    {
        Filesystem::validateFilename($file);

        $this->setServer($server);
        $this->file = $file;
    }



    /**
     * Returns a new File object with the specified restrictions
     *
     * @param array|string|null $file
     * @param Server|array|string|null $server
     * @return static
     */
    public static function new(array|string|null $file = null, Server|array|string|null $server = null): static
    {
        return new static($file, $server);
    }



    /**
     * Returns the file for this File object
     *
     * @param string $file
     * @return static
     */
    public function setFile(string $file): static
    {
        $this->file = $file;
        return $this;
    }



    /**
     * Returns the file for this File object
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }



    /**
     * Sets the target file name in case operations create copies of this file
     *
     * @param string $target
     * @return static
     */
    public function setTarget(string $target): static
    {
        $this->target = $target;
        return $this;
    }



    /**
     * Returns the target file name in case operations create copies of this file
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        if ($this->target === null) {
            // By default assume a backup file
            return $this->file . '~';
        }

        return $this->target;
    }



    /**
     * Check the specified $path against these objects' restrictions
     *
     * @param string|null $file
     * @param bool $write
     * @return void
     */
    protected function checkRestrictions(string|null &$file, bool $write): void
    {
        $this->server->checkRestrictions($file, $write);
    }



    /**
     * Checks if the specified file exists
     *
     * @return void
     */
    protected function exists(): void
    {
        if (!file_exists($this->file)) {
            throw new FilesystemException(tr('Specified file ":file" does not exist', [':file' => $this->file]));
        }
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
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null) : static
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, false);

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

        if ($previous_e) {
            throw $previous_e;

//            // This method was called because a read action failed, throw an exception for it
//            throw new FilesystemException(tr('The:type file ":file" cannot be read because of an unknown error', [
//                ':type' => ($type ? '' : ' ' . $type),
//                ':file' => $this->file
//            ]), previous: $previous_e);
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
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

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

        return $this;
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
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);

        // Get configuration. We need file and directory default modes
        $mode = Config::get('filesystem.mode.default.file', 0640, $mode);

        // If the object file exists and is writable, then we're done.
        if (is_writable($this->file)) {
            return $this;
        }

        // From here the file is not writable. It may not exist, or it may simply not be writable. Lets continue...

        if (file_exists($this->file)) {
            // Great! The file exists, but it is not writable at this moment. Try to make it writable.
            try {
                Log::warning(tr('The object file ":file" (Realpath ":path") is not writable. Attempting to apply default file mode ":mode"', [
                    ':file' => $this->file,
                    ':path' => realpath($this->file),
                    ':mode' => $mode
                ]));

                $this->chmod('u+w');

            } catch (ProcessesException $e) {
                throw new FileNotWritableException(tr('The object file ":file" (Realpath ":path") is not writable, and could not be made writable', [
                    ':file' => $this->file,
                    ':path' => realpath($this->file)
                ]));
            }
        }

        // As of here we know the file doesn't exist. Attempt to create it. First ensure the parent path exists.
        Path::new(dirname($this->file), $this->server)->ensure();

        Log::warning(tr('The object file ":file" (Realpath ":path") does not exist. Attempting to create it with file mode ":mode"', [
            ':mode' => Strings::fromOctal($mode),
            ':file' => $this->file,
            ':path' => realpath($this->file)
        ]));

        touch($this->file);
        $this->chmod($mode);
        return $this;
    }



    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileType(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, true);
        $return = [];

        $this->exists();

        $perms     = fileperms($this->file);
        $socket    = (($perms & 0xC000) == 0xC000);
        $symlink   = (($perms & 0xA000) == 0xA000);
        $regular   = (($perms & 0x8000) == 0x8000);
        $bdevice   = (($perms & 0x6000) == 0x6000);
        $cdevice   = (($perms & 0x2000) == 0x2000);
        $directory = (($perms & 0x4000) == 0x4000);
        $fifopipe  = (($perms & 0x1000) == 0x1000);

        if ($socket) {
            // This file is a socket
            $return[$this->file] = 'socket';

        } elseif ($symlink) {
            // This file is a symbolic link
            $return[$this->file] = 'symbolic link';

        } elseif ($regular) {
            // This file is a regular file
            $return[$this->file] = 'regular file';

        } elseif ($bdevice) {
            // This file is a block device
            $return[$this->file] = 'block device';

        } elseif ($directory) {
            // This file is a directory
            $return[$this->file] = 'directory';

        } elseif ($cdevice) {
            // This file is a character device
            $return[$this->file] = 'character device';

        } elseif ($fifopipe) {
            // This file is a FIFO pipe
            $return[$this->file] = 'fifo pipe';
        } else {
            // This file is an unknown type
            $return[$this->file] = 'unknown';
        }

        return $return;
    }



    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileMode(): array
    {
        // Check filesystem restrictions
        $this->checkRestrictions($this->file, false);
        $this->exists();

        $perms  = fileperms($this->file);
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
}
