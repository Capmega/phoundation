<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

interface SpanInterface extends ElementInterface
{
    /**
     * Returns the parent for this anchor
     *
     * @return ElementInterface|null
     */
    public function getChildElement(): ?ElementInterface;


    /**
     * Sets the parent for this anchor
     *
     * @param ElementInterface|null $parent
     *
     * @return static
     */
    public function setChildElement(?ElementInterface $parent): static;
}
