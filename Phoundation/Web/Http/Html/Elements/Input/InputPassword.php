<?php

namespace Phoundation\Web\Http\Html\Elements\Input;



/**
 * Class InputPassword
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputPassword extends Input
{
    /**
     * InputPassword class constructor
     */
    public function __construct()
    {
        $this->type = 'password';
        parent::__construct();
    }
}