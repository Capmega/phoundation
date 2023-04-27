<?php

namespace Templates\None\Html\Layouts;

use Phoundation\Web\Http\Html\Renderer;


/**
 * None Plugin Layout class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
abstract class Layout extends Renderer
{
    /**
     * Layout class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Layouts\Layout $element)
    {
        parent::__construct($element);
    }
}