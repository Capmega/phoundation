<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Element;
use Phoundation\Web\Http\Html\Components\ElementsBlock;

/**
 * Class Renderer
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Renderer
{
    /**
     * The rendered HTML, so far
     *
     * @var string|null $render
     */
    protected ?string $render = null;

    /**
     * The element to render
     *
     * @var ElementsBlock|Element $element
     */
    protected ElementsBlock|Element $element;

    /**
     * The parent render function
     *
     * @var mixed $render_function
     */
    protected mixed $render_function;


    /**
     * Renderer class element
     *
     * @param ElementsBlock|Element $element
     */
    public function __construct(ElementsBlock|Element $element)
    {
        $this->element = $element;
    }


    /**
     * Returns new renderer object
     *
     * @param ElementsBlock|Element $element
     * @return $this
     */
    public static function new(ElementsBlock|Element $element): static
    {
        return new static($element);
    }


    /**
     * Sets the parent rendering function
     *
     * @param callable $render_function
     * @return static
     */
    public function setParentRenderFunction(callable $render_function): static
    {
        $this->render_function = $render_function;
        return $this;
    }


    /**
     * Returns the parent rendering function
     *
     * @return callable
     */
    public function getParentRenderFunction(): callable
    {
        return $this->render_function;
    }


    /**
     * Sets the element to be rendered
     *
     * @param ElementsBlock|Element $element
     * @return static
     */
    public function setElement(ElementsBlock|Element $element): static
    {
        $this->element = $element;
        return $this;
    }


    /**
     * Returns the element to be rendered
     *
     * @return ElementsBlock|Element
     */
    public function getElement(): ElementsBlock|Element
    {
        return $this->element;
    }


    /**
     * Ensures that the specified class is a Renderer subclass
     *
     * @param string $class
     * @param object $object
     * @return void
     */
    public static function ensureClass(string $class, object $object): void
    {
        if (!is_subclass_of($class, Renderer::class)) {
            throw new OutOfBoundsException(tr('Cannot render class ":class", the render class ":render" is not a sub class of ":main"', [
                ':class'  => get_class($object),
                ':render' => $class,
                ':main'   => Renderer::class
            ]));
        }
    }


    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $function = $this->render_function;
        return $function($this->render);
    }
}