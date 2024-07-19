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

trait TraitDataDateFormat
{
    use TraitDataFormat {
        setFormat as protected __setFormat;
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
        $this->format = str_replace('-', '/', $this->format);

        return $this;
    }
}
