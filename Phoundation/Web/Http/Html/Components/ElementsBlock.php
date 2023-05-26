<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Iterator;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Interfaces\InterfaceElementsBlock;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Page;
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
abstract class ElementsBlock implements Iterator, InterfaceElementsBlock
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
     * The data source of this object
     *
     * @var array $source
     */
    protected array $source = [];


    /**
     * Returns the rendered version of this element
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }


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
     * Returns the form of this objects block
     *
     * @return Form|null
     */
    public function getForm(): ?Form
    {
        return $this->form;
    }


    /**
     * Returns the form of this objects block
     *
     * @param Form|null $form
     * @return static
     */
    public function setForm(?Form $form): static
    {
        $this->form = $form;
        return $this;
    }


    /**
     * Returns the source of this object
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }


    /**
     * Returns the specified entry from the source of this object
     *
     * @param string|int $entry
     * @return mixed
     */
    public function getSourceEntry(string|int $entry): mixed
    {
        return isset_get($this->source[$entry]);
    }


    /**
     * Sets the data source of this object
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(?array $source): static
    {
        $this->source = [];
        return $this->addSource($source);
    }


    /**
     * Sets the data source of this object
     *
     * @param array|null $source
     * @return $this
     */
    public function addSource(?array $source): static
    {
        foreach ($source as $key => $value) {
            $this->addSourceEntry($key, $value);
        }

        return $this;
    }


    /**
     * Adds a single entry to the source of this object
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addSourceEntry(string $key, mixed $value): static
    {
        $this->source[$key] = $value;
        return $this;
    }


    /**
     * Renders and returns the HTML for this object using the template renderer if avaialable
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see InterfaceElement::render()
     */
    public function render(): ?string
    {
        $renderer_class = Page::getTemplate()->getRendererClass($this);

        $render_function = function (?string $render = null) {
            if ($this->form) {
                $this->form->setContent($render);
                return $this->form->render();
            }

            $this->render = null;

            return $render;
        };

        if ($renderer_class) {
            Renderer::ensureClass($renderer_class, $this);

            return $renderer_class::new($this)
                ->setParentRenderFunction($render_function)
                ->render();
        }

        // The template component does not exist, return the basic Phoundation version
        Log::warning(tr('No template render class found for block component ":component", rendering basic HTML', [
            ':component' => get_class($this)
        ]));

        return $render_function($this->render);
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
     * Returns the specified item
     *
     * @param string|int $key
     * @return mixed
     */
    #[ReturnTypeWillChange] public function get(string|int $key): mixed
    {
        if (array_key_exists($key, $this->source)) {
            return $this->source[$key];
        }

        throw new OutOfBoundsException(tr('The specified source key ":key" does not exist', [
            ':key' => $key
        ]));
    }


    /**
     * Returns if the specified key exists or not
     *
     * @param string|int $key
     * @return bool
     */
    public function exists(string|int $key): bool
    {
        return array_key_exists($key, $this->source);
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