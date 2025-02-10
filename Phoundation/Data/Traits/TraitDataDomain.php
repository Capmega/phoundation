<?php

/**
 * Trait TraitDataDomain
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataDomain
{
    /**
     * The domain for this object
     *
     * @var string|null $domain
     */
    protected ?string $domain = null;


    /**
     * Returns the domain
     *
     * @return string|null
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }


    /**
     * Sets the domain
     *
     * @param string|null $domain
     *
     * @return static
     */
    public function setDomain(?string $domain): static
    {
        $this->domain = get_null($domain);
        return $this;
    }
}
