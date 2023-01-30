<?php

namespace Templates\Mdb\Components;

use Phoundation\Web\Http\Html\Components\ButtonProperties;



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
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '';

        if ($this->group) {
            $this->render .= '<div class="btn-group" role="group" aria-label="Button group">';
        }

        foreach ($this->source as $button) {
            $this->render .= $button->render();
        }

        if ($this->group) {
            $this->render .= '</div>';
        }

        return parent::render();
    }
}