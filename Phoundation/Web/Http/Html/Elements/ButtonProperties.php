<?php

namespace Phoundation\Web\Http\Html\Elements;



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
    /**
     * Outlined buttons
     *
     * @var bool $outlined
     */
    protected bool $outlined = false;

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