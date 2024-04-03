<?php

/**
 * Trait TraitInputLabel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */

namespace Phoundation\Web\Html\Traits;

trait TraitInputLabel
{
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
}