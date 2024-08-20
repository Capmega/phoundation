<?php

/**
 * Class FsFile
 *
 * This library contains various filesystem file-related functions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Filesystem\Traits\TraitPathConstructor;
use Phoundation\Filesystem\Traits\TraitPathNew;
use Phoundation\Utils\Strings;


class FsFile extends FsFileCore
{
    use TraitPathConstructor;
    use TraitPathNew;


    /**
     * Returns a new temporary file with the specified restrictions
     *
     * @param bool        $public
     * @param string|null $name
     * @param bool        $create
     * @param bool        $persist
     *
     * @return static
     */
    public static function getTemporary(bool $public = false, ?string $name = null, bool $create = true, bool $persist = false): static
    {
        $directory = FsDirectory::getTemporaryObject($public, $persist);
        $name      = ($name ?? Strings::getUuid());
        $file      = static::new($directory->getSource() . $name, FsRestrictions::getWritable($directory->getSource() . $name, tr('persistent temporary file')));

        if ($create) {
            $file->create();
        }

        return $file;
    }
}
