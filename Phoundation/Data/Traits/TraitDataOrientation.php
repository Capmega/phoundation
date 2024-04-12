<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Enums\EnumOrientation;

/**
 * Trait TraitDataOrientation
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataOrientation
{
    /**
     * @var EnumOrientation|null $orientation
     */
    protected ?EnumOrientation $orientation = null;


    /**
     * Returns the orientation
     *
     * @return EnumOrientation|null
     */
    public function getOrientation(): ?EnumOrientation
    {
        return $this->orientation;
    }


    /**
     * Sets the orientation
     *
     * @param EnumOrientation|null $orientation
     *
     * @return static
     */
    public function setOrientation(?EnumOrientation $orientation): static
    {
        $this->orientation = $orientation;

        return $this;
    }
}