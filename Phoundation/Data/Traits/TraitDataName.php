<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataName
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openname.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataName
{
    /**
     * The name to use
     *
     * @var string|null $name
     */
    protected ?string $name = null;


    /**
     * Returns the name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * Sets the name
     *
     * @param string|null $name
     * @return static
     */
    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }
}
