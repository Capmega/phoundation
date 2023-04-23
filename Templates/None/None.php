<?php

namespace Templates\None;

use Phoundation\Web\Http\Html\Template\Template;
use Templates\None\Html\Components\Menu;


/**
 * Class None
 *
 * This is the None template, a template that will build *nothing* and allows (and requires) you to build all the HTML
 * yourself
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package None\Web
 */
class None extends Template
{
    /**
     * Template constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->name        = 'None';
        $this->page_class  = TemplatePage::class;
        $this->menus_class = Menu::class;

        parent::__construct();
    }



    /**
     * Return a description for this template
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'This is the None template, a template that will build *nothing* and allows (and requires) you to build all the HTML yourself';
    }



    /**
     * Returns the path for this template
     *
     * @return string
     */
    public function getPath(): string
    {
        return __DIR__ . '/';
    }
}