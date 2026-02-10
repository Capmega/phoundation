<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;

interface ElementsBlockInterface extends ComponentInterface, ElementAttributesInterface, IteratorInterface, ContentInterface
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
    public function getFormObject(): ?FormInterface;


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
     * @param DefinitionInterface|null $o_definition
     *
     * @return static
     */
    public function setDefinitionObject(DefinitionInterface|null $o_definition): static;


    /**
     * Returns the (optional) anchor for this element
     *
     * @return AnchorInterface
     */
    public function getAnchorObject(): AnchorInterface;


    /**
     * Sets the anchor for this element
     *
     * @param UrlInterface|AnchorInterface|null $o_anchor
     *
     * @return static
     */
    public function setAnchorObject(UrlInterface|AnchorInterface|null $o_anchor): static;


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

    /**
     * Returns if the contents of the element should be selectable by a user, or not
     *
     * @return bool
     */
    public function getSelectable(): bool;

    /**
     * Sets if the contents of the element should be selectable by a user, or not
     *
     * @param bool $selectable
     *
     * @return static
     */
    public function setSelectable(bool $selectable): static;

    /**
     * Returns true when this object is neither readonly nor disabled
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Returns the HTML disabled element attribute
     *
     * @return bool
     */
    public function getDisabled(): bool;

    /**
     * Set the HTML disabled element attribute
     *
     * @param bool              $disabled
     * @param bool|null         $set_readonly
     * @param string|false|null $title
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null, string|false|null $title = false): static;

    /**
     * Returns the HTML readonly element attribute
     *
     * @return bool
     */
    public function getReadonly(): bool;

    /**
     * Set the HTML readonly element attribute
     *
     * @param bool              $readonly
     * @param bool|null         $set_disabled
     * @param string|false|null $title
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null, string|false|null $title = false): static;

    /**
     * Returns if this control renders any output or not
     *
     * @return bool
     */
    public function getRenderToNull(): bool;

    /**
     * Set if this control renders any output or not
     *
     * @param bool $render If true, will render the component. If false, the component will render with NULL output
     *
     * @return static
     */
    public function setRenderToNull(bool $render): static;
}
