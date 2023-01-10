<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Element
{
    use ElementAttributes;



    /**
     * The element type
     *
     * @var string $element
     */
    protected string $element;



    /**
     * Sets the type of element to display
     *
     * @param string $element
     * @return static
     */
    public function setElement(string $element): static
    {
        $this->element = $element;
        return $this;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return string
     */
    public function getElement(): string
    {
        return $this->element;
    }



    /**
     * Generates and returns the HTML string
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->element) {
            throw new OutOfBoundsException(tr('Cannot render HTML element, no element type specified'));
        }

        $attributes = $this->buildAttributes();
        $attributes = Arrays::implodeWithKeys($attributes, ' ', '=', '"', true);

        $this->render = '<' . $this->element . ' ' . $attributes . $this->extra;

        if ($this->content) {
            return $this->render . '>' . $this->content . '</' . $this->element . '>';

        }

        return $this->render . ' />';
    }



    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function buildClassString(): ?string
    {
        $class = $this->getClass();

        if ($class) {
            return ' class="' . $class . '"';
        }

        return null;
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildAttributes(): array
    {
        $return = [
            'id'        => $this->id,
            'name'      => $this->name,
            'class'     => $this->getClass(),
            'height'    => $this->height,
            'width'     => $this->width,
            'autofocus' => $this->autofocus,
            'readonly'  => $this->readonly,
            'disabled'  => $this->disabled,
        ];

        // Remove empty entries
        foreach ($return as $key => $value) {
            if ($value === null) {
                unset($return[$key]);
                continue;
            }
        }

        // Merge the system values over the set attributes
        return array_merge($this->attributes, $return);
    }
}