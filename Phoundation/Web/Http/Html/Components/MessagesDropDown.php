<?php

namespace Phoundation\Web\Http\Html\Components;


/**
 * MessagesDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
abstract class MessagesDropDown extends ElementsBlock
{
    /**
     * Renders and returns the MessagesDropDown component
     *
     * @return string|null
     */
    abstract public function render(): ?string;
}