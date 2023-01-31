<?php

namespace Templates\AdminLte\Html\Components\Input;

use Phoundation\Web\Http\Html\Renderer;



/**
 * Class InputMultiButtonText
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputMultiButtonText extends Renderer
{
    /**
     * InputMultiButtonText class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputMultiButtonText $element)
    {
        parent::__construct($element);
    }



    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $options = '';

        // Build the options list
        foreach ($this->element->getSource() as $url => $label) {
            if (str_starts_with($label, '#')) {
                // Any label starting with # is a divider
                $options .= '<li class="dropdown-divider"></li>';
            } else {
                $options .= '<li class="dropdown-item"><a href="' . $url . '">' . $label . '</a></li>';
            }
        }

        // Render the entire object
        $this->render = '   <div class="input-group input-group-lg mb-3">
                                <div class="input-group-prepend">
                                ' . $this->element->getButton()->render() . '                                
                                <ul class="dropdown-menu" style="">
                                    ' . $options . '
                                </ul>
                                </div>
                                
                                ' . $this->element->getInput()->render() . '
                            </div>';

        return parent::render();
    }
}

//
//
//<div class="input-group input-group-lg mb-3">
//    <div class="input-group-prepend">
//    <button type="button" class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
//        Action
//    </button>
//    <ul class="dropdown-menu">
//    <li class="dropdown-item"><a href="#">Action</a></li>
//    <li class="dropdown-item"><a href="#">Another action</a></li>
//    <li class="dropdown-item"><a href="#">Something else here</a></li>
//    <li class="dropdown-divider"></li>
//    <li class="dropdown-item"><a href="#">Separated link</a></li>
//    </ul>
//    </div>
//
//    <input type="text" class="form-control">
//</div>
//
//
//
//
//
//
//
//
//<div class="input-group input-group-lg mb-3">
//    <div class="input-group-prepend">
//    <button type="submit" class="btn"></button>
//    Action
//    </button>
//    <ul class="dropdown-menu" style="">
//    <li class="dropdown-item"><a href="All">all</a></li><li class="dropdown-item"><a href="FWD">id</a></li><li class="dropdown-item"><a href="Forwarder">forwarder</a></li><li class="dropdown-item"><a href="MAWB">mawb</a></li><li class="dropdown-item"><a href="HAWB">hawb</a></li><li class="dropdown-item"><a href="Cargo control">cargo_control</a></li><li class="dropdown-item"><a href="Customer reference">customer_reference</a></li>
//    </ul>
//    </div>
//
//    <input type="text" id="type[]" name="type[]" class="form-control" />
//</div>