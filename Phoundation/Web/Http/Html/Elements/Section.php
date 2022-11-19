<?php

namespace Phoundation\Web\Http\Html\Elements;



/**
 * Section class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Section extends Element
{
    /**
     * Section class constructor
     */
    public function __construct()
    {
        parent::__construct();
        parent::setElement('section');
    }
}