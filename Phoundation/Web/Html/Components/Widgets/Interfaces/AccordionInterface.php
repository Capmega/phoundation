<?php

namespace Phoundation\Web\Html\Components\Widgets\Interfaces;

use Phoundation\Web\Html\Components\Widgets\Accordion;
use Stringable;

interface AccordionInterface extends WidgetInterface
{
    /**
     * Returns if this accordion should use selectors or not
     *
     * @return bool
     */
    public function getSelectors(): bool;

    /**
     * Sets if this accordion should use selectors or not
     *
     * @param bool $selectors
     *
     * @return $this
     */
    public function setSelectors(bool $selectors): static;

    /**
     * Returns the key of the accordion element that is open
     *
     * @return Stringable|string|float|int|null $open
     */
    public function getOpen(): Stringable|string|float|int|null;

    /**
     * Sets the key of the accordion element that is open
     *
     * @param Stringable|string|float|int|null $open
     *
     * @return $this
     */
    public function setOpen(Stringable|string|float|int|null $open): static;

    /**
     * @inheritDoc
     */
    public function render(): ?string;
}
