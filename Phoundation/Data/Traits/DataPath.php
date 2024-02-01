<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait DataPath
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataPath
{
    /**
     * The path to use
     *
     * @var string|null $path
     */
    protected ?string $path = null;


    /**
     * Returns the path
     *
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }


    /**
     * Sets the path
     *
     * @param string|null $path
     * @return static
     */
    public function setPath(?string $path): static
    {
        $this->path = $path;
        return $this;
    }
}
