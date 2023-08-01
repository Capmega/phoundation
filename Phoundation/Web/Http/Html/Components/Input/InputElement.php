<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Web\Http\Html\Components\Mode;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Interfaces\InterfaceInputType;
use Stringable;


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
    use Mode;


    /**
     * Input element type
     *
     * @var InterfaceInputType|null $type
     */
    protected ?InterfaceInputType $type = InputType::text;

    /**
     * Input element value
     *
     * @var string|null $value
     */
    protected ?string $value = null;


    /**
     * Returns the type for the input element
     *
     * @return InterfaceInputType|null
     */
    public function getType(): ?InterfaceInputType
    {
        return $this->type;
    }


    /**
     * Sets the type for the input element
     *
     * @param InterfaceInputType|null $type
     * @return static
     */
    public function setType(?InterfaceInputType $type): static
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
     * @param Stringable|string|float|int|null $value
     * @return static
     */
    public function setValue(Stringable|string|float|int|null $value): static
    {
        $this->value = htmlspecialchars((string) $value);
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
            'type'  => $this->type?->value,
            'value' => $this->value,
        ];
    }
}