<?php

namespace Templates\AdminLte\Components;



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
class Table extends \Phoundation\Web\Http\Html\Components\Table
{
    /**
     * Table class constructor
     */
    public function __construct()
    {
        $this->addClass('table');
        parent::__construct();
    }
}