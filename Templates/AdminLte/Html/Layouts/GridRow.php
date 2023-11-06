<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Layouts;

use Phoundation\Web\Html\Renderer;


/**
 * AdminLte Plugin GridRow class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class GridRow extends Renderer
{
    /**
     * GridRow class constructor
     */
    public function __construct(\Phoundation\Web\Html\Layouts\GridRow $element)
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
        $class        = $this->render_object->getClass();
        $this->render = '<div class="row' . ($class ? ' ' . $class : '') . '">';

        if ($this->render_object->getForm()) {
            // Return content rendered in a form
            $render = '';

            foreach ($this->render_object->getSource() as $column) {
                $render .= $column->render();
            }

            $this->render .= $this->render_object->getForm()->setContent($render)->render();
            $this->render_object->setForm(null);
        } else {
            foreach ($this->render_object->getSource() as $column) {
                $this->render .= $column->render();
            }
        }

        $this->render .= '</div>';
        return parent::render();
    }
}