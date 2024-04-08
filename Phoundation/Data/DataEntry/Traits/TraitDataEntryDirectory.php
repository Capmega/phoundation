<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryDirectory
 *
 * This trait contains methods for DataEntry objects that require a directory
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryDirectory
{
    /**
     * Returns the path for this object
     *
     * @return string|null
     */
    public function getDirectory(): ?string
    {
        return $this->getSourceFieldValue('string', 'directory');
    }


    /**
     * Sets the path for this object
     *
     * @param string|null $directory
     *
     * @return static
     */
    public function setDirectory(?string $directory): static
    {
        return $this->setSourceValue('directory', $directory);
    }
}