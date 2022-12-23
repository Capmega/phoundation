<?php

namespace Templates\AdminLte\Layouts;

use Phoundation\Exception\OutOfBoundsException;



/**
 * AdminLte Plugin GridColumn class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class GridColumn extends \Phoundation\Web\Http\Html\Layouts\GridColumn
{
    /**
     * Render this grid column
     *
     * @return string
     */
    public function render(): string
    {
        if (!$this->size) {
            throw new OutOfBoundsException(tr('Cannot render GridColumn, no size specified'));
        }

        return '<div class="col' . ($this->tier ? '-' . $this->tier : '') . '-' . $this->size . '">' . $this->content . '</div>';
    }
}