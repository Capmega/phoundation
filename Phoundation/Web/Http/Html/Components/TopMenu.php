<?php

namespace Phoundation\Web\Http\Html\Components;



/**
 * AdminLte Plugin TopMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TopMenu extends Menu
{
    /**
     * Default the top panel menu
     *
     * @return array
     */
    public function getSource(): array
    {
        if (!isset($this->source)) {
            $this->source = [
                tr('Front-end') => ['url' => '/'],
            ];
        }

        return $this->source;
    }



    /**
     * Render the HTML for the AdminLte top menu
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '';;
    }
}