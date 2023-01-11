<?php

namespace Templates\AdminLte\Components;


use Phoundation\Core\Log;
use Phoundation\Exception\OutOfBoundsException;

/**
 * Class DataEntryForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class DataEntryForm extends \Phoundation\Web\Http\Html\Components\DataEntryForm
{
    /**
     * Renders and returns the HTML for this component
     *
     * @param string|int|null $id
     * @param string|null $label
     * @param string|null $html
     * @param int $size
     * @return string
     */
    protected function renderItem(string|int|null $id, ?string $label, ?string $html, int $size): string
    {
        static $col_size = 12;
        Log::printr($label);
        Log::printr($size);
        Log::printr($col_size);
        $return = '';

        if ($size === -1) {
            if ($col_size === 12) {
                // No row is open right now
                return '';
            }

            // Close the row
            $col_size = 0;

        } else {
            // Keep track of column size, close each row when size 12 is reached
            if ($col_size === 12) {
                // Open a new row
                $return = '<div class="row">';
            }

            $return .= '    <div class="col-sm-' . $size . '">
                            <div class="form-group">
                                <label for="' . $id . '">' . $label . '</label>
                                ' . $html . '
                            </div>
                        </div>';

            $col_size -= $size;

            if ($col_size < 0) {
                throw new OutOfBoundsException(tr('Cannot add column ":label", the row would surpass size 12', [
                    ':label' => $label
                ]));
            }
        }

        if ($col_size == 0) {
            // Close the row
            $col_size = 12;
            $return  .= '</div>';
        }

        return $return;
    }



}