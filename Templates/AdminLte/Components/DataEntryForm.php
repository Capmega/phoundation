<?php

namespace Templates\AdminLte\Components;

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
     * @param string|null $html
     * @param array|null $data
     * @return string|null
     */
    protected function renderItem(string|int|null $id, ?string $html, ?array $data): ?string
    {
        static $col_size = 12;
// TODO Leave the following lines for easy debugging form layouts
//        Log::printr($label);
//        Log::printr($size);
//        Log::printr($col_size);
        $return = '';

        if ($data === null) {
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

            switch ($data['type']) {
                case 'checkbox':
                    $return .= '    <div class="col-sm-' . $data['size'] . '">
                                        <div class="form-group">
                                            <label for="' . $id . '">' . $data['label'] . '</label>
                                            <div class="form-check">
                                                ' . $html . '
                                                <label class="form-check-label" for="' . $id . '">' . $data['label'] . '</label>
                                            </div>
                                        </div>
                                    </div>';
                    break;

                default:
                    $return .= '    <div class="col-sm-' . $data['size'] . '">
                                        <div class="form-group">
                                            <label for="' . $id . '">' . $data['label'] . '</label>
                                            ' . $html . '
                                        </div>
                                    </div>';
            }

            $col_size -= $data['size'];

            if ($col_size < 0) {
                throw new OutOfBoundsException(tr('Cannot add column ":label", the row would surpass size 12', [
                    ':label' => $data['label']
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