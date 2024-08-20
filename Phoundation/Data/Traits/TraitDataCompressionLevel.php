<?php

/**
 * Trait TraitDataCompressionLevel
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataCompressionLevel
{
    /**
     * @var int $CompressionLevel
     */
    protected int $compression_level;


    /**
     * Returns the source
     *
     * @return int
     */
    public function getCompressionLevel(): int
    {
        return $this->compression_level;
    }


    /**
     * Sets the source
     *
     * @param int $CompressionLevel
     *
     * @return static
     */
    public function setCompressionLevel(int $CompressionLevel): static
    {
        $this->compression_level = $CompressionLevel;

        return $this;
    }
}
