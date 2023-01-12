<?php

namespace Templates\AdminLte\Components\Input;



/**
 * Class InputCheckbox
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputCheckbox extends \Phoundation\Web\Http\Html\Components\Input\InputCheckbox
{
    /**
     * InputCheckbox class constructor
     */
    public function __construct()
    {
        $this->class = 'form-check-input';
        parent::__construct();
    }
}