<?php

/**
 * Trait TraitDataNamespace
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opennamespace.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Traits;

trait TraitDataNamespace
{
    /**
     * Namespace
     *
     * @var string|null $namespace
     */
    protected ?string $namespace = null;


    /**
     * Returns the namespace
     *
     * @return string|null
     */
    public function getNamespace(): ?string
    {
        return $this->namespace;
    }


    /**
     * Sets the namespace
     *
     * @param string|null $namespace
     *
     * @return static
     */
    public function setNamespace(?string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }
}