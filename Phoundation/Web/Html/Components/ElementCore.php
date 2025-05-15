<?php

/**
 * Class Element
 *
 * This class contains the implementation of the Div class
 *
 * @see \Phoundation\Web\Html\Components\Element
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\Input\Input;
use Phoundation\Web\Html\Components\Input\InputAutoSuggest;
use Phoundation\Web\Html\Components\Input\InputText;
use Phoundation\Web\Html\Components\Input\InputTextInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;
use Phoundation\Web\Html\Template\TemplateRenderer;
use Phoundation\Web\Html\Traits\TraitElementAttributes;
use Phoundation\Web\Requests\Request;


abstract class ElementCore implements ElementInterface
{
    use TraitElementAttributes {
        __construct as protected ___construct;
    }


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
     * @note Templates work as follows: Any component that renders HTML must be in an HTML/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found, then that file will
     *       render the HTML for the component. For example, Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  ElementsBlock::render()
     */
    public function render(): ?string
    {
        if ($this->render) {
            // Return cached render information
            return $this->render;
        }

        if (isset($this->o_tooltip)) {
            if ($this->o_tooltip->getUseIcon()) {
                if ($this->o_tooltip->getRenderBefore()) {
                    $this->o_classes->add(true, 'has-tooltip-icon-left');

                } else {
                    $this->o_classes->add(true, 'has-tooltip-icon-right');
                }
            }
        }

        if (!$this->element) {
            if ($this->element === null) {
                // This is a NULL element, only return the contents
                return $this->content;
            }

            throw new OutOfBoundsException(tr('Cannot render HTML element, no element type specified'));
        }

        $auto_submit = $this->o_attributes->get('auto_submit', false);

        if ($auto_submit) {
            if (!is_object($auto_submit)) {
                if ($this instanceof InputTextInterface) {
                    if ($this instanceof InputAutoSuggest) {
                        $auto_suggest_auto_submit = Script::new('var $input   = $(\'[name="' . $this->getName() . '"]\');
                                                                 var dropdown = "#autocomplete-dropdown-' . $this->getName() . '_autosuggest_div";
                                                                        
                                                                 $input.on("keydown", function(e){
                                                                     if (e.keyCode === 13) {
                                                                         $(this).closest("form").trigger("submit");
                                                                     }
                                                                 });
                                                                        
                                                                 $(document).on("mousedown", dropdown + " .autocomplete-item", function(){
                                                                     var txt = $(this).text();
                                                                     $input.val(txt);
                                                                     $input.closest("form").trigger("submit");
                                                                 });')->setJavascriptWrapper(EnumJavascriptWrappers::window);
                    }

                    if ($this->getClearButton()) {
                        $clear_button_auto_submit = Script::new('$(document).on("click", "div.form-icon-trailing.ward .clear", function(){
                                                                     $(\'[name="' . $this->getName() . '"]\').closest("form").trigger("submit");
                                                                 });')->setJavascriptWrapper(EnumJavascriptWrappers::window);
                    }
                }

                $auto_submit = Script::new('$(\'[name="' . $this->name . '"]\').on("change keydown", function (e) {
                                                    switch (e.type) {
                                                        case "keydown":
                                                            if (e.keyCode !== 13) {
                                                                break;
                                                            } 
                                                            
                                                            // On enter, auto submit too by using the "on change" event
                                                            // no break
                                                            
                                                        case "change":
                                                            setTimeout(function () {
                                                                console.log("Auto submitting form from target \"" + $(e.target).attr("name") + "\"");            
                                                                $(e.target).closest("form").trigger("submit"); 
                                                            }, 100);
                                                    }           
                                                });' . isset_get($auto_suggest_auto_submit) . isset_get($clear_button_auto_submit))->setJavascriptWrapper(EnumJavascriptWrappers::window);
            }
            // Add JavaScript code to automatically submit on change
            // NOTE: This method uses the WINDOW JavaScript wrapper because it fires AFTER the event
            // document.addEventListener('DOMContentLoaded') which could cause accidental change events right on load
            $this->addScriptObject($auto_submit);
        }

        $this->o_attributes->removeKeys('auto_submit');

        $renderer_class  = Request::getTemplateObject()->getRendererClass($this);
        $render_function = function () {
            $attributes = $this->renderAttributesArray();
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

            return $render;
        };

        if ($renderer_class) {
            TemplateRenderer::ensureClass($renderer_class, $this);

            // NOTE: Class methods are rendered by the Template libraries rendering for the correct template and these
            // already add the "before content" and "after content". Do NOT add before and after content here!
            $render = $renderer_class::new($this)
                                     ->setParentRenderFunction($render_function)
                                     ->render() . $this->o_scripts?->render();

        } else {
            // The template component doesn't exist, return the basic Phoundation version
            Log::warning(ts('No template render class found for element component ":component", rendering basic HTML', [
                ':component' => get_class($this),
            ]), 2);

            // The render function does NOT add before and after content, add it manually here.
            $render = $this->renderBeforeContent() .
                      $render_function() .
                      $this->renderAfterContent() .
                      $this->o_scripts?->render();
        }

        if (isset($this->o_tooltip)) {
            $render = $this->o_tooltip->render($render);
        }

        if ($this->o_anchor) {
            // This element has an anchor. Render the anchor -which will render this element to be its contents- instead
            return $this->renderBeforeContent() . $this->o_anchor->setContent($render)
                                                                 ->setChildElement(null)
                                                                 ->render() . $this->renderAfterContent();
        }

        return $this->render = $render;
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function renderAttributesArray(): IteratorInterface
    {
        $return = [
            'id'        => $this->id,
            'name'      => $this->name,
            'class'     => $this->getClass(),
            'height'    => $this->height,
            'width'     => $this->width,
            'autofocus' => ((static::$autofocus and (static::$autofocus === $this->id)) ? 'autofocus' : null),
            'readonly'  => ($this->readonly                                             ? 'readonly'  : null),
            'disabled'  => ($this->disabled                                             ? 'disabled'  : null),
        ];

        // Remove empty entries
        foreach ($return as $key => $value) {
            if ($value === null) {
                unset($return[$key]);
            }
        }

        // Add data-* entries
        if (isset($this->o_data)) {
            foreach ($this->o_data as $key => $value) {
                if ($value === null) {
                    $return['data-' . $key] = null;

                } else {
                    $return['data-' . $key] = Strings::force($value, ' ');
                }
            }
        }

        // Add aria-* entries
        if (isset($this->o_aria)) {
            foreach ($this->o_aria as $key => $value) {
                $return['aria-' . $key] = $value;
            }
        }

        // Merge the system values over the set attributes
        return $this->o_attributes->appendSource($return);
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
