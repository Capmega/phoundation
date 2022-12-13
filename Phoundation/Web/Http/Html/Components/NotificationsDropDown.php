<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * NotificationsDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class NotificationsDropDown extends ElementsBlock
{
    /**
     * Renders and returns the NavBar
     *
     * @return string|null
     */
    abstract public function render(): ?string;
}