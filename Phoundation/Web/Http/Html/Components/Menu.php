<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
abstract class Menu extends ElementsBlock
{
    /**
     * The menu data
     *
     * @var array|null
     */
    protected ?array $menu = null;



    /**
     * Returns the menu data
     *
     * @return array
     */
    public function getMenu(): array
    {
        return $this->menu;
    }



    /**
     * Sets the menu data
     *
     * @param array|null $menu
     * @return static
     */
    public function setMenu(?array $menu): static
    {
        $this->menu = $menu;
        return $this;
    }
}