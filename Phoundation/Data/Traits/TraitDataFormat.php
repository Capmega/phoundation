<?php

/**
 * Trait TraitDataFormat
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openformat.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */

declare(strict_types=1);

namespace Phoundation\Data\Traits;

trait TraitDataFormat
{
    /**
     * The format to use
     *
     * @var string|null $format
     */
    protected ?string $format = null;


    /**
     * Returns the format
     *
     * @return string|null
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }


    /**
     * Sets the format
     *
     * @param string|null $format
     *
     * @return static
     */
    public function setFormat(string|null $format): static
    {
        $this->format = $format;

        return $this;
    }
}
