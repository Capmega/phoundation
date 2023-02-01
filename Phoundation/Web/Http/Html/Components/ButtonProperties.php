<?php

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Web\Http\UrlBuilder;



/**
 * ButtonProperties trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait ButtonProperties
{
    use Mode;



    /**
     * Sets if this is an anchor button or not
     *
     * @var string|null $anchor_url
     */
    protected ?string $anchor_url = null;

    /**
     * Outlined buttons
     *
     * @var bool $outlined
     */
    protected bool $outlined = false;

    /**
     * Flat buttons
     *
     * @var bool $flat
     */
    protected bool $flat = false;

    /**
     * Rounded buttons
     *
     * @var bool $rounded
     */
    protected bool $rounded = false;

    /**
     * Text wrapping
     *
     * @var bool $wrapping
     */
    protected bool $wrapping = true;

    /**
     * Button size
     *
     * @var string|null $size
     */
    #[ExpectedValues(values: ['lg', 'sm', 'xs', null])]
    protected ?string $size = null;



    /**
     * Set if the button is outlined or not
     *
     * @param bool $outlined
     * @return Button
     */
    public function setOutlined(bool $outlined): static
    {
        $this->outlined = $outlined;
        return $this;
    }



    /**
     * Returns if the button is outlined or not
     *
     * @return string
     */
    public function getOutlined(): string
    {
        return $this->outlined;
    }



    /**
     * Set the button size
     *
     * @param string|null $size
     * @return Button
     */
    public function setSize(#[ExpectedValues(values: ['lg', 'sm', 'xs', null])] ?string $size): static
    {
        $this->size = $size;
        return $this;
    }



    /**
     * Returns the button size
     *
     * @return string|null
     */
    #[ExpectedValues(values: ['lg', 'sm', 'xs', null])] public function getSize(): ?string
    {
        return $this->size;
    }



    /**
     * Set the button type
     *
     * @param string|null $type
     * @return Button
     */
    public function setType(#[ExpectedValues(values: ['submit', 'reset', 'button', null])] ?string $type = 'button'): static
    {
        switch ($type) {
            case 'submit':
                // Make this an input submit button
                $this->setElement('input');
                $this->setName('submit');

                $this->value   = $this->content;
                $this->content = null;
                $this->type    = $type;
                break;

            default:
                // Make this a normal button
                $this->setElement('button');

                $this->content = $this->value;
                $this->value   = null;
                $this->type    = $type;
        }

        return $this;
    }



    /**
     * Returns the button type
     *
     * @return string|null
     */
    #[ExpectedValues(values: ['submit', 'reset', 'button'])] public function getType(): ?string
    {
        return $this->type;
    }



    /**
     * Returns the button's anchor URL
     *
     * @return string|null
     */
    public function getAnchorUrl(): ?string
    {
        return $this->anchor_url;
    }



    /**
     * Returns the button's anchor URL
     *
     * @param string|null $anchor_url
     * @return Button
     */
    public function setAnchorUrl(?string $anchor_url): static
    {
        if ($anchor_url) {
            $this->setElement('a');
            $this->anchor_url = UrlBuilder::getWww($anchor_url);
            $this->type       = null;
            $this->content    = $this->value;
            $this->value      = null;
        } else {
            $this->setElement('button');
            $this->anchor_url = null;
            $this->value      = $this->content;
            $this->content    = null;

            if (!$this->type) {
                // Default to button
                $this->setType();
            }
        }

        return $this;
    }



    /**
     * Set if the button is flat or not
     *
     * @param bool $flat
     * @return Button
     */
    public function setFlat(bool $flat): static
    {
        $this->flat = $flat;
        return $this;
    }



    /**
     * Returns if the button is flat or not
     *
     * @return string
     */
    public function getFlat(): string
    {
        return $this->flat;
    }



    /**
     * Set if the button is rounded or not
     *
     * @param bool $rounded
     * @return Button
     */
    public function setRounded(bool $rounded): static
    {
        $this->rounded = $rounded;
        return $this;
    }



    /**
     * Returns if the button is rounded or not
     *
     * @return string
     */
    public function getRounded(): string
    {
        return $this->rounded;
    }



    /**
     * Set if the button is wrapping or not
     *
     * @param bool $wrapping
     * @return Button
     */
    public function setWrapping(bool $wrapping): static
    {
        $this->wrapping = $wrapping;
        return $this;
    }



    /**
     * Returns if the button is wrapping or not
     *
     * @return string
     */
    public function getWrapping(): string
    {
        return $this->wrapping;
    }
}