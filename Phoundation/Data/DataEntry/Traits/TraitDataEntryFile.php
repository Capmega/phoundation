<?php

/**
 * Trait TraitDataEntryFile
 *
 * This trait contains methods for DataEntry objects that require a file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\Interfaces\FsFileInterface;


trait TraitDataEntryFile
{
    use TraitDataRestrictions;


    /**
     * Returns the file for this object
     *
     * @return FsFileInterface|null
     */
    public function getFile(): ?FsFileInterface
    {
        $file = $this->getTypesafe(FsFileInterface::class . '|string', 'file');

        if ($file and is_string($file)) {
            $file = new FsFile($file, $this->restrictions);
        }

        return $file;
    }


    /**
     * Sets the file for this object
     *
     * @param FsFileInterface|string|null $file
     *
     * @return static
     */
    public function setFile(FsFileInterface|string|null $file): static
    {
        if (is_string($file)) {
            if (strlen($file) > 2048) {
                throw new OutOfBoundsException(tr('Specified file ":file" is invalid, the string should be no longer than 2048 characters', [
                    ':file' => $file,
                ]));
            }
        }

        return $this->set(get_null((string) $file), 'file');
    }
}
