<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Enums\Interfaces\EnumOrientationInterface;


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
     * @var EnumOrientationInterface|null $orientation
     */
    protected ?EnumOrientationInterface $orientation = null;


    /**
     * Returns the orientation
     *
     * @return EnumOrientationInterface|null
     */
    public function getOrientation(): ?EnumOrientationInterface
    {
        return $this->orientation;
    }


    /**
     * Sets the orientation
     *
     * @param EnumOrientationInterface|null $orientation
     *
     * @return static
     */
    public function setOrientation(?EnumOrientationInterface $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }
}