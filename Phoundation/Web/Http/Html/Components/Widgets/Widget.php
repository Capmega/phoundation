<?php

namespace Phoundation\Web\Http\Html\Components\Widgets;

use Phoundation\Web\Http\Html\Components\Background;
use Phoundation\Web\Http\Html\Components\ElementsBlock;
use Phoundation\Web\Http\Html\Components\Mode;

/**
 * Widget class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class Widget extends ElementsBlock
{
    use Mode;
    use Background;


    /**
     * Show the type color as gradient or not
     *
     * @var bool $gradient
     */
    protected bool $gradient = false;


    /**
     * Returns if this card is shown with gradient color or not
     *
     * @return bool
     */
    public function getGradient(): bool
    {
        return $this->gradient;
    }


    /**
     * Sets if this card is shown with gradient color or not
     *
     * @param bool $gradient
     * @return static
     */
    public function setGradient(bool $gradient): static
    {
        $this->gradient = $gradient;
        return $this;
    }
}