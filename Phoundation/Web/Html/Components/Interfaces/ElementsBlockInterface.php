<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Span;
use Stringable;

interface ElementsBlockInterface extends ComponentInterface, IteratorInterface
{
    /**
     * Sets the content of the element to display
     *
     * @param bool $use_form
     * @param bool $post
     *
     * @return static
     */
    public function useForm(bool $use_form, bool $post = true): static;


    /**
     * Returns the form of this objects block
     *
     * @return FormInterface|null
     */
    public function getForm(): ?FormInterface;


    /**
     * Returns the form of this objects block
     *
     * @param FormInterface|null $form
     *
     * @return static
     */
    public function setForm(?FormInterface $form): static;


    /**
     * If set true, when this element renders it will only return the contents
     *
     * @param bool $enable
     *
     * @return static
     */
    public function setRenderContentsOnly(bool $enable): static;


    /**
     * Returns if this element renders it will only return the contents
     *
     * @return bool
     */
    public function getRenderContentsOnly(): bool;


    /**
     * Returns if this FlashMessages object has rendered HTML or not
     *
     * @return bool
     */
    public function hasRendered(): bool;


    /**
     * Clears the render cache for this object
     *
     * @return static
     */
    public function clearRenderCache(): static;


    /**
     * Returns the definition
     *
     * @return DefinitionInterface|null
     */
    public function getDefinitionObject(): ?DefinitionInterface;


    /**
     * Sets the definition
     *
     * @param DefinitionInterface|null $definition
     *
     * @return static
     */
    public function setDefinitionObject(DefinitionInterface|null $definition): static;


    /**
     * Returns the (optional) anchor for this element
     *
     * @return AInterface
     */
    public function getAnchorObject(): AInterface;


    /**
     * Sets the anchor for this element
     *
     * @param AInterface|null $o_anchor
     *
     * @return static
     */
    public function setAnchorObject(?AInterface $o_anchor): static;


    /**
     * Returns the HTML class element attribute
     *
     * @param string|null $prefix                       If true, will prefix the class list with the specified prefix
     * @param bool        $add_definition_name_to_class If true, will add the element's name attribute to the list of classes
     *
     * @return string|null
     */
    public function getClass(?string $prefix = null, bool $add_definition_name_to_class = true): ?string;


    /**
     * Returns the HTML name attribute for this element
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Shortcut to set the form action directly
     *
     * @param Stringable|string|null $action
     *
     * @return static
     */
    public function setFormAction(Stringable|string|null $action): static;
}
