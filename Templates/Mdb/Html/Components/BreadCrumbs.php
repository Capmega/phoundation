<?php

namespace Templates\Mdb\Html\Components;

use Phoundation\Web\Http\Html\Renderer;



/**
 * Mdn Plugin BreadCrumbs class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class BreadCrumbs extends Renderer
{
    /**
     * BreadCrumbs class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\BreadCrumbs $element)
    {
        parent::__construct($element);
    }
}