<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons\Interfaces;

use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
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
     * @param RenderInterface|callable|string|float|int|null $content
     * @param bool                                           $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;


    /**
     * Set the content for this button
     *
     * @param Stringable|string|float|int|null $value
     * @param bool                             $make_safe
     *
     * @return static
     * @todo add documentation for when button is floating as it is unclear what is happening there
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = false): static;


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string;

    /**
     * Returns if the button is disabled after a mouse click, or not
     *
     * @return bool
     */
    public function getDisableAfterClick(): bool;

    /**
     * Set if the button is disabled after a mouse click, or not
     *
     * @param bool $disable_after_click
     *
     * @return Button
     */
    public function setDisableAfterClick(bool $disable_after_click): static;

    /**
     * Returns if the button is disabled and requires one or more keys down to enable
     *
     * @return ?array
     */
    public function getRequireKeysToEnable(): ?array;

    /**
     * Sets if the button is disabled and requires one or more keys down to enable
     *
     * @param array|null  $keys         The buttons that need to be pressed down to enable the button
     * @param string|null $class [null] If specified, the JavaScript code will apply this for all elements with that class. If not, the JavaScript will apply to
     *                                  the unique button ID
     *
     * @return Button
     */
    public function setRequireKeysToEnable(?array $keys, ?string $class = null): static;

    /**
     * Returns if the button is disabled and requires one or more keys down to enable
     *
     * @return string|null
     */
    public function getRequireKeysToEnableClass(): ?string;
}
