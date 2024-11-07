<?php

/**
 * Trait TraitDataMethod
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


trait TraitDataMethod
{
    /**
     * The method for this object
     *
     * @var string|null $method
     */
    protected ?string $method = null;


    /**
     * Returns the method data
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }


    /**
     * Sets the method data
     *
     * @param string $method
     *
     * @return static
     */
    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }
}
