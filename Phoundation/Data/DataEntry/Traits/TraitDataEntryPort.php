<?php

/**
 * Trait TraitDataEntryHostnamePort
 *
 * This trait contains methods for DataEntry objects that requires a url
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

trait TraitDataEntryPort
{
    /**
     * Returns the port for this object
     *
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->getTypesafe('int', 'port');
    }


    /**
     * Sets the port for this object
     *
     * @param int|null $port
     *
     * @return static
     */
    public function setPort(?int $port): static
    {
        return $this->set($port, 'port');
    }
}