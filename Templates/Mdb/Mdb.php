<?php

declare(strict_types=1);


namespace Templates\Mdb;

use Phoundation\Web\Http\Html\Template\Template;
use Templates\Mdb\Html\Components\Menu;

/**
 * Class Mdb
 *
 * This is the Mdb template
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Mdb\Web
 */
class Mdb extends Template
{
    /**
     * Template constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->name        = 'Mdb';
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
        return 'This is the default template for your website. You are free to add or build other templates';
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