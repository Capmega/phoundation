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

use Phoundation\Filesystem\Interfaces\PhoPathInterface;


trait TraitDataPath
{
    /**
     * The path to use
     *
     * @var PhoPathInterface|null $path
     */
    protected ?PhoPathInterface $path = null;


    /**
     * Returns the path
     *
     * @return PhoPathInterface|null
     */
    public function getPath(): ?PhoPathInterface
    {
        return $this->path;
    }


    /**
     * Sets the path
     *
     * @param PhoPathInterface|null $path
     *
     * @return static
     */
    public function setPath(PhoPathInterface|null $path): static
    {
        $this->path = $path;

        return $this;
    }
}
