<?php

namespace Templates\AdminLte\Components\Input;



/**
 * Class InputRadio
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputRadio extends \Phoundation\Web\Http\Html\Components\Input\InputRadio
{
    /**
     * InputRadio class constructor
     */
    public function __construct()
    {
        $this->class = 'form-control';
        parent::__construct();
    }
}