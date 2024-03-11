<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Buttons;

use Phoundation\Web\Html\Enums\Interfaces\EnumInputTypeInterface;
use Phoundation\Web\Html\Traits\Mode;
use Phoundation\Web\Html\Traits\UsesSize;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * ButtonProperties trait
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait ButtonProperties
{
    use Mode;
    use UsesSize;


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
     * Block (full width) buttons
     *
     * @var bool $block
     */
    protected bool $block = false;

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
     * Set the button type
     *
     * @param EnumInputTypeInterface|null $type
     * @return Button
     */
    public function setType(?EnumInputTypeInterface $type): static
    {
        $this->setElement('button');
        $this->type = $type;
        return $this;
    }


    /**
     * Returns the button type
     *
     * @return EnumInputTypeInterface|null
     */
    public function getType(): ?EnumInputTypeInterface
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
     * @param Stringable|string|null $anchor_url
     * @return Button
     */
    public function setAnchorUrl(Stringable|string|null $anchor_url): static
    {
        $this->setElement('a');
        $this->anchor_url = (string) UrlBuilder::getWww($anchor_url);
        $this->type       = null;

        return $this;
    }


    /**
     * Returns if the button is outlined or not
     *
     * @return bool
     */
    public function getOutlined(): bool
    {
        return $this->outlined;
    }


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
     * Returns if the button is block or not
     *
     * @return bool
     */
    public function getBlock(): bool
    {
        return $this->block;
    }


    /**
     * Set if the button is block or not
     *
     * @param bool $block
     * @return Button
     */
    public function setBlock(bool $block): static
    {
        $this->block = $block;
        return $this;
    }


    /**
     * Returns if the button is flat or not
     *
     * @return bool
     */
    public function getFlat(): bool
    {
        return $this->flat;
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
     * Returns if the button is rounded or not
     *
     * @return bool
     */
    public function getRounded(): bool
    {
        return $this->rounded;
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
     * Returns if the button is wrapping or not
     *
     * @return bool
     */
    public function getWrapping(): bool
    {
        return $this->wrapping;
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
}