<?php

namespace Templates\Mdb\Layouts;

use Phoundation\Core\Log;



/**
 * MDB Plugin GridRow class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
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
        $size   = 0;

        foreach ($this->columns as $column) {
            $size   += $column->getSize();
            $return .= $column->render();
        }

        if ($size != 12) {
            Log::warning(tr('GridRow found a row with size ":size" while each row should be exactly size 12', [
                ':size' => $size
            ]));
        }

        return $return . '</div>';
    }
}