<?php

namespace Phoundation\Web\Html\Components\Widgets\Interfaces;

interface ProgressBarInterface extends WidgetInterface
{
    /**
     * @inheritDoc
     */
    public function render(): ?string;

    /**
     * Returns the maximum value
     *
     * @return int|null
     */
    public function getMaximum(): ?int;

    /**
     * Sets the maximum value
     *
     * @param int|null $maximum
     *
     * @return static
     */
    public function setMaximum(?int $maximum): static;

    /**
     * Returns the minimum value
     *
     * @return int|null
     */
    public function getMinimum(): ?int;

    /**
     * Sets the minimum value
     *
     * @param int|null $minimum
     *
     * @return static
     */
    public function setMinimum(?int $minimum): static;

    /**
     * Returns the label
     *
     * @return string|null
     */
    public function getLabel(): ?string;

    /**
     * Sets the label
     *
     * @param string|null $label
     *
     * @return static
     */
    public function setLabel(?string $label): static;

    /**
     * Returns the current value
     *
     * @return float|null
     */
    public function getCurrent(): ?float;

    /**
     * Sets the current value
     *
     * @param floatnull $current
     *
     * @return static
     */
    public function setCurrent(?float $current): static;
}
