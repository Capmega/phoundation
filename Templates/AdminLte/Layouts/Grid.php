<?php

namespace Templates\AdminLte\Layouts;



/**
 * AdminLte Plugin Grid class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Grid extends \Phoundation\Web\Http\Html\Layouts\Grid
{
    /**
     * Render the HTML for this grid
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = '';

        if ($this->form) {
            // Return content rendered in a form
            $render = '';

            foreach ($this->rows as $row) {
                $render .= $row->render();
            }

            $this->form->setContent($render)->render();
            $this->form   = null;
        } else {
            foreach ($this->rows as $row) {
                $this->render .= $row->render();
            }
        }

        return parent::render();
    }
}