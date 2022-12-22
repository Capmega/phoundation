<?php

namespace Phoundation\Web\Http\Html\Elements\Input;



/**
 * Class InputTel
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputTel extends Input
{
    /**
     * InputTel class constructor
     */
    public function __construct()
    {
        $this->type = 'tel';
        parent::__construct();
    }
}