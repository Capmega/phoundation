<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components;

use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin Buttons class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Buttons extends Renderer
{
    /**
     * Buttons class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Buttons $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the buttons HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '';

        if ($this->render_object->getGroup()) {
            $this->render .= '<div class="btn-group" role="group" aria-label="Button group">';
        }

        foreach ($this->render_object->getSource() as $button) {
            $this->render .= $button->render(). ' ';
        }

        if ($this->render_object->getGroup()) {
            $this->render .= '</div>';
        }

        return parent::render();
    }
}