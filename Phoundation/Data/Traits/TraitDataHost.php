<?php

/**
 * Trait TraitDataHost
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataHost
{
    /**
     * The host for this object
     *
     * @var string|null $host
     */
    protected ?string $host = null;


    /**
     * Returns the host
     *
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }


    /**
     * Sets the host
     *
     * @param string|null $host
     *
     * @return static
     */
    public function setHost(?string $host): static
    {
        $this->host = get_null($host);

        return $this;
    }
}
