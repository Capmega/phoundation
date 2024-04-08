<?php

declare(strict_types=1);

namespace Phoundation\Filesystem\Traits;

/**
 * Trait TraitDataSize
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category  Function reference
 * @package   Phoundation\Filesystem
 */
trait TraitDataSize
{
    /**
     * Tracks the size to use
     *
     * @var int|null $size
     */
    protected ?int $size = null;


    /**
     * Returns the size
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }


    /**
     * Sets the size
     *
     * @param int|null $size
     *
     * @return static
     */
    public function setSize(?int $size = null): static
    {
        $this->size = $size;

        return $this;
    }
}
