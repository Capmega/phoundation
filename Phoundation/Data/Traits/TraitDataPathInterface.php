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

use Phoundation\Filesystem\Interfaces\FsPathInterface;


trait TraitDataPathInterface
{
    /**
     * The path to use
     *
     * @var FsPathInterface|null $path
     */
    protected ?FsPathInterface $path = null;


    /**
     * Returns the path
     *
     * @return FsPathInterface|null
     */
    public function getPath(): ?FsPathInterface
    {
        return $this->path;
    }


    /**
     * Sets the path
     *
     * @param FsPathInterface|null $path
     *
     * @return static
     */
    public function setPath(FsPathInterface|null $path): static
    {
        $this->path = $path;

        return $this;
    }
}
