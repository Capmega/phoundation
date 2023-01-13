<?php

namespace Templates\AdminLte\Layouts;



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
class GridRow extends \Phoundation\Web\Http\Html\Layouts\GridRow
{
    /**
     * Render this grid row
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '<div class="row">';

        if ($this->form) {
            // Return content rendered in a form
            $render = '';

            foreach ($this->columns as $column) {
                $render = $column->render();
            }

            $this->form->setContent($render)->render();
            $this->form   = null;
        } else {
            foreach ($this->columns as $column) {
                $this->render .= $column->render();
            }
        }

        $this->render .= '</div>';
        return parent::render();
    }
}