<?php

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Content\Images\Interfaces\ImageInterface;
use Stringable;
use Throwable;


/**
 * FileVariables class
 *
 * This library contains the variables used in the File class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
interface FileBasicsInterface
{
    /**
     * Returns the file for this File object
     *
     * @param Stringable|string|null $file
     * @param string|null $prefix
     * @param bool $must_exist
     * @return ImageInterface
     */
    public function setFile(Stringable|string|null $file, string $prefix = null, bool $must_exist = false): static;

    /**
     * Returns the file for this File object
     *
     * @return string|null
     */
    public function getFile(): ?string;

    /**
     * Sets the target file name in case operations create copies of this file
     *
     * @param Stringable|string $target
     * @return ImageInterface
     */
    public function setTarget(Stringable|string $target): static;

    /**
     * Returns the target file name in case operations create copies of this file
     *
     * @return string|null
     */
    public function getTarget(): ?string;

    /**
     * Check if the object file exists and is readable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type This is the label that will be added in the exception indicating what type
     *                                      of file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will
     *                                      be thrown
     * @return ImageInterface
     */
    public function checkReadable(?string $type = null, ?Throwable $previous_e = null): static;

    /**
     * Check if the object file exists and is writable. If not both, an exception will be thrown
     *
     * On various occasions, this method could be used AFTER a file read action failed and is used to explain WHY the
     * read action failed. Because of this, the method optionally accepts $previous_e which would be the exception that
     * is the reason for this check in the first place. If specified, and the method cannot file reasons why the file
     * would not be readable (ie, the file exists, and can be read accessed), it will throw an exception with the
     * previous exception attached to it
     *
     * @param string|null $type This is the label that will be added in the exception indicating what type of
     *                                   file it is
     * @param Throwable|null $previous_e If the file is okay, but this exception was specified, this exception will be
     *                                   thrown
     * @return ImageInterface
     */
    public function checkWritable(?string $type = null, ?Throwable $previous_e = null): static;

    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileType(): array;

    /**
     * Returns array with all permission information about the object files.
     *
     * Idea taken from http://php.net/manual/en/function.fileperms.php
     *
     * @return array
     */
    public function getHumanReadableFileMode(): array;

    /**
     * Returns the mimetype data for the object file
     *
     * @return string The mimetype data for the object file
     * @version 2.4: Added documentation
     */
    public function mimetype(): string;

    /**
     * Securely delete a file weather it exists or not, without error, using the "shred" command
     *
     * Since shred doesn't have a recursive option, this function will use "find" to find all files matching the
     * specified pattern, and will delete them all
     *
     * @param bool $clean_path
     * @param bool $sudo
     * @return static
     */
    public function secureDelete(bool $clean_path = true, bool $sudo = false): static;

    /**
     * Delete a file weather it exists or not, without error, using the "rm" command
     *
     * @param boolean $clean_path If specified true, all directories above each specified pattern will be deleted as
     *                            well as long as they are empty. This way, no empty directories will be left lying
     *                            around
     * @param boolean $sudo If specified true, the rm command will be executed using sudo
     * @param bool $escape If true, will escape the filename. This may cause issues when using wildcards, for
     *                            example
     * @return ImageInterface
     * @see Restrictions::check() This function uses file location restrictions
     */
    public function delete(bool $clean_path = true, bool $sudo = false, bool $escape = true): static;

    /**
     * Moves this file to the specified target, will try to ensure target path exists
     *
     * @param string $target
     * @param bool $ensure_path
     * @return static
     */
    public function move(string $target, bool $ensure_path = true): static;

    /**
     * Switches file mode to the new value and returns the previous value
     *
     * @param string|int $mode
     * @return string|int
     */
    public function switchMode(string|int $mode): string|int;

    /**
     * Returns the file mode for the object file
     *
     * @return string|int|null
     */
    public function getMode(): string|int|null;

    /**
     * Returns the stat data for the object file
     *
     * @return array
     */
    public function getStat(): array;

    /**
     * Update the object file owner and group
     *
     * @param string|null $user
     * @param string|null $group
     * @param bool $recursive
     * @return ImageInterface
     * @see $this->chmod()
     *
     * @note This function ALWAYS requires sudo as chown is a root only filesystem command
     */
    public function chown(?string $user = null, ?string $group = null, bool $recursive = false): static;

    /**
     * Change file mode, optionally recursively
     *
     * @param string|int $mode The mode to apply to the specified path (and all files below if recursive is specified)
     * @param boolean $recursive If set to true, apply specified mode to the specified path and all files below by
     *                           recursion
     * @param bool $sudo
     * @return ImageInterface
     * @see $this->chown()
     *
     */
    public function chmod(string|int $mode, bool $recursive = false, bool $sudo = false): static;

    /**
     * Ensure that the object file is readable
     *
     * This method will ensure that the object file will exist and is readable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return bool
     */
    public function ensureFileReadable(?int $mode = null): bool;

    /**
     * Ensure that the object file is writable
     *
     * This method will ensure that the object file will exist and is writable. If it does not exist, an empty file
     * will be created in the parent directory of the specified $this->file
     *
     * @param int|null $mode
     * @return bool
     */
    public function ensureFileWritable(?int $mode = null): bool;
}