<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


/**
 * Trait TraitDataType
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataType
{
    /**
     * The type for this object
     *
     * @var string|null $type
     */
    protected ?string $type = null;


    /**
     * Returns the type
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * Sets the type
     *
     * @param string|null $type
     *
     * @return static
     */
    public function setType(?string $type): static
    {
        $this->type = get_null($type);
        return $this;
    }
}
