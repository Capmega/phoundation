<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Iterator;
use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\InputTypeInterface;
use Phoundation\Web\Html\Enums\ButtonType;
use Phoundation\Web\Html\Enums\DisplayMode;
use ReturnTypeWillChange;
use Stringable;


/**
 * Buttons class
 *
 * This class manages and can render a set of multiple buttons
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Buttons extends ElementsBlock implements Iterator
{
    use ButtonProperties;


    /**
     * If true, the buttons will be grouped in one larger button
     *
     * @var bool $group
     */
    protected bool $group = false;


    /**
     * Sets the buttons list
     *
     * @param array $buttons
     * @return static
     */
    public function setButtons(array $buttons): static
    {
        $this->source = [];
        return $this->addButtons($buttons);
    }


    /**
     * Adds multiple buttons to button list
     *
     * @param ArrayableInterface|array $buttons
     * @return static
     */
    public function addButtons(ArrayableInterface|array $buttons): static
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
     * @param DisplayMode $mode
     * @param InputTypeInterface|Stringable|string $type_or_anchor_url
     * @param bool $outline
     * @param bool $right
     * @return static
     */
    public function addButton(Button|string|null $button, DisplayMode $mode = DisplayMode::primary, InputTypeInterface|Stringable|string $type_or_anchor_url = ButtonType::submit, bool $outline = false, bool $right = false): static
    {
        if (!$button) {
            // Don't add anything
            return $this;
        }

        if (is_string($button)) {
            if ($button === tr('Save')) {
                $type_or_anchor_url = ButtonType::submit;
            }

            // Button was specified as string, create a button first
            $button = Button::new()
                ->setWrapping($this->wrapping)
                ->setOutlined($this->outlined)
                ->setRounded($this->rounded)
                ->addClasses($this->classes)
                ->setOutlined($outline)
                ->setContent($button)
                ->setValue($button)
                ->setFloatRight($right)
                ->setMode($mode)
                ->setName('submit');

            switch ($type_or_anchor_url) {
                case ButtonType::submit:
                // no break
                case ButtonType::button:
                // no break
                case ButtonType::reset:
                    // One of the submit, reset, or button buttons
                    $button->setType($type_or_anchor_url);
                    break;

                default:
                    // This is a URL button, place an anchor with href instead
                    $button->setAnchorUrl($type_or_anchor_url);
            }

        }

        if (empty($button->getValue())) {
            if (empty($button->getContent())) {
                throw new OutOfBoundsException(tr('No name specified for button ":button"', [
                    ':button' => $button
                ]));
            }

            $this->source[$button->getContent()] = $button;
        } else {
            $this->source[$button->getValue()] = $button;
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
        return $this->source;
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
    #[ReturnTypeWillChange] public function current(): Button
    {
        return current($this->source);
    }


    /**
     * Progresses the internal pointer to the next button
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static
    {
        next($this->source);
        return $this;
    }


    /**
     * Returns the current key for the current button
     *
     * @return string
     */
    #[ReturnTypeWillChange] public function key(): string
    {
        return key($this->source);
    }


    /**
     * Returns if the current pointer is valid or not
     *
     * @todo Is this really really required? Since we're using internal array pointers anyway, it always SHOULD be valid
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->source[key($this->source)]);
    }


    /**
     * Rewinds the internal pointer
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static
    {
        reset($this->source);
        return $this;

    }
}