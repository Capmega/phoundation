<?php

/**
 * Analyze class
 *
 *
 * @see https://www.exakat.io/en/php-7-static-analysis-tools/
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   \Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer;

use Phoundation\Data\Traits\TraitDataPathInterface;
use Phoundation\Filesystem\PhoPath;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;


class Analyze
{
    use TraitDataPathInterface {
        setPath as protected __setPath;
    }


    /**
     * Analyze class constructor
     */
    public function __construct(PhoPathInterface|string|null $path = null)
    {
        $this->setPath($path);
    }


    /**
     * Sets the path
     *
     * @param PhoPathInterface|null $path
     *
     * @return static
     */
    public function setPath(PhoPathInterface|null $path = null): static
    {
        if (!$path) {
            // Default to the root directory of this project
            $path = new PhoPath(DIRECTORY_ROOT, PhoRestrictions::newReadonlyObject(DIRECTORY_ROOT));
        }

        return $this->__setPath($path);
    }


    /**
     *
     *
     * @return static
     */
    public function all(): static
    {
    }


    /**
     *
     *
     * @return static
     */
    public function phpStan(): static
    {
    }


    /**
     *
     *
     * @return static
     */
    public function phan(): static
    {
    }


    /**
     *
     *
     * @return static
     */
    public function tuli(): static
    {
    }


    /**
     *
     *
     * @return static
     */
    public function exaKat(): static
    {
    }
}
