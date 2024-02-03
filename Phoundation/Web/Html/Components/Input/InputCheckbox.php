<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\Input\Traits\InputElement;
use Phoundation\Web\Html\Enums\InputType;


/**
 * Checkbox class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class InputCheckbox extends Input
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
        $this->type = InputType::checkbox;
        parent::__construct();
    }


    /**
     * Returns if the checkbox is checked or not
     *
     * @return bool
     */
    public function getChecked(): bool
    {
        return (bool) $this->attributes->get('checked', false);
    }


    /**
     * Sets if the checkbox is checked or not
     *
     * @param bool $checked
     * @return static
     */
    public function setChecked(bool $checked): static
    {
        return $this->setAttribute($checked ? 1 : null, 'checked');
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
    public function setLabel(?string $label): static
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
    public function setLabelClass(?string $label_class): static
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
        if ($this->label) {
            $element = Element::new()
                ->setElement('label')
                ->setClass($this->label_class)
                ->setContent($this->label);

            $element->attributes->add($this->id, 'for');

            $this->render = $element->render();
        }

        return parent::render();
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function buildAttributes(): IteratorInterface
    {
        $return = [];

        if ($this->checked)  {
            $return['checked'] = null;
        }

        // Merge the system values over the set attributes
        return parent::buildAttributes()->merge($this->buildInputAttributes(), $return);
    }
}