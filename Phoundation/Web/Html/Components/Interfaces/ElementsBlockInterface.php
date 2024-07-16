<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Span;

interface ElementsBlockInterface extends RenderInterface, IteratorInterface
{
    /**
     * Sets the content of the element to display
     *
     * @param bool $use_form
     *
     * @return static
     */
    public function useForm(bool $use_form): static;


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
     * @return $this
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
     * Returns the definition
     *
     * @return DefinitionInterface|null
     */
    public function getDefinition(): ?DefinitionInterface;


    /**
     * Sets the definition
     *
     * @param DefinitionInterface|null $definition
     *
     * @return static
     */
    public function setDefinition(DefinitionInterface|null $definition): static;


    /**
     * Returns the (optional) anchor for this element
     *
     * @return AInterface
     */
    public function getAnchor(): AInterface;


    /**
     * Sets the anchor for this element
     *
     * @param AInterface|null $anchor
     *
     * @return Span
     */
    public function setAnchor(?AInterface $anchor): static;
}
