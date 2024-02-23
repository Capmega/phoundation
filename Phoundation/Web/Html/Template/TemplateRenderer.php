<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Interfaces\TemplateRendererInterface;


/**
 * Class TemplateRenderer
 *
 * This class contains basic template render functionalities. All template classes must extend this class!
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TemplateRenderer implements TemplateRendererInterface
{
    /**
     * The rendered HTML, so far
     *
     * @var string|null $render
     */
    protected ?string $render = null;

    /**
     * The object to render
     *
     * @var ElementsBlockInterface|ElementInterface $render_object
     */
    protected ElementsBlockInterface|ElementInterface $render_object;

    /**
     * The parent render function
     *
     * @var mixed $render_function
     */
    protected mixed $render_function;


    /**
     * Renderer class element
     *
     * @param ElementsBlockInterface|ElementInterface $render_object
     */
    public function __construct(ElementsBlockInterface|ElementInterface $render_object)
    {
        $this->render_object = $render_object;
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
     * @param ElementsBlockInterface|ElementInterface $render_object
     * @return static
     */
    public function setRenderobject(ElementsBlockInterface|ElementInterface $render_object): static
    {
        $this->render_object = $render_object;
        return $this;
    }


    /**
     * Returns the element to be rendered
     *
     * @return ElementsBlockInterface|ElementInterface
     */
    public function getRenderobject(): ElementsBlockInterface|ElementInterface
    {
        return $this->render_object;
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
        if (!is_subclass_of($class, TemplateRenderer::class)) {
            throw new OutOfBoundsException(tr('Cannot render class ":class", the render class ":render" is not a sub class of ":main"', [
                ':class'  => get_class($object),
                ':render' => $class,
                ':main'   => TemplateRenderer::class
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