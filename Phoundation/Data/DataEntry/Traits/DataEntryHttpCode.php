<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait DataEntryHttpCode
 *
 * This trait contains methods for DataEntry objects that require an http_code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryHttpCode
{
    /**
     * Returns the http_code for this object
     *
     * @return int|null
     */
    public function getHttpCode(): ?int
    {
        return $this->getSourceValueTypesafe('int', 'http_code');
    }


    /**
     * Sets the http_code for this object
     *
     * @param int|null $http_code
     * @return static
     */
    public function setHttpCode(?int $http_code): static
    {
        return $this->setSourceValue('http_code', $http_code);
    }
}
