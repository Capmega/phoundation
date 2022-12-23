<?php

namespace Phoundation\Web\Http\Html\Components\Input;



/**
 * Class InputHidden
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class InputHidden extends Input
{
    /**
     * InputHidden class constructor
     */
    public function __construct()
    {
        $this->type = 'hidden';
        parent::__construct();
    }
}