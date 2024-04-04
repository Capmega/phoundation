<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Interfaces\TemplateRendererInterface;


/**
 * Class TemplateRenderer
 *
 * This class contains basic template render functionalities. All template classes must extend this class!
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
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
     * @var RenderInterface $component
     */
    protected RenderInterface $component;

    /**
     * The parent render function
     *
     * @var mixed $render_function
     */
    protected mixed $render_function;


    /**
     * Renderer class element
     *
     * @param RenderInterface $component
     */
    public function __construct(RenderInterface $component)
    {
        $this->component = $component;
    }


    /**
     * Returns a new renderer object
     *
     * @param RenderInterface $component
     *
     * @return $this
     */
    public static function new(RenderInterface $component): static
    {
        return new static($component);
    }

    /**
     * Ensures that the specified class is a Renderer subclass
     *
     * @param string $class
     * @param object $object
     *
     * @return void
     */
    public static function ensureClass(string $class, object $object): void
    {
        if (!is_subclass_of($class, TemplateRenderer::class)) {
            throw new OutOfBoundsException(tr('Cannot render class ":class", the render class ":render" is not a sub class of ":main"', [
                ':class'  => get_class($object),
                ':render' => $class,
                ':main'   => TemplateRenderer::class,
            ]));
        }
    }

    /**
     * Sets the parent rendering function
     *
     * @param callable $render_function
     *
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
     * Returns the component to be rendered
     *
     * @return RenderInterface
     */
    public function getComponent(): RenderInterface
    {
        return $this->component;
    }

    /**
     * Sets the component to be rendered
     *
     * @param RenderInterface $component
     *
     * @return static
     */
    public function setComponent(RenderInterface $component): static
    {
        $this->component = $component;
        return $this;
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