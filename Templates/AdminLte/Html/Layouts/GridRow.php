<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Layouts;

use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin GridRow class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class GridRow extends Renderer
{
    /**
     * GridRow class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Layouts\GridRow $element)
    {
        parent::__construct($element);
    }


    /**
     * Render this grid row
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '<div class="row">';

        if ($this->element->getForm()) {
            // Return content rendered in a form
            $render = '';

            foreach ($this->element->getColumns() as $column) {
                $render .= $column->render();
            }

            $this->render .= $this->element->getForm()->setContent($render)->render();
            $this->element->setForm(null);
        } else {
            foreach ($this->element->getColumns() as $column) {
                $this->render .= $column->render();
            }
        }

        $this->render .= '</div>';
        return parent::render();
    }
}