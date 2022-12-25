<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * Class InputButton
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputButton extends Input
{
    /**
     * InputButton class constructor
     */
    public function __construct()
    {
        $this->type = 'button';
        parent::__construct();
    }
}