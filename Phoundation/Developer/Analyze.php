<?php

/**
 * Analyze class
 *
 *
 * @see https://www.exakat.io/en/php-7-static-analysis-tools/
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */

namespace Phoundation\Developer;

use Phoundation\Data\Traits\TraitDataPathInterface;
use Phoundation\Filesystem\FsPath;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsPathInterface;

class Analyze
{
    use TraitDataPathInterface {
        setPath as protected __setPath;
    }


    /**
     * Analyze class constructor
     */
    public function __construct(FsPathInterface|string|null $path = null)
    {
        $this->setPath($path);
    }


    /**
     * Sets the path
     *
     * @param FsPathInterface|null $path
     *
     * @return static
     */
    public function setPath(FsPathInterface|null $path = null): static
    {
        if (!$path) {
            // Default to the root directory of this project
            $path = new FsPath(DIRECTORY_ROOT, FsRestrictions::getReadonly(DIRECTORY_ROOT));
        }

        return $this->__setPath($path);
    }


    /**
     *
     *
     * @return $this
     */
    public function all(): static
    {
    }


    /**
     *
     *
     * @return $this
     */
    public function phpStan(): static
    {
    }


    /**
     *
     *
     * @return $this
     */
    public function phan(): static
    {
    }


    /**
     *
     *
     * @return $this
     */
    public function tuli(): static
    {
    }


    /**
     *
     *
     * @return $this
     */
    public function exaKat(): static
    {
    }
}
