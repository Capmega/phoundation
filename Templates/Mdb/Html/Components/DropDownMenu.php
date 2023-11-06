<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Components;

use Phoundation\Web\Html\Renderer;


/**
 * MDB Plugin DropDownMenu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class DropDownMenu extends Renderer
{
    /**
     * DropDownMenu class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\DropDownMenu $element)
    {
        parent::__construct($element);
    }
}