<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Page;


/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Element implements ElementInterface
{
    use ElementAttributes;


    /**
     * The element type
     *
     * @var string $element
     */
    protected string $element;

    /**
     * If true, will produce <element></element> instead of <element />
     *
     * @var bool $requires_closing_tag
     */
    protected bool $requires_closing_tag = true;


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
     * Sets the type of element to display
     *
     * @param string $element
     * @return static
     */
    public function setElement(string $element): static
    {
        $this->requires_closing_tag = match ($element) {
            'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' => false,
            default => true,
        };

        $this->element = $element;
        return $this;
    }


    /**
     * Returns the HTML class element attribute
     *
     * @return string
     */
    public function getElement(): string
    {
        return $this->element;
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
     * @see ElementsBlock::render()
     */
    public function render(): ?string
    {
        if (!$this->element) {
            throw new OutOfBoundsException(tr('Cannot render HTML element, no element type specified'));
        }

        $renderer_class  = Page::getTemplate()->getRendererClass($this);

        $render_function = function () {
            $attributes  = $this->buildAttributes();
            $attributes  = Arrays::implodeWithKeys($attributes, ' ', '=', '"', Arrays::FILTER_NULL | Arrays::QUOTE_ALWAYS | Arrays::FILTER_NULL);
            $attributes .= $this->extra;

            if ($attributes) {
                $attributes = ' ' . $attributes;
            }

            $this->render = '<' . $this->element . $attributes;

            if ($this->requires_closing_tag) {
                return $this->render . '>' . $this->content . '</' . $this->element . '>';

            }

            $render       = $this->render . ' />';
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
        Log::warning(tr('No template render class found for element component ":component", rendering basic HTML', [
            ':component' => get_class($this)
        ]), 4);

        return $render_function();
    }


    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function buildClassString(): ?string
    {
        $class = $this->getClass();

        if ($class) {
            return ' class="' . $class . '"';
        }

        return null;
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildAttributes(): array
    {
        $return = [
            'id'        => $this->id,
            'name'      => $this->name,
            'class'     => $this->getClass(),
            'height'    => $this->height,
            'width'     => $this->width,
            'autofocus' => ((self::$autofocus === $this->id) ? 'autofocus' : null),
            'readonly'  => ($this->readonly ? 'readonly' : null),
            'disabled'  => ($this->disabled ? 'disabled' : null),
        ];

        // Remove empty entries
        foreach ($return as $key => $value) {
            if ($value === null) {
                unset($return[$key]);
            }
        }

        // Add data-* entries
        foreach ($this->data as $key=> $value) {
            $return['data-' . $key] = $value;
        }

        // Add aria-* entries
        foreach ($this->aria as $key=> $value) {
            $return['aria-' . $key] = $value;
        }

        // Merge the system values over the set attributes
        return array_merge($this->attributes, $return);
    }
}