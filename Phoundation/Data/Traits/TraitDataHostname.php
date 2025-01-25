<?php

/**
 * Trait TraitDataHostname
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


trait TraitDataHostname
{
    /**
     * The hostname for this object
     *
     * @var string|null $hostname
     */
    protected ?string $hostname = null;


    /**
     * Returns the hostname for this object
     *
     * @return string|null
     */
    public function getHostname(): ?string
    {
        return $this->hostname;
    }


    /**
     * Sets the hostname for this object
     *
     * @param string|null $hostname
     *
     * @return static
     */
    public function setHostname(?string $hostname): static
    {
        $this->hostname = $hostname;

        return $this;
    }
}
