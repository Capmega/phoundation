<?php

/**
 * Trait TraitDataEntryFile
 *
 * This trait contains methods for DataEntry objects that require a file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;


trait TraitDataEntryFile
{
    use TraitDataRestrictions;


    /**
     * Returns the file for this object
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->getTypesafe('string', 'file');
    }


    /**
     * Sets the file for this object
     *
     * @param string|null $file
     *
     * @return static
     */
    public function setFile(string|null $file): static
    {
        if ($file and (strlen($file) > 2048)) {
            throw new OutOfBoundsException(tr('Specified file ":file" is invalid, the string should be no longer than 2048 characters', [
                ':file' => $file,
            ]));
        }

        return $this->set(get_null($file), 'file');
    }


    /**
     * Returns the file for this object
     *
     * @return PhoFileInterface|null
     */
    public function getFileObject(): ?PhoFileInterface
    {
        $file = $this->getFile();

        if ($file) {
            $file = new PhoFile($file, $this->restrictions);
        }

        return $file;
    }


    /**
     * Sets the file for this object
     *
     * @param PhoFileInterface|null $file
     *
     * @return static
     */
    public function setFileObject(PhoFileInterface|null $file): static
    {
        return $this->setFile($file?->getSource());
    }
}
