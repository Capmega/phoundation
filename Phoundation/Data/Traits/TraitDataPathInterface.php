<?php

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

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Filesystem\Interfaces\PathInterface;
use Phoundation\Filesystem\Path;

trait TraitDataPathInterface
{
    /**
     * The path to use
     *
     * @var PathInterface|null $path
     */
    protected ?PathInterface $path = null;


    /**
     * Returns the path
     *
     * @return PathInterface|null
     */
    public function getPath(): ?PathInterface
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
        $path = get_null($path);

        if ($path) {
            $path = new Path($path);
        }

        $this->path = $path;

        return $this;
    }
}
