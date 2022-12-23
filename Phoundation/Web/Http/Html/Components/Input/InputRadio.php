<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * Class InputRadio
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputRadio extends Input
{
    /**
     * InputRadio class constructor
     */
    public function __construct()
    {
        $this->type = 'radio';
        parent::__construct();
    }
}