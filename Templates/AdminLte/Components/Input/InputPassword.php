<?php

namespace Templates\AdminLte\Components\Input;



/**
 * Class InputPassword
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class InputPassword extends \Phoundation\Web\Http\Html\Components\Input\InputPassword
{
    /**
     * InputPassword class constructor
     */
    public function __construct()
    {
        $this->class = 'form-control';
        parent::__construct();
    }
}