<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Web\Http\Html\Components\Interfaces\InputTypeInterface;
use Phoundation\Web\Http\Html\Components\Mode;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Html;
use Stringable;


/**
 * Trait InputElement
 *
 * This trait adds functionality for HTML input elements
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait InputElement
{
    use Mode;


    /**
     * Input element type
     *
     * @var InputTypeInterface|null $type
     */
    protected ?InputTypeInterface $type = null;

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
     * Returns the type for the input element
     *
     * @return InputTypeInterface|null
     */
    public function getType(): ?InputTypeInterface
    {
        return $this->type;
    }


    /**
     * Sets the type for the input element
     *
     * @param InputTypeInterface|null $type
     * @return static
     */
    public function setType(?InputTypeInterface $type): static
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
     * Returns a new input element from the specified data entry field
     *
     * @param DefinitionInterface $field
     * @return static
     */
    public static function newFromDataEntryField(DefinitionInterface $field): static
    {
        $element    = new static();
        $attributes = $field->getRules();

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