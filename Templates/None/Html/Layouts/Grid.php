<?php

namespace Templates\None\Html\Layouts;

use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin Grid class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class Grid extends Renderer
{
    /**
     * Grid class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Layouts\Grid $element)
    {
        parent::__construct($element);
    }


    /**
     * Render the HTML for this grid
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '<div class="container-fluid">';

        if ($this->element->getForm()) {
            // Return content rendered in a form
            $render = '';

            foreach ($this->element->getRows() as $row) {
                $render .= $row->render();
            }

            $this->render .= $this->element->getForm()->setContent($render)->render();
            $this->element->setForm(null);
        } else {
            foreach ($this->element->getRows() as $row) {
                $this->render .= $row->render();
            }
        }

        $this->render .= '</div>';
        return parent::render();
    }
}