<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Components;

use Phoundation\Web\Http\Html\Components\ButtonProperties;
use Phoundation\Web\Http\Html\Renderer;


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

        if ($this->element->getGroup()) {
            $this->render .= '<div class="btn-group" role="group" aria-label="Button group">';
        }

        foreach ($this->element->getSource() as $button) {
            $this->render .= $button->render();
        }

        if ($this->element->getGroup()) {
            $this->render .= '</div>';
        }

        return parent::render();
    }
}