<?php

/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Html\Template\TemplateRenderer;
use Phoundation\Web\Requests\Request;

abstract class Element implements ElementInterface
{
    use TraitElementAttributes;

    /**
     * The element type
     *
     * @var string|null $element
     */
    protected ?string $element;

    /**
     * If true, will produce <element></element> instead of <element />
     *
     * @var bool $requires_closing_tag
     */
    protected bool $requires_closing_tag = true;


    /**
     * ElementAttributes class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->classes    = new Iterator();
        $this->attributes = new Iterator();
        $this->setContent($content);
    }


    /**
     * Returns the rendered version of this element
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->render();
    }


    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  ElementsBlock::render()
     */
    public function render(): ?string
    {
        if (isset($this->tooltip)) {
            if ($this->tooltip->getUseIcon()) {
                if ($this->tooltip->getRenderBefore()) {
                    $this->classes->add(true, 'has-tooltip-icon-left');

                } else {
                    $this->classes->add(true, 'has-tooltip-icon-right');
                }
            }
        }

        if (!$this->element) {
            if ($this->element === null) {
                // This is a NULL element, only return the contents
                return $this->content . $this->extra;
            }

            throw new OutOfBoundsException(tr('Cannot render HTML element, no element type specified'));
        }

        $postfix = null;

        if ($this->attributes->get('auto_submit', false)) {
            // Add javascript to automatically submit on change
            $this->attributes->removeKeys('auto_submit');

            $postfix .= Script::new()
                              ->setContent('$("[name=' . $this->name . ']").change(function (e){ $(e.target).closest("form").submit(); });')
                              ->setJavascriptWrapper(EnumJavascriptWrappers::window);
        }

        $renderer_class = Request::getTemplate()->getRendererClass($this);

        $render_function = function () use ($postfix) {
            $attributes = $this->renderAttributes();
            $attributes = Arrays::implodeWithKeys($attributes, ' ', '=', '"', Utils::QUOTE_ALWAYS | Utils::HIDE_EMPTY_VALUES);

            if ($attributes) {
                $attributes = ' ' . $attributes;
            }

            $this->render = '<' . $this->element . $attributes;

            if ($this->requires_closing_tag) {
                return $this->render . '>' . $this->content . '</' . $this->element . '>';

            }

            $render       = $this->render . ' />';
            $this->render = null;

            return $render . $postfix;
        };

        if ($renderer_class) {
            TemplateRenderer::ensureClass($renderer_class, $this);

            $render = $renderer_class::new($this)
                                     ->setParentRenderFunction($render_function)
                                     ->render() . $postfix;

        } else {
            // The template component does not exist, return the basic Phoundation version
            Log::warning(tr('No template render class found for element component ":component", rendering basic HTML', [
                ':component' => get_class($this),
            ]), 2);

            $render = $render_function() . $postfix;
        }

        if (isset($this->tooltip)) {
            $render = $this->tooltip->render($render);
        }

        if ($this->anchor) {
            // This element has an anchor. Render the anchor -which will render this element to be its contents- instead
            return $this->anchor->setContent($render)
                                ->setChildElement(null)
                                ->render() . $this->extra;
        }

        return $render . $this->extra;
    }


    /**
     * Returns a new ElementAttributes class
     *
     * @param string|null $content
     *
     * @return static
     */
    public static function new(?string $content = null): static
    {
        return new static($content);
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function renderAttributes(): IteratorInterface
    {
        $return = [
            'id'        => $this->id,
            'name'      => $this->name,
            'class'     => $this->getClass(),
            'height'    => $this->height,
            'width'     => $this->width,
            'autofocus' => ((static::$autofocus and (static::$autofocus === $this->id)) ? 'autofocus' : null),
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
        if (isset($this->data)) {
            foreach ($this->data as $key => $value) {
                if ($value === null) {
                    $return['data-' . $key] = null;

                } else {
                    $return['data-' . $key] = Strings::force($value, ' ');
                }
            }
        }
        // Add aria-* entries
        if (isset($this->aria)) {
            foreach ($this->aria as $key => $value) {
                $return['aria-' . $key] = $value;
            }
        }

        // Merge the system values over the set attributes
        return $this->attributes->appendSource($return);
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
     * Sets the type of element to display
     *
     * @param EnumElement|string|null $element
     *
     * @return static
     */
    public function setElement(EnumElement|string|null $element): static
    {
        if (is_enum($element)) {
            $element = $element->value;
        }
        if ($element) {
            $this->requires_closing_tag = match ($element) {
                'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr' => false,
                default                                                                                              => true,
            };

        } elseif ($element !== null) {
            throw new OutOfBoundsException(tr('Invalid element ":element" specified, must be NULL or valid HTML element', [
                ':element' => $element,
            ]));
        }
        $this->element = $element;

        return $this;
    }


    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function renderClassString(): ?string
    {
        $class = $this->getClass();
        if ($class) {
            return ' class="' . $class . '"';
        }

        return null;
    }
}
