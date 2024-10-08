<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

/**
 * Trait TraitDataEntryDomain
 *
 * This trait contains methods for DataEntry objects that require a domain
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryDomain
{
    /**
     * Returns the domain for this object
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->getValueTypesafe('string', 'domain');
    }


    /**
     * Sets the domain for this object
     *
     * @param string|null $domain
     *
     * @return static
     */
    public function setDomain(?string $domain): static
    {
        return $this->set($domain, 'domain');
    }
}
