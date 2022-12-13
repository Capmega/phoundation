<?php

namespace Phoundation\Web\Http\Html\Elements;



/**
 * Buttons class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Buttons extends ElementsBlock
{
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
     * @param Button|null $button
     * @return static
     */
    public function addButton(?Button $button): static
    {
        if ($button) {
            $this->buttons[] = $button;
        }

        return $this;
    }



    /**
     * Creates a new button with defaults and adds it to button list
     *
     * @param string $content
     * @param string $type
     * @return static
     */
    public function createButton(string $content, string $type): static
    {
        $button = Button::new()
            ->setButtonType($type)
            ->setWrapping($this->wrapping)
            ->setOutlined($this->outlined)
            ->setRounded($this->rounded)
            ->addClasses($this->classes)
            ->setContent($content);

        $this->addButton($button);

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
     * Renders and returns the buttons HTML
     */
    abstract public function render(): string;
}