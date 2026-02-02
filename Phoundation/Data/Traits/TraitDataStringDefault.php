<?php

/**
 * Trait TraitDataDefault
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


trait TraitDataStringDefault
{
    /**
     * @var string|null $default
     */
    protected ?string $default;


    /**
     * Returns the source
     *
     * @return string|null
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }


    /**
     * Sets the source
     *
     * @param string|null $default
     *
     * @return static
     */
    public function setDefault(?string $default): static
    {
        $this->default = get_null($default);
        return $this;
    }
}
