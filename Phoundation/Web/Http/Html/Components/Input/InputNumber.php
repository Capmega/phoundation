<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * Class InputNumber
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputNumber extends Input
{
    /**
     * InputNumber class constructor
     */
    public function __construct()
    {
        $this->type = 'number';
        parent::__construct();
    }
}