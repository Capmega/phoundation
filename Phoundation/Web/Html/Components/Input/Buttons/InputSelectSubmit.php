<?php

/**
 * Class InputSelectSubmit
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Exception\UnderConstructionException;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Exception\HtmlException;

class InputSelectSubmit extends InputSelect
{
    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string|null
     */
    public function render(): ?string
    {
        throw new UnderConstructionException();
        /*
         * Return HTML for a multi select submit button. This button, once clicked, will show a list of selectable submit buttons.
         *
         * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
         * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         * @category Function reference
         * @package html
         * @see html_select()
         *
         * @param params $params The parameters for this HTML select button
         * @params string name The HTML name for the button
         * @params string id The HTML id for the button
         * @params boolean autosubmit If set to true, clicking the button will automatically subimit the form where this button is placed
         * @params string none The text that will be shown when the button is closed and not used
         * @params midex buttons The buttons to be shown. This may be specified by array, or PDO SQL statement
         * @return string The HTML for the button selector
         */
        array_params($params);
        array_default($params, 'name', 'multisubmit');
        array_default($params, 'id', '');
        array_default($params, 'autosubmit', true);
        array_default($params, 'none', tr('Select action'));
        array_default($params, 'buttons', []);
        /*
         * Build the html_select resource from the buttons
         */
        if (is_object($params['buttons'])) {
            /*
             * This should be a PDO statement, do nothing, html_select will take
             * care of it
             */
            $params['resource'] = $params['buttons'];

        } elseif (is_array($params['buttons'])) {
            foreach ($params['buttons'] as $key => $value) {
                if (is_numeric($key)) {
                    $key = $value;
                }
                $params['resource'][$key] = $value;
            }

        } else {
            $type = gettype($params['buttons']);
            if ($type === 'object') {
                $type .= tr(' of class :class', [':class' => get_class($params['buttons'])]);
            }
            throw new HtmlException(tr('Invalid data type specified for params "buttons", it should be an array or PDO statement object, but it is an ":type"', [
                ':type' => $type,
            ]));
        }

        return parent::render();
    }
}
