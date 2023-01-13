<?php

namespace Phoundation\Web\Http\Html\Components;

use Iterator;
use JetBrains\PhpStorm\ExpectedValues;



/**
 * Buttons class
 *
 * This class manages and can render a set of multiple buttons
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Buttons extends ElementsBlock implements Iterator
{
    use ButtonProperties;



    /**
     * The buttons to render
     *
     * @var array $buttons
     */
    protected array $buttons = [];

    /**
     * If true, the buttons will be grouped in one larger button
     *
     * @var bool $group
     */
    protected bool $group = false;



    /**
     * Clears the buttons list
     *
     * @return static
     */
    public function clearButtons(): static
    {
        $this->buttons = [];
        return $this;
    }



    /**
     * Sets the buttons list
     *
     * @param array $buttons
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = [];
        return $this->addButtons($buttons);
    }



    /**
     * Adds multiple buttons to button list
     *
     * @param array $buttons
     * @return static
     */
    public function addButtons(array $buttons): static
    {
        foreach ($buttons as $button) {
            $this->addButton($button);
        }

        return $this;
    }



    /**
     * Adds a single button to button list
     *
     * @param Button|string|null $button
     * @param string $kind
     * @param string|null $type_or_anchor_url
     * @param bool $outline
     * @param bool $right
     * @return static
     */
    public function addButton(Button|string|null $button, #[ExpectedValues(values: ['success', 'info', 'warning', 'danger', 'primary', 'secondary', 'tertiary', 'link', 'light', 'dark'])] string $kind = 'primary', ?string $type_or_anchor_url = null, bool $outline = false, bool $right = false): static
    {
        if (is_string($button)) {
            // Button was specified as string, create a button first
            $button = Button::new()
                ->setWrapping($this->wrapping)
                ->setOutlined($this->outlined)
                ->setRounded($this->rounded)
                ->addClasses($this->classes)
                ->setOutlined($outline)
                ->setContent($button)
                ->setRight($right)
                ->setKind($kind);

            switch ($type_or_anchor_url) {
                case null:
                    // Use default button
                    break;

                case 'submit':
                // no break
                case 'button':
                // no break
                case 'reset':
                    // One of the submit, reset or button buttons
                    $button->setType($type_or_anchor_url);
                    break;

                default:
                    // This is a URL button, place an anchor with href instead
                    $button->setAnchorUrl($type_or_anchor_url);
            }

        }

        if ($button) {
            $this->buttons[] = $button;
        }

        return $this;
    }



    /**
     * Returns the buttons list
     *
     * @return array
     */
    public function getButtons(): array
    {
        return $this->buttons;
    }



    /**
     * Sets the button grouping
     *
     * @param bool $group
     * @return static
     */
    public function setGroup(bool $group): static
    {
        $this->group = $group;
        return $this;
    }



    /**
     * Returns the button grouping
     *
     * @return bool
     */
    public function getGroup(): bool
    {
        return $this->group;
    }



    /**
     * Returns the current button
     *
     * @return Button
     */
    public function current(): Button
    {
        return current($this->buttons);
    }



    /**
     * Progresses the internal pointer to the next button
     *
     * @return void
     */
    public function next(): void
    {
        next($this->buttons);
    }



    /**
     * Returns the current key for the current button
     *
     * @return string
     */
    public function key(): string
    {
        return key($this->buttons);
    }



    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->buttons[key($this->buttons)]);
    }



    /**
     * Rewinds the internal pointer
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->buttons);
    }
}