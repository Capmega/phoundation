<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Data\Traits\DataTitle;


/**
 * Modal class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Modal extends ElementsBlock
{
    use DataTitle;


    /**
     * The Modal identifier
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * The bottom buttons content for this modal
     *
     * @var Buttons|null
     */
    protected ?Buttons $buttons = null;

    /**
     * Sets the size for this modal
     *
     * @var string|null $size
     */
    #[ExpectedValues(values:["sm", "lg", "xj", "fullscreen"])]
    protected ?string $size = null;

    /**
     * Sets if the modal will animate fading in or not
     *
     * @var bool $fade
     */
    protected bool $fade = true;

    /**
     * Sets if the modal is vertically centered or not
     *
     * @var bool $vertical_center
     */
    protected bool $vertical_center = true;

    /**
     * Sets if the escape key will close the modal or not
     *
     * @var bool $escape
     */
    protected bool $escape = true;

    /**
     * Sets
     *
     * @var bool $escape
     */
    protected ?bool $backdrop = true;


    /**
     * Sets the modal size
     *
     * @return string|null
     */
    #[ExpectedValues(values:["sm", "lg", "xj", "fullscreen"])] public function getSize(): ?string
    {
        return $this->size;
    }


    /**
     * Sets the modal size
     *
     * @param string|null $size
     * @return static
     */
    public function setSize(#[ExpectedValues(values:["sm", "md", "lg", "xj", "fullscreen"])] ?string $size): static
    {
        if ($size === 'md') {
            $size = null;
        }

        $this->size = $size;
        return $this;
    }


    /**
     * Returns the modal fade
     *
     * @return bool
     */
    public function getFade(): bool
    {
        return $this->fade;
    }


    /**
     * Sets the modal fade
     *
     * @param bool $fade
     * @return static
     */
    public function setFade(bool $fade): static
    {
        $this->fade = $fade;
        return $this;
    }


    /**
     * Returns the modal backdrop
     *
     * @return string|null
     */
    public function getBackdrop(): ?string
    {
        return $this->backdrop;
    }


    /**
     * Sets the modal backdrop
     *
     * @param string|null $backdrop
     * @return static
     */
    public function setBackdrop(?string $backdrop): static
    {
        $this->backdrop = $backdrop;
        return $this;
    }


    /**
     * Returns if the modal is vertically centered
     *
     * @return bool
     */
    public function getVerticalCenter(): bool
    {
        return $this->vertical_center;
    }


    /**
     * Sets if the modal is vertically centered
     *
     * @param bool $vertical_center
     * @return static
     */
    public function setVerticalCenter( $vertical_center): static
    {
        $this->vertical_center = $vertical_center;
        return $this;
    }


    /**
     * Returns the modal escape
     *
     * @return bool
     */
    public function getEscape(): bool
    {
        return $this->escape;
    }


    /**
     * Sets the modal escape
     *
     * @param bool $escape
     * @return static
     */
    public function setEscape(bool $escape): static
    {
        $this->escape = $escape;
        return $this;
    }


    /**
     * Returns the modal buttons
     *
     * @return Buttons|null
     */
    public function getButtons(): ?Buttons
    {
        return $this->buttons;
    }


    /**
     * Sets the modal buttons
     *
     * @param Buttons|null $buttons
     * @return static
     */
    public function setButtons(?Buttons $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }
}