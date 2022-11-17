<?php

namespace Plugins\Mdb;

use Phoundation\Web\Http\Html\ElementsBlock;



/**
 * Phoundation template class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class ProfileImage extends ElementsBlock
{
    /**
     * The profile image
     *
     * @var string|null
     */
    protected ?string $image = null;

    /**
     * The profile menu
     *
     * @var array|null $menu
     */
    protected ?array $menu = null;

    

    /**
     * Renders and returns the profile image block HTML
     *
     * @return string
     */
    public function render(): string
    {

    }
}