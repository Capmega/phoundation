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

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Filesystem\Traits\TraitDataRestrictions;
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
     * @param string|null $o_directory
     *
     * @return static
     */
    public function setDirectory(string|null $o_directory): static
    {
        if ($o_directory and (strlen($o_directory) > 2048)) {
            throw new OutOfBoundsException(tr('Specified directory ":directory" is invalid, the string should be no longer than 2048 characters', [
                ':directory' => $o_directory,
            ]));
        }

        return $this->set(get_null($o_directory), 'directory');
    }


    /**
     * Returns the directory for this object
     *
     * @return PhoDirectoryInterface|null
     */
    public function getDirectoryObject(): ?PhoDirectoryInterface
    {
        $o_directory = $this->getDirectory();

        if ($o_directory) {
            $o_directory = new PhoDirectory($o_directory, $this->o_restrictions);
        }

        return $o_directory;
    }


    /**
     * Sets the directory for this object
     *
     * @param PhoDirectoryInterface|null $o_directory
     *
     * @return static
     */
    public function setDirectoryObject(PhoDirectoryInterface|null $o_directory): static
    {
        return $this->setDirectory($o_directory?->getSource());
    }
}
