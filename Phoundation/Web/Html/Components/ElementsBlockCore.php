<?php

/**
 * Class ElementsBlock
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use PDOStatement;
use Phoundation\Core\Log\Log;
use Phoundation\Data\Enums\EnumPoadTypes;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\IteratorCore;
use Phoundation\Data\Poad\Poad;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Template\TemplateRenderer;
use Phoundation\Web\Html\Traits\TraitElementAttributes;
use Phoundation\Web\Requests\Request;
use Stringable;


abstract class ElementsBlockCore extends IteratorCore implements ElementsBlockInterface
{
    use TraitElementAttributes {
        __construct as ___construct;
    }


    /**
     * If true, this element block will only render the contents
     *
     * @var bool $render_contents_only
     */
    protected bool $render_contents_only = false;

    /**
     * Indicates if flash messages were rendered (and then we can assume, sent to client too)
     *
     * @var bool
     */
    protected bool $has_rendered = false;

    /**
     * A form around this element block
     *
     * @var FormInterface|null
     */
    protected ?FormInterface $form = null;


    /**
     * ElementsBlock class constructor
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     */
    public function __construct(IteratorInterface|PDOStatement|array|string|null $source = null)
    {
        parent::__construct($source);
        $this->___construct();
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
     * @note Templates work as follows: Any component that renders HTML must be in an HTML/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  ElementInterface::render()
     */
    public function render(): ?string
    {
        if (empty($this->content)) {
            if (!$this->render_on_empty_content) {
                // Do not render components that have no content
                return null;
            }
        }

        if ($this->render_contents_only) {
            return $this->content;
        }

        $scripts         = $this->renderScripts();
        $renderer_class  = Request::getTemplateObject()->getRenderClass($this);
        $render_function = function (?string $render = null) {
            if ($this->form) {
                return $this->form
                            ->setContent($render)
                            ->render();
            }

            if (empty($render)) {
                $render = $this->render;
            }

            return $render;
        };

        if ($renderer_class) {
            Log::dump(ts('Using renderer class ":class" for ":this"', [
                ':class' => $renderer_class,
                ':this'  => static::class,
            ]), 2);

            TemplateRenderer::ensureClass($renderer_class, $this);

            return $renderer_class::new($this)
                                  ->setRenderFunction($render_function)
                                  ->render() . $scripts;
        }

        if (method_exists($this, 'defaultRender')) {
            // Use the default render for this object
            return $this->defaultRender() . $scripts;
        }

        // The template component doesn't exist, return the basic Phoundation version
        Log::warning(ts('No template render class found for block component ":component", rendering basic HTML', [
            ':component' => static::class,
        ]), 3);

        return $render_function($this->render) . $scripts;
    }


    /**
     * Renders and returns the linked Scripts object, if any
     *
     * @return string|null
     */
    public function renderScripts(): ?string
    {
        return $this->o_scripts?->render();
    }


    /**
     * Returns the contents of this object as an array
     *
     * @return array
     */
    public function __toArray(): array
    {
        return Poad::generateArray($this->getSource(), static::class, EnumPoadTypes::object);
    }


    /**
     * Sets if this element block should render an HTML form around itself, or not
     *
     * @param bool        $use_form
     * @param bool        $post
     * @param string|null $id
     *
     * @return static
     */
    public function useForm(bool $use_form, bool $post = true, ?string $id = null): static
    {
        if ($use_form) {
            if (empty($this->form)) {
                $this->form = Form::new()
                                  ->setRequestMethod($post ? EnumHttpRequestMethod::post : EnumHttpRequestMethod::get)
                                  ->setId($id);
            }

        } else {
            $this->form = null;
        }

        return $this;
    }


    /**
     * Shortcut to set the form action directly
     *
     * @param Stringable|string|null $action
     *
     * @return static
     */
    public function setFormAction(Stringable|string|null $action): static
    {
        $this->getFormObject()->setAction($action);
        return $this;
    }


    /**
     * Returns the form of this objects block
     *
     * @return FormInterface|null
     */
    public function getFormObject(): ?FormInterface
    {
        return $this->form;
    }


    /**
     * Returns the form of this objects block
     *
     * @param FormInterface|null $form
     *
     * @return static
     */
    public function setFormObject(?FormInterface $form): static
    {
        $this->form = $form;
        return $this;
    }


    /**
     * Returns if this element renders it will only return the contents
     *
     * @return bool
     */
    public function getRenderContentsOnly(): bool
    {
        return $this->render_contents_only;
    }


    /**
     * If set true, when this element renders it will only return the contents
     *
     * @param bool $enable
     *
     * @return static
     */
    public function setRenderContentsOnly(bool $enable): static
    {
        $this->render_contents_only = $enable;
        return $this;
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
}
