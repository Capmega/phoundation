<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

/**
 * Trait DataTarget
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataTarget
{
    protected ?string $target;

    /**
     * Returns the source
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }


    /**
     * Sets the source
     *
     * @param string|null $target
     * @return static
     */
    public function setTarget(?string $target): static
    {
        $this->target = $target;
        return $this;
    }
}