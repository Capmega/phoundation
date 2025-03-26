<?php

/**
 * Class TemplateRenderer
 *
 * This class contains basic template render functionalities. All template classes must extend this class!
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Template;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Interfaces\ComponentInterface;
use Phoundation\Web\Html\Interfaces\TemplateRendererInterface;
use Phoundation\Web\Traits\TraitDataComponent;


class TemplateRenderer implements TemplateRendererInterface
{
    use TraitDataComponent;


    /**
     * The rendered HTML, so far
     *
     * @var string|null $render
     */
    protected ?string $render = null;

    /**
     * The parent render function
     *
     * @var mixed $render_function
     */
    protected mixed $render_function;


    /**
     * Renderer class element
     *
     * @param ComponentInterface|null $o_component
     */
    public function __construct(ComponentInterface|null $o_component)
    {
        $this->o_component = $o_component;
    }


    /**
     * Returns a new renderer object
     *
     * @param ComponentInterface|null $component
     *
     * @return static
     */
    public static function new(?ComponentInterface $component): static
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
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Use the supplied render function
        return ($this->render_function)($this->render);
    }
}
