<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryPath
 *
 * This trait contains methods for DataEntry objects that require a path
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryPath
{
    /**
     * Returns the path for this object
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->getValueTypesafe('string', 'path');
    }


    /**
     * Sets the path for this object
     *
     * @param string|null $path
     *
     * @return static
     */
    public function setPath(?string $path): static
    {
        return $this->set('path', $path);
    }
}
