<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumOrientation;

interface TabsInterface extends ElementsBlockInterface
{
    /**
     * Returns the orientation
     *
     * @return EnumOrientation|null
     */
    public function getOrientation(): ?EnumOrientation;


    /**
     * Sets the orientation
     *
     * @param EnumOrientation|null $orientation
     *
     * @return static
     */
    public function setOrientation(?EnumOrientation $orientation): static;
}