<?php

namespace Phoundation\Web\Http\Html\Components;

use Iterator;
use ReturnTypeWillChange;



/**
 * Class ElementsBlock
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class ElementsBlock implements Iterator
{
    use ElementAttributes;



    /**
     * Indicates if flash messages were rendered (and then we can assume, sent to client too)
     *
     * @var bool
     */
    protected bool $has_rendered = false;



    /**
     * A form around this element block
     *
     * @var Form|null
     */
    protected ?Form $form = null;

    /**
     * The data source for this element
     *
     * @var array $source
     */
    protected array $source = [];



    /**
     * Returns the contents of this object as an array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return $this->source;
    }



    /**
     * Sets the content of the element to display
     *
     * @param bool $use_form
     * @return static
     */
    public function useForm(bool $use_form): static
    {
        if ($use_form) {
            if (!$this->form) {
                $this->form = Form::new();
            }
        } else {
            $this->form = null;
        }

        return $this;
    }



    /**
     * Returns the form for this elements block
     *
     * @return Form
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $this->form = Form::new();
        }

        return $this->form;
    }



    /**
     * Returns the source for this element
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }



    /**
     * Sets the data source for this element
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(?array $source): static
    {
        $this->source = $source;
        return $this;
    }



    /**
     * Render the ElementsBlock
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if ($this->form) {
            $this->form->setContent($this->render);
            return $this->form->render();
        }

        return $this->render;
    }



    /**
     * Returns if this FlashMessages object has rendered HTML or not
     *
     * @return bool
     */
    public function hasRendered(): bool
    {
        return $this->has_rendered;
    }



    /**
     * Clear all messages in this object
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->source = [];
        return $this;
    }



    /**
     * Return the amount of flash messages in this object
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->source);
    }



    /**
     * Returns the current item
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function current(): mixed
    {
        return $this->get(key($this->source));
    }



    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function next(): static
    {
        next($this->source);
        return $this;
    }



    /**
     * Jumps to the next element
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function previous(): static
    {
        prev($this->source);
        return $this;
    }



    /**
     * Returns the current iterator position
     *
     * @return mixed
     */
    #[ReturnTypeWillChange] public function key(): mixed
    {
        return key($this->source);
    }



    /**
     * Returns if the current element exists or not
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->source[key($this->source)]);
    }



    /**
     * Rewinds the internal pointer to 0
     *
     * @return static
     */
    #[ReturnTypeWillChange] public function rewind(): static
    {
        reset($this->source);
        return $this;
    }
}