<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataPrefix
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataPrefix
{
    /**
     * The prefix string 
     *
     * @var string|null $prefix
     */
    protected ?string $prefix = null;


    /**
     * Returns the source
     *
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }


    /**
     * Sets the source
     *
     * @param string|null $prefix
     * @return static
     */
    public function setPrefix(?string $prefix): static
    {
        $this->prefix = $prefix;
        return $this;
    }
}