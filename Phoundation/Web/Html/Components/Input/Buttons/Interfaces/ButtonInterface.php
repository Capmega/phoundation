<?php

namespace Phoundation\Web\Html\Components\Input\Buttons\Interfaces;

use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Stringable;

interface ButtonInterface
{
    /**
     * Set if the button is floating or not
     *
     * @param bool $floating
     *
     * @return Button
     */
    public function setFloating(bool $floating): static;


    /**
     * Returns if the button is floating or not
     *
     * @return bool
     */
    public function getFloating(): bool;


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static;


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $value
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = true): static;


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string;
}