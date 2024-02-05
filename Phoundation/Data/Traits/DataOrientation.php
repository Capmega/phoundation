<?php

declare(strict_types=1);

namespace Phoundation\Data\Traits;


use JetBrains\PhpStorm\ExpectedValues;

/**
 * Trait DataOrientation
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataOrientation
{
    /**
     * @var string $orientation
     */
    #[ExpectedValues('top', 'bottom', 'left', 'right')] protected string $orientation;


    /**
     * Returns the orientation
     *
     * @return string
     */
    public function getOrientation(): string
    {
        return $this->orientation;
    }


    /**
     * Sets the orientation
     *
     * @param string $orientation
     * @return static
     */
    public function setOrientation(#[ExpectedValues('top', 'bottom', 'left', 'right')]  string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }
}