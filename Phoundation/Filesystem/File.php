<?php

declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Filesystem\Interfaces\FileInterface;
use Phoundation\Filesystem\Traits\TraitPathConstructor;
use Phoundation\Filesystem\Traits\TraitPathNew;
use Phoundation\Utils\Strings;

/**
 * Class File
 *
 * This library contains various filesystem file-related functions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
class File extends FileCore implements FileInterface
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
        $directory = Directory::getTemporary($public, $persist);
        $name      = ($name ?? Strings::getUuid());
        $file      = static::new($directory->getPath() . $name, Restrictions::writable($directory->getPath() . $name, tr('persistent temporary file')));
        if ($create) {
            $file->create();
        }

        return $file;
    }
}
