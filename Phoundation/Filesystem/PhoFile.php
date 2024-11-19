<?php

/**
 * Class PhoFile
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

use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\Interfaces\PhoRestrictionsInterface;
use Phoundation\Filesystem\Traits\TraitPathConstructor;
use Phoundation\Filesystem\Traits\TraitPathNew;
use Phoundation\Utils\Strings;


class PhoFile extends PhoFileCore
{
    use TraitPathConstructor;
    use TraitPathNew;


    /**
     * Returns a new file object for a file in data/...
     *
     * @param string                   $file
     * @param PhoRestrictionsInterface $restrictions
     *
     * @return PhoFileInterface
     */
    public static function newDataObject(string $file, PhoRestrictionsInterface $restrictions): PhoFileInterface
    {
        return static::new(DIRECTORY_DATA . $file, $restrictions);
    }


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
    public static function getTemporaryObject(bool $public = false, ?string $name = null, bool $create = true, bool $persist = false): static
    {
        $directory = PhoDirectory::newTemporaryObject($public, $persist);
        $name      = ($name ?? Strings::getUuid());
        $file      = static::new($directory->getSource() . $name, PhoRestrictions::newWritable($directory->getSource() . $name));

        if ($create) {
            $file->create();
        }

        return $file;
    }
}
