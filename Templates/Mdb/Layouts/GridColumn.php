<?php

namespace Templates\Mdb\Layouts;



/**
 * MDB Plugin GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class GridColumn extends \Phoundation\Web\Http\Html\Layouts\GridColumn
{
    /**
     * Render this grid column
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if ($this->form) {
            // Return content rendered in a form
            $this->render = '<div class="col' . ($this->tier ? '-' . $this->tier : '') . '-' . $this->size . '">' . $this->form->setContent($this->content)->render() . '</div>';
            $this->form   = null;
            return parent::render();
        }

        $this->render = '<div class="col' . ($this->tier ? '-' . $this->tier : '') . '-' . $this->size . '">' . $this->content . '</div>';
        return parent::render();
    }
}