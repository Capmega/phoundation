<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * Class InputCheckbox
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputCheckbox extends Input
{
    /**
     * InputCheckbox class constructor
     */
    public function __construct()
    {
        $this->type = 'checkbox';
        parent::__construct();
    }
}