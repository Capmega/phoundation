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
 * @category  Function reference
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
    public static function getTemporary(bool $public = false, bool $persist = false): FsDirectoryInterface
    {
        if (!$persist) {
            // Return a non-persistent temporary directory that will be deleted once this process terminates
            $path = static::getSessionTemporaryPath($public) . Strings::getUuid();

            return static::new($path, FsRestrictions::getWritable($path, tr('persistent temporary directory')))
                         ->ensure();
        }

        $directory    = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $restrictions = FsRestrictions::getWritable($directory, tr('persistent temporary directory'));

        return static::new($directory . Strings::getUuid(), $restrictions)->ensure();
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_ROOT
     *
     * @param bool        $writable
     * @param string|null $label
     *
     * @return static
     */
    public static function getRoot(bool $writable = false, ?string $label = 'Unknown'): static
    {
        return new static(DIRECTORY_ROOT, FsRestrictions::new(DIRECTORY_ROOT, $writable, $label));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_COMMANDS
     *
     * @param bool        $writable
     * @param string|null $label
     *
     * @return static
     */
    public static function getCommands(bool $writable = false, ?string $label = 'Unknown'): static
    {
        return new static(DIRECTORY_COMMANDS, FsRestrictions::new(DIRECTORY_COMMANDS, $writable, $label));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_WEB
     *
     * @param bool        $writable
     * @param string|null $label
     *
     * @return static
     */
    public static function getWeb(bool $writable = false, ?string $label = 'Unknown'): static
    {
        return new static(DIRECTORY_WEB, FsRestrictions::new(DIRECTORY_WEB, $writable, $label));
    }


    /**
     * Returns a new FsDirectory object for the path DIRECTORY_DATA
     *
     * @param bool        $writable
     * @param string|null $label
     *
     * @return static
     */
    public static function getData(bool $writable = false, ?string $label = 'Unknown'): static
    {
        return new static(DIRECTORY_DATA, FsRestrictions::new(DIRECTORY_DATA, $writable, $label));
    }
}
