<?php

namespace Phoundation\Web\Html\Components\Input\Buttons\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;

interface ButtonsInterface
{
    /**
     * Sets the buttons list
     *
     * @param ArrayableInterface|array $buttons
     *
     * @return static
     */
    public function setButtons(ArrayableInterface|array $buttons): static;


    /**
     * Adds multiple buttons to button list
     *
     * @param ArrayableInterface|array $buttons
     *
     * @return static
     */
    public function addButtons(ArrayableInterface|array $buttons): static;


    /**
     * Adds a single button to button list
     *
     * @param Button|string|null              $button
     * @param EnumDisplayMode                 $mode
     * @param EnumInputType|Stringable|string $type_or_anchor_url
     * @param bool                            $outline
     * @param bool                            $right
     *
     * @return static
     */
    public function addButton(Button|string|null $button, EnumDisplayMode $mode = EnumDisplayMode::primary, EnumButtonType|Stringable|string $type_or_anchor_url = EnumButtonType::submit, bool $outline = false, bool $right = false): static;


    /**
     * Returns the buttons list
     *
     * @return array
     */
    public function getButtons(): array;


    /**
     * Sets the button grouping
     *
     * @param bool $group
     *
     * @return static
     */
    public function setGroup(bool $group): static;


    /**
     * Returns the button grouping
     *
     * @return bool
     */
    public function getGroup(): bool;


    /**
     * Returns the current button
     *
     * @return Button
     */
    public function current(): Button;


    /**
     * Progresses the internal pointer to the next button
     *
     * @return void
     */
    public function next(): void;


    /**
     * Returns the current key for the current button
     *
     * @return string
     */
    public function key(): string;


    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool;


    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void;
}