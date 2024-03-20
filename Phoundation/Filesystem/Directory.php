<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Exception\DirectoryNotDirectoryException;
use Phoundation\Filesystem\Interfaces\DirectoryInterface;
use Phoundation\Filesystem\Traits\TraitPathConstructor;
use Phoundation\Filesystem\Traits\TraitPathNew;
use Phoundation\Utils\Strings;


/**
 * Class Directory
 *
 * This class represents a single directory and contains various methods to manipulate directories.
 *
 * It can rename, copy, traverse, mount, and much more
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Filesystem
 */
class Directory extends DirectoryCore implements DirectoryInterface
{
    use TraitPathConstructor {
        __construct as protected ___construct;
    }
    use TraitPathNew;


    /**
     * Directory class constructor
     *
     * @param mixed $source
     * @param array|string|Restrictions|null $restrictions
     * @param bool $make_absolute
     */
    public function __construct(mixed $source = null, array|string|Restrictions|null $restrictions = null, bool $make_absolute = false)
    {
        $this->___construct($source, $restrictions, $make_absolute);

        // Path must always end with a /
        $this->path = Strings::slash($this->path);

        if (file_exists($this->path)) {
            // This exists, it must be a directory!
            if (!is_dir($this->path)) {
                throw new DirectoryNotDirectoryException(tr('The specified path ":path" is not a directory', [
                    ':path' => $source
                ]));
            }
        }
    }


    /**
     * Returns a temporary directory specific for this process that will be removed once the process terminates
     *
     * The temporary directory returned will always be the same within one process, if per
     *
     * @param bool $public
     * @param bool $persist If specified, the temporary directory will persist and not be removed once the process
     *                      terminates
     * @return DirectoryInterface
     */
    public static function getTemporary(bool $public = false, bool $persist = false): DirectoryInterface
    {
        if (!$persist) {
            // Return a non-persistent temporary directory that will be deleted once this process terminates
            $path = static::getSessionTemporaryPath($public) . Strings::getUuid();
            return static::new($path, Restrictions::writable($path, tr('persistent temporary directory')))->ensure();
        }

        $directory    = ($public ? DIRECTORY_PUBTMP : DIRECTORY_TMP);
        $restrictions = Restrictions::writable($directory, tr('persistent temporary directory'));

        return static::new($directory . Strings::getUuid(), $restrictions)->ensure();
    }
}
