<?php

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;



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
trait Mode
{
    /**
     * The type of infobox to show
     *
     * @var string|null $mode
     */
    #[ExpectedValues([null, 'primary', 'info', 'warning', 'danger', 'success'])]
    protected ?string $mode = null;



    /**
     * Sets the type of infobox to show
     *
     * @return string|null
     */
    #[ExpectedValues([null, 'primary', 'info', 'warning', 'danger', 'success'])]
    public function getMode(): ?string
    {
        return $this->mode;
    }



    /**
     * Returns the type of infobox to show
     *
     * @param string|null $mode
     * @return static
     */
    public function setMode(#[ExpectedValues([null, 'primary', 'info', 'warning', 'danger', 'success'])] ?string $mode): static
    {
        $this->mode = $mode;
        return $this;
    }
}