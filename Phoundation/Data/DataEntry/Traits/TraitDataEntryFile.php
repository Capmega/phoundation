<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Exception\OutOfBoundsException;

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
trait TraitDataEntryFile
{
    /**
     * Returns the file for this object
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->getValueTypesafe('string', 'file');
    }


    /**
     * Sets the file for this object
     *
     * @param string|null $file
     *
     * @return static
     */
    public function setFile(?string $file): static
    {
        if (strlen((string) $file) > 2048) {
            throw new OutOfBoundsException(tr('Specified file ":file" is invalid, the string should be no longer than 2048 characters', [
                ':file' => $file,
            ]));
        }

        return $this->set($file, 'file');
    }
}
