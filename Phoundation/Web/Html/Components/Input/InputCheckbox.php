<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataInline;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Html\Traits\TraitInputElement;


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
    use TraitInputElement;
    use TraitDataInline;


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
     * Labels may be hidden and only visible with aria
     *
     * @var bool $label_hidden
     */
    protected bool $label_hidden = false;


    /**
     * CheckBox class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setElement('input');
        $this->input_type = EnumInputType::checkbox;
        parent::__construct($content);
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
     * Returns the label_hidden for the checkbox
     *
     * @return bool
     */
    public function getLabelHidden(): bool
    {
        return $this->label_hidden;
    }


    /**
     * Sets the label_hidden for the checkbox
     *
     * @param bool $label_hidden
     * @return static
     */
    public function setLabelHidden(bool $label_hidden): static
    {
        $this->label_hidden = $label_hidden;
        return $this;
    }


    /**
     * Render the HTML for this checkbox
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if ($this->label_hidden) {
            // Hide the label, put it in aria instead
            $this->getAria()->add($this->label, 'label');
            $this->label = null;
        }

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
    protected function renderAttributes(): IteratorInterface
    {
        $return = [];

        if ($this->checked)  {
            $return['checked'] = null;
        }

        // Merge the system values over the set attributes
        return parent::renderAttributes()->appendSource($this->renderInputAttributes(), $return);
    }
}