<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Components\Input\InputElement;


/**
 * Checkbox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class CheckBox extends Element
{
    use InputElement;



    /**
     * Checkbox is checked?
     *
     * @var bool $checked
     */
    protected bool $checked = false;

    /**
     * Optional label
     *
     * @var string|null $label
     */
    protected ?string $label = null;

    /**
     * Optional label class
     *
     * @var string|null $label_class
     */
    protected ?string $label_class = null;



    /**
     * CheckBox class constructor
     */
    public function __construct()
    {
        $this->setElement('input');
        $this->setType('checkbox');
        parent::__construct();
    }



    /**
     * Returns if the checkbox is checked or not
     *
     * @return bool
     */
    public function getChecked(): bool
    {
        return $this->checked;
    }



    /**
     * Sets if the checkbox is checked or not
     *
     * @param bool $checked
     * @return static
     */
    public function setChecked(bool $checked): static
    {
        $this->checked = $checked;
        return $this;
    }



    /**
     * Returns the label for the checkbox
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }



    /**
     * Sets the label for the checkbox
     *
     * @param string|null $label
     * @return static
     */
    public function setLabel(string|null $label): static
    {
        $this->label = $label;
        return $this;
    }



    /**
     * Returns the label_class for the checkbox
     *
     * @return string|null
     */
    public function getLabelClass(): ?string
    {
        return $this->label_class;
    }



    /**
     * Sets the label_class for the checkbox
     *
     * @param string|null $label_class
     * @return static
     */
    public function setLabelClass(string|null $label_class): static
    {
        $this->label_class = $label_class;
        return $this;
    }



    /**
     * Render the HTML for this checkbox
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $html = parent::render();

        if ($this->label) {
            $html .= Element::new()
                ->setElement('label')
                ->addAttribute('for', $this->id, true)
                ->setClass($this->label_class)
                ->setContent($this->label)
                ->render();
        }

        return $html;
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
        $return = [];

        if ($this->checked)  {
            $return['checked'] = null;
        }

        // Merge the system values over the set attributes
        return array_merge(parent::buildAttributes(), $this->buildInputAttributes(), $return);
    }
}