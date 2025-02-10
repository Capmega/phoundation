<?php

/**
 * Trait TraitDataEntryDirectory
 *
 * This trait contains methods for DataEntry objects that require a directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Data\Traits\TraitDataRestrictions;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;


trait TraitDataEntryDirectory
{
    use TraitDataRestrictions;


    /**
     * Returns the directory for this object
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectory(): ?string
    {
        return $this->getTypesafe('string', 'directory');
    }


    /**
     * Sets the directory for this object
     *
     * @param string|null $directory
     *
     * @return static
     */
    public function setDirectory(string|null $directory): static
    {
        if ($directory and (strlen($directory) > 2048)) {
            throw new OutOfBoundsException(tr('Specified directory ":directory" is invalid, the string should be no longer than 2048 characters', [
                ':directory' => $directory,
            ]));
        }

        return $this->set(get_null($directory), 'directory');
    }


    /**
     * Returns the directory for this object
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectoryObject(): ?PhoDirectoryInterface
    {
        $directory = $this->getDirectory();

        if ($directory) {
            $directory = new PhoDirectory($directory, $this->restrictions);
        }

        return $directory;
    }


    /**
     * Sets the directory for this object
     *
     * @param PhoDirectoryInterface|null $directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface|null $directory): static
    {
        return $this->setDirectory($directory?->getSource());
    }
}
