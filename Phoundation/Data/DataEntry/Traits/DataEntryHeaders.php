<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryHeaders
 *
 * This trait contains methods for DataEntry objects that require a name and headers
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryHeaders
{
    /**
     * Returns the headers for this object
     *
     * @return array|null
     */
    public function getHeaders(): ?array
    {
        return $this->getSourceValueTypesafe('array', 'headers');
    }


    /**
     * Sets the headers for this object
     *
     * @param array|null $headers
     * @return static
     */
    public function setHeaders(?array $headers): static
    {
        return $this->setSourceValue('headers', $headers);
    }
}
