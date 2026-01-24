<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons\Interfaces;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Web\Html\Components\Input\Buttons\AuditButton;
use Phoundation\Web\Html\Components\Input\Buttons\Button;
use Phoundation\Web\Html\Components\Input\Buttons\DeleteButton;
use Phoundation\Web\Html\Components\Input\Buttons\UndeleteButton;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumInputType;
use Stringable;


interface ButtonsInterface extends ElementsBlockInterface
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
     * Adds a single button to buttons list
     *
     * @param ButtonInterface|DropdownButtonInterface|string|null $button
     * @param EnumDisplayMode                                     $mode
     * @param EnumButtonType|\Stringable|string                   $type_or_url
     * @param bool                                                $outline
     * @param bool                                                $float_right
     *
     * @return static
     */
    public function addButton(ButtonInterface|DropdownButtonInterface|string|null $button, EnumDisplayMode $mode = EnumDisplayMode::primary, EnumButtonType|Stringable|string $type_or_url = EnumButtonType::submit, bool $outline = false, bool $float_right = false): static;


    /**
     * Returns the buttons list
     *
     * @return array
     */
    public function getSource(): array;


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
     * @return ButtonInterface|DropdownButtonInterface
     */
    public function current(): ButtonInterface|DropdownButtonInterface;


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
     * @todo Is this really really required? Since we are using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool;


    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void;

    /**
     * Adds a single "Delete" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addDeleteButton(bool $float_right = false): static;

    /**
     * Adds a single "Undelete" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addUndeleteButton(bool $float_right = false): static;

    /**
     * Adds a single "Audit" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addAuditButton(bool $float_right = false): static;

    /**
     * Adds a single "Audit" button to the button list
     *
     * @param bool $float_right [false] If true, will add a float-right class to the button
     *
     * @return static
     */
    public function addLockButton(bool $float_right = false): static;
}
