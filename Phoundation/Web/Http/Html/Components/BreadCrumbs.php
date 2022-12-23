<?php

namespace Phoundation\Web\Http\Html\Components;


/**
 * BreadCrumbs class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
class BreadCrumbs extends ElementsBlock
{
    /**
     * Wen library breadcrumbs don't give rendered output (for now)
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return null;
    }
}