<?php

namespace Templates\AdminLte\Html\Components;

use Phoundation\Web\Http\Html\Renderer;



/**
 * AdminLte Plugin Table class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Table extends Renderer
{
    /**
     * Table class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Table $element)
    {
        $element->addClass('table');
        parent::__construct($element);
    }
}