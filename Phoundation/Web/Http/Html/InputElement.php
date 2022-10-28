<?php

namespace Phoundation\Web\Http\Html;



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
     * If true, will submit on change
     *
     * @var bool $auto_submit
     */
    protected bool $auto_submit = false;

    /**
     * Add onchange functioanlity
     *
     * @var string|null $on_change
     */
    protected ?string $on_change = null;



    /**
     * Sets if the element will auto submit
     *
     * @param bool $auto_submit
     * @return Element
     */
    public function setAutoSubmit(bool $auto_submit): self
    {
        $this->auto_submit = $auto_submit;
        return $this;
    }



    /**
     * Returns if the element will auto submit
     *
     * @return bool
     */
    public function getAutoSubmit(): bool
    {
        return $this->auto_submit;
    }



    /**
     * Sets onchange functionality
     *
     * @param string|null $on_change
     * @return Element
     */
    public function setOnChange(?string $on_change): self
    {
        $this->on_change = $on_change;
        return $this;
    }



    /**
     * Returns onchange functionality
     *
     * @return string|null
     */
    public function getOnChange(): ?string
    {
        return $this->on_change;
    }
}