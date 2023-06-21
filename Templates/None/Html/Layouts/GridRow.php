<?php

declare(strict_types=1);


namespace Templates\None\Html\Layouts;

use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin GridRow class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
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

            foreach ($this->element->getSource() as $column) {
                $render .= $column->render();
            }

            $this->render .= $this->element->getForm()->setContent($render)->render();
            $this->element->setForm(null);
        } else {
            foreach ($this->element->getSource() as $column) {
                $this->render .= $column->render();
            }
        }

        $this->render .= '</div>';
        return parent::render();
    }
}