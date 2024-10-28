<?php

namespace Phoundation\Web\Html\Components\Input;

interface InputTextInterface
{
    /**
     * Returns the minimum length this text input
     *
     * @return int|null
     */
    public function getMinLength(): ?int;


    /**
     * Returns the minimum length this text input
     *
     * @param int|null $minlength
     *
     * @return static
     */
    public function setMinLength(?int $minlength): static;


    /**
     * Returns the maximum length this text input
     *
     * @param int|null $maxlength
     *
     * @return static
     */
    public function setMaxLength(?int $maxlength): static;


    /**
     * Returns the maximum length this text input
     *
     * @return int|null
     */
    public function getMaxLength(): ?int;


    /**
     * Returns the auto complete setting
     *
     * @return bool
     */
    public function getAutoComplete(): bool;


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     *
     * @return static
     */
    public function setAutoComplete(bool $auto_complete): static;


    /**
     * Returns placeholder text
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string;


    /**
     * Sets placeholder text
     *
     * @param string|null $placeholder
     *
     * @return static
     */
    public function setPlaceholder(?string $placeholder): static;
}
