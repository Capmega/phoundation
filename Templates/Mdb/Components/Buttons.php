<?php

namespace Templates\Mdb\Components;



use Templates\Mdb\Elements\ButtonProperties;

/**
 * MDB Plugin Buttons class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class Buttons extends \Phoundation\Web\Http\Html\Components\Buttons
{
    use ButtonProperties;



   /**
     * Renders and returns the buttons HTML
     */
    public function render(): string
    {
        $html = '';

        if ($this->group) {
            $html .= '<div class="btn-group" role="group" aria-label="Button group">';
        }

        foreach ($this->buttons as $button) {
            $html .= $button->render();
        }

        if ($this->group) {
            $html .= '</div>';
        }

        return $html;
    }
}