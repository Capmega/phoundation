<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\DataDescription;
use Phoundation\Data\Traits\DataIcon;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Interfaces\EnumInputTypeInterface;
use Phoundation\Web\Html\Html;
use Stringable;


/**
 * Trait InputElement
 *
 * This trait adds functionality for HTML input elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait InputElement
{
    use DataDescription;
    use DataIcon;
    use Mode;


    /**
     * Input element type
     *
     * @var EnumInputTypeInterface|null $type
     */
    protected ?EnumInputTypeInterface $type = null;


    /**
     * Sets if this control should have a clear button
     *
     * @var bool $clear_button
     */
    protected bool $clear_button = false;

    /**
     * Input hidden or not
     *
     * @var bool $type
     */
    protected bool $hidden = false;

    /**
     * Input element value
     *
     * @var string|null $value
     */
    protected ?string $value = null;


    /**
     * Returns if the input element has a clear button or not
     *
     * @return bool
     */
    public function getClearButton(): bool
    {
        return $this->clear_button;
    }


    /**
     * Sets if the input element has a clear button or not
     *
     * @param bool $clear_button
     * @return static
     */
    public function setClearButton(bool $clear_button): static
    {
        $this->clear_button = $clear_button;
        return $this;
    }


    /**
     * Returns the type for the input element
     *
     * @return EnumInputTypeInterface|null
     */
    public function getType(): ?EnumInputTypeInterface
    {
        return $this->type;
    }


    /**
     * Sets the type for the input element
     *
     * @param EnumInputTypeInterface|null $type
     * @return static
     */
    public function setType(?EnumInputTypeInterface $type): static
    {
        $this->type = $type;
        return $this;
    }


    /**
     * Returns if this input element is hidden or not
     *
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }


    /**
     * Returns if this input element is hidden or not
     *
     * @param bool $hidden
     * @return static
     */
    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }


    /**
     * Returns the value for the input element
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }


    /**
     * Sets the value for the input element
     *
     * @param Stringable|string|float|int|null $value
     * @param bool $make_safe
     * @return static
     */
    public function setValue(Stringable|string|float|int|null $value, bool $make_safe = true): static
    {
        if ($make_safe) {
            $this->value = Html::safe($value);
        } else {
            $this->value = $value;
        }

        return $this;
    }


    /**
     * Sets if the element will auto submit
     *
     * @param bool $auto_submit
     * @return static
     */
    public function setAutoSubmit(bool $auto_submit): static
    {
        return $this->setAttribute($auto_submit, 'auto_submit');
    }


    /**
     * Returns if the element will auto submit
     *
     * @return bool
     */
    public function getAutoSubmit(): bool
    {
        return $this->attributes->get('auto_submit', false);
    }


    /**
     * Sets onchange functionality
     *
     * @param string|null $on_change
     * @return static
     */
    public function setOnChange(?string $on_change): static
    {
        return $this->setAttribute($on_change, 'on_change');
    }


    /**
     * Returns onchange functionality
     *
     * @return string|null
     */
    public function getOnChange(): ?string
    {
        return $this->attributes->get('on_change', false);
    }


    /**
     * Returns a new input element from the specified data entry field
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public static function newFromDataEntryField(DefinitionInterface $field): static
    {
        $element    = new static();
        $attributes = $field->getSource();

        // Set all attributes from the definitions file
        foreach($attributes as $key => $value) {
            $method = 'set' . Strings::capitalize($key);

            if (method_exists($element, $method)) {
                $element->$method($value);
            }
        }

        return $element;
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, tabindex, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function buildInputAttributes(): IteratorInterface
    {
        return Iterator::new()->setSource([
            'type'  => $this->type?->value,
            'value' => $this->value,
        ]);
    }
}