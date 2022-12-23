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
trait Type
{
    /**
     * The type of infobox to show
     *
     * @var string|null $type
     */
    #[ExpectedValues([null, 'primary', 'info', 'warning', 'danger', 'success'])]
    protected ?string $type = null;



    /**
     * Sets the type of infobox to show
     *
     * @return string|null
     */
    #[ExpectedValues([null, 'primary', 'info', 'warning', 'danger', 'success'])]
    public function getType(): ?string
    {
        return $this->type;
    }



    /**
     * Returns the type of infobox to show
     *
     * @param string|null $type
     * @return static
     */
    public function setType(#[ExpectedValues([null, 'primary', 'info', 'warning', 'danger', 'success'])] ?string $type): static
    {
        $this->type = $type;
        return $this;
    }
}