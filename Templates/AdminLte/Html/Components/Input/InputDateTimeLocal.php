<?php

namespace Templates\AdminLte\Html\Components\Input;

use Phoundation\Web\Http\Html\Renderer;



/**
 * Class InputDateTimeLocal
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputDateTimeLocal extends Renderer
{
    /**
     * InputDateTimeLocal class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Input\InputDateTimeLocal $element)
    {
        $element->addClass( 'form-control');
        parent::__construct($element);
    }
}