<?php

namespace Templates\None\Html\Components\Widgets\Boxes;

use Phoundation\Web\Http\Html\Renderer;



/**
 * None Plugin Box class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
abstract class Box extends Renderer
{
    /**
     * Box class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Widgets\Boxes\Box $element)
    {
        parent::__construct($element);
    }
}