<?php

namespace Templates\None\Html\Components;

use Phoundation\Web\Http\Html\Renderer;



/**
 * None Plugin Button class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class Button extends Renderer
{
    /**
     * Button class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Button $element)
    {
        parent::__construct($element);
    }
}