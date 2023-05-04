<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;


/**
 * Modal class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Modal extends ElementsBlock
{
    /**
     * The Modal identifier
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * The text in the modal title bar
     *
     * @var string|null
     */
    protected ?string $title = null;

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
     * Returns the modal identifier
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }


    /**
     * Sets the modal identifier
     *
     * @param string|null $id
     * @return static
     */
    public function setId(?string $id): static
    {
        $this->id = $id;
        return $this;
    }


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
     * @return string|null
     */
    public function getFade(): ?string
    {
        return $this->fade;
    }


    /**
     * Sets the modal fade
     *
     * @param string|null $fade
     * @return static
     */
    public function setFade(?string $fade): static
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
     * @return string|null
     */
    public function getVerticalCenter(): ?string
    {
        return $this->vertical_center;
    }


    /**
     * Sets if the modal is vertically centered
     *
     * @param string|null $vertical_center
     * @return static
     */
    public function setVerticalCenter(?string $vertical_center): static
    {
        $this->vertical_center = $vertical_center;
        return $this;
    }


    /**
     * Returns the modal escape
     *
     * @return string|null
     */
    public function getEscape(): ?string
    {
        return $this->escape;
    }


    /**
     * Sets the modal escape
     *
     * @param string|null $escape
     * @return static
     */
    public function setEscape(?string $escape): static
    {
        $this->escape = $escape;
        return $this;
    }


    /**
     * Returns the modal title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }


    /**
     * Sets the modal title
     *
     * @param string|null $title
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;
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