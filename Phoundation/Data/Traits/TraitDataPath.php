<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PathInterface;

/**
 * Trait TraitDataPath
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openpath.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataPath
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
     * @param PathInterface|string|null $path
     *
     * @return static
     */
    public function setPath(PathInterface|string|null $path): static
    {
        $this->path = get_null((string) $path);

        return $this;
    }
}
