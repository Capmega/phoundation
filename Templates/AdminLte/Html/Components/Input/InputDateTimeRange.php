<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components\Input;



/**
 * Class InputDateTimeRange
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputDateTimeRange extends Input
{
    /**
     * InputText class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputText $element)
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
//        <div class="form-group">
//            <label>Date and time range:</label>
//            <div class="input-group">
//                <div class="input-group-prepend">
//                    <span class="input-group-text"><i class="far fa-clock"></i></span>
//                </div>
//                <input type="text" class="form-control float-right" id="reservationtime">
//            </div>
//        </div>

//        //Date range picker with time picker
//        $('#reservationtime').daterangepicker({
//          timePicker: true,
//          timePickerIncrement: 30,
//          locale: {
//            format: 'MM/DD/YYYY hh:mm A'
//          }
//        })

        return parent::render();
    }
}