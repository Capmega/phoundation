<?php

/**
 * Class FsDirectory
 *
 * This class represents a single directory and contains various methods to manipulate directories.
 *
 * It can rename, copy, traverse, mount, and much more
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Filesystem\Traits\TraitDirectoryConstructor;
use Phoundation\Filesystem\Traits\TraitDirectoryNew;
use Phoundation\Utils\Strings;


class FsDirectory extends FsDirectoryCore
{
    use TraitDirectoryConstructor;
    use TraitDirectoryNew;


    /**
     * Returns a temporary directory specific for this process that will be removed once the process terminates
     *
     * The temporary directory returned will always be the same within one process, if per
     *
     * @param bool $public
     * @param bool $persist If specified, the temporary directory will persist and not be removed once the process
     *                      terminates
     *
     * @return FsDirectoryInterface
     */
    public static function getTemporaryObject(bool $public = false, bool $persist = false): FsDirectoryInterface
    {
        if (!$persist) {
            // Return a non-persistent temporary directory that will be deleted once this process terminates
            $path = static::getProcessTemporaryPath($public) . Strings::getUuid();

            return static::new($path, FsRestrictions::getWritable($path))->ensure();
        }

        $directory    = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $restrictions = FsRestrictions::getWritable($directory);

        return static::new($directory . Strings::getUuid(), $restrictions)->ensure();
    }


    /**
     * Returns the data/tmp directory specific for this process that will be removed once the process terminates
     *
     * The temporary directory returned will always be the same within one process
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return FsDirectoryInterface
     */
    public static function getDataTmpObject(bool $restrictions_writable = false, ?string $sub_directory = null): FsDirectoryInterface
    {
        return new static(DIRECTORY_TMP . $sub_directory, FsRestrictions::new(DIRECTORY_TMP, $restrictions_writable));
    }


    /**
     * Returns the data/content/cdn directory
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return FsDirectoryInterface
     */
    public static function getCdnObject(bool $restrictions_writable = false, ?string $sub_directory = null): FsDirectoryInterface
    {
        return new static(DIRECTORY_CDN . LANGUAGE . $sub_directory, FsRestrictions::new(DIRECTORY_CDN . LANGUAGE, $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_ROOT
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getRootObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_ROOT . $sub_directory, FsRestrictions::new(DIRECTORY_ROOT, $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path /
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getFilesystemRootObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static('/' . $sub_directory, FsRestrictions::new('/', $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the specified domain
     *
     * @param string      $domain
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getDomainObject(string $domain, bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static($domain . ':/' . $sub_directory, FsRestrictions::new($domain . ':/', $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_COMMANDS
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getCommandsObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_COMMANDS . $sub_directory, FsRestrictions::new(DIRECTORY_COMMANDS, $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_WEB
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getWebObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_WEB . $sub_directory, FsRestrictions::new(DIRECTORY_WEB, $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_DATA
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getDataObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_DATA . $sub_directory, FsRestrictions::new(DIRECTORY_DATA, $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_DATA
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function getDataSourcesObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return new static(DIRECTORY_DATA . 'sources/' . $sub_directory, FsRestrictions::new(DIRECTORY_DATA . 'sources/', $restrictions_writable));
    }
}
