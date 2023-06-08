<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;


/**
 * Div class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Div extends Element
{
    /**
     * Form class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setElement('div');
    }
}