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
        $return = '<div class="row">';

        foreach ($this->columns as $column) {
            $return .= $column->render();
        }

        return $return . '</div>';
    }
}