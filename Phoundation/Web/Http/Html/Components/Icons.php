<?php

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Icons class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Icons extends Element
{
    /**
     * The icon size
     *
     * @var string|null $size
     */
    #[ExpectedValues(values:["xs", "sm", "lg", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x"])]
    protected ?string $size = null;


    /**
     * Sets the icon size
     *
     * @return string
     */
    #[ExpectedValues(values:["xs", "sm", "lg", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x"])] public function getSize(): string
    {
        return $this->size;
    }


    /**
     * Sets the icon size
     *
     * @param string $size
     * @return static
     */
    public function setSize(#[ExpectedValues(values:["xs", "sm", "lg", "2x", "3x", "4x", "5x", "6x", "7x", "8x", "9x", "10x"])] string $size): static
    {
        $this->size = $size;
        return $this;
    }
}