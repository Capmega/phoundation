<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Components\Interfaces\FormInterface;
use Phoundation\Web\Html\Renderer;
use Phoundation\Web\Page;


/**
 * Class ElementsBlock
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
// TODO Implement Phoundation Iterator instead of PHP Iterator
abstract class ElementsBlock extends Iterator implements IteratorInterface, ElementsBlockInterface
{
    use ElementAttributes {
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
     * The data source of this object
     *
     * @var array $source
     */
    protected array $source;


    /**
     * Class constructor
     */
    public function __construct(?array $source = null)
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
     * @return FormInterface|null
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }


    /**
     * Returns the form of this objects block
     *
     * @param FormInterface|null $form
     * @return static
     */
    public function setForm(?FormInterface $form): static
    {
        $this->form = $form;
        return $this;
    }


    /**
     * If set true, when this element renders it will only return the contents
     *
     * @param bool $enable
     * @return $this
     */
    public function setRenderContentsOnly(bool $enable): static
    {
        $this->render_contents_only = $enable;
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
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see ElementInterface::render()
     */
    public function render(): ?string
    {
        if ($this->render_contents_only) {
            return $this->content;
        }

        $renderer_class = Page::getTemplate()->getRendererClass($this);

        Log::write(tr('Using renderer class ":class" for ":this"', [
            ':class' => $renderer_class,
            ':this'  => get_class($this)
        ]), 'debug', 2);

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
        ]), 3);

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
}
