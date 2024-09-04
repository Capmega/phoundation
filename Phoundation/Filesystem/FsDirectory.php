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
    public static function newTemporaryObject(bool $public = false, bool $persist = false): FsDirectoryInterface
    {
        if (!$persist) {
            // Return a non-persistent temporary directory that will be deleted once this process terminates
            $path = static::getProcessTemporaryPath($public) . Strings::getUuid();

            return static::new($path, FsRestrictions::newWritable($path))->ensure();
        }

        $directory    = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $restrictions = FsRestrictions::newWritable($directory);

        return static::new($directory . Strings::getUuid(), $restrictions)
                     ->setAutoMount(false)
                     ->ensure();
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
    public static function newDataTmpObject(bool $restrictions_writable = false, ?string $sub_directory = null): FsDirectoryInterface
    {
        return static::new(DIRECTORY_TMP . $sub_directory, FsRestrictions::new(DIRECTORY_TMP . $sub_directory, $restrictions_writable))
                     ->ensure();
    }


    /**
     * Returns the data/content/cdn directory
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return FsDirectoryInterface
     */
    public static function newCdnObject(bool $restrictions_writable = false, ?string $sub_directory = null): FsDirectoryInterface
    {
        return static::new(DIRECTORY_CDN . LANGUAGE . $sub_directory, FsRestrictions::new(DIRECTORY_CDN . LANGUAGE . $sub_directory, $restrictions_writable))
                     ->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_ROOT
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newRootObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new(DIRECTORY_ROOT . $sub_directory, FsRestrictions::new(DIRECTORY_ROOT . $sub_directory, $restrictions_writable))
                     ->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path /
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newFilesystemRootObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new('/' . $sub_directory, FsRestrictions::new('/' . $sub_directory, $restrictions_writable))
                     ->ensure();
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
    public static function newDomainObject(string $domain, bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new($domain . ':/' . $sub_directory, FsRestrictions::new($domain . ':/' . $sub_directory, $restrictions_writable));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_COMMANDS
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newCommandsObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new(DIRECTORY_COMMANDS . $sub_directory, FsRestrictions::new(DIRECTORY_COMMANDS . $sub_directory, $restrictions_writable))
                     ->setAutoMount(false)
                     ->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_WEB
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newWebObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new(DIRECTORY_WEB . $sub_directory, FsRestrictions::new(DIRECTORY_WEB . $sub_directory, $restrictions_writable))
                     ->setAutoMount(false)
                     ->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_DATA
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newDataObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new(DIRECTORY_DATA . $sub_directory, FsRestrictions::new(DIRECTORY_DATA . $sub_directory, $restrictions_writable))
                     ->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_DATA
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newDataSourcesObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new(DIRECTORY_DATA . 'sources/' . $sub_directory, FsRestrictions::new(DIRECTORY_DATA . 'sources/' . $sub_directory, $restrictions_writable))
                     ->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_DATA
     *
     * @param bool        $restrictions_writable
     * @param string|null $sub_directory
     *
     * @return static
     */
    public static function newUserFilesObject(bool $restrictions_writable = false, ?string $sub_directory = null): static
    {
        return static::new(DIRECTORY_DATA . 'files/' . $sub_directory, FsRestrictions::newUserFilesObject($restrictions_writable, $sub_directory))
                     ->ensure();
    }
}
