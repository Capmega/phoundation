<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components\Input;

use Phoundation\Exception\OutOfBoundsException;


/**
 * Class InputDateRange
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputDateRange extends Input
{
    /**
     * InputText class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\Input\InputText $element)
    {
        $element->addClass('form-control');
        parent::__construct($element);
    }


    /**
     * Render and return the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->render_object->getName()) {
            throw new OutOfBoundsException(tr('Cannot render InputDateRange object, no HTML name attribute specified'));
        }

        // Ensure these two classes are always available
        $this->render_object->addClasses(['form-control', 'float-right']);

        $html = '   <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="far fa-calendar-alt"></i>
                            </span>
                        </div>
                        ' . parent::render() . '
                    </div>';

        // Add date range picker JS
        return $html;
    }
}
