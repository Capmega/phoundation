<?php

namespace Phoundation\Web\Http\Html\Elements;



/**
 * Trait InputElement
 *
 * This trait adds functionality for HTML input elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait InputElement
{
    /**
     * Input element type
     *
     * @var string|null $type
     */
    protected ?string $type = null;

    /**
     * Input element value
     *
     * @var string|null $value
     */
    protected ?string $value = null;



    /**
     * Returns the type for the input element
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }



    /**
     * Sets the type for the input element
     *
     * @param string|null $type
     * @return $this
     */
    public function setType(string|null $type): static
    {
        $this->type = $type;
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
     * @param string|null $value
     * @return $this
     */
    public function setValue(string|null $value): static
    {
        $this->value = $value;
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
        $this->attributes['auto_submit'] = $auto_submit;
        return $this;
    }



    /**
     * Returns if the element will auto submit
     *
     * @return bool
     */
    public function getAutoSubmit(): bool
    {
        return $this->attributes['auto_submit'];
    }



    /**
     * Sets onchange functionality
     *
     * @param string|null $on_change
     * @return static
     */
    public function setOnChange(?string $on_change): static
    {
        $this->attributes['on_change'] = $on_change;
        return $this;
    }



    /**
     * Returns onchange functionality
     *
     * @return string|null
     */
    public function getOnChange(): ?string
    {
        return isset_get($this->attributes['on_change']);
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, tabindex, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildInputAttributes(): array
    {
        return [
            'type'  => $this->type,
            'value' => $this->value,
        ];
    }
}