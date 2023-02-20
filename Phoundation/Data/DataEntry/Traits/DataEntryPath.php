<?php

namespace Phoundation\Data\DataEntry\Traits;



/**
 * Trait DataEntryPath
 *
 * This trait contains methods for DataEntry objects that require a path
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryPath
{
    /**
     * Returns the path for this object
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->getDataValue('path');
    }



    /**
     * Sets the path for this object
     *
     * @param string|null $path
     * @return static
     */
    public function setPath(?string $path): static
    {
        return $this->setDataValue('path', $path);
    }
}