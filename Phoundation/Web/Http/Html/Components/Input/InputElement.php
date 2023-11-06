<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
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
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
    protected ?InputTypeInterface $type = InputType::text;

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