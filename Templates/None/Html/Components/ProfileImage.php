<?php

namespace Templates\None\Html\Components;

use Phoundation\Content\Images\Image;
use Phoundation\Core\Session;
use Phoundation\Web\Http\Html\Components\DropDownMenu;
use Phoundation\Web\Http\Html\Renderer;
use Phoundation\Web\Page;

/**
 * None Plugin ProfileImage class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class ProfileImage extends Renderer
{
    /**
     * ProfileImage class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\ProfileImage $element)
    {
        parent::__construct($element);
    }
}