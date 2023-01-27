<?php

namespace Templates\Mdb;

use Phoundation\Core\Session;
use Templates\Mdb\Components\Menu;


/**
 * TemplateMenus class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class TemplateMenus extends \Phoundation\Web\Http\Html\Template\TemplateMenus
{
    /**
     * TemplateMenus class constructor
     */
    public function __construct()
    {
        static::$menu_class = Menu::class;
    }


    /**
     * Returns the default top navbar top menu
     *
     * @return Menu
     */
    public static function getPrimaryMenu(): Menu
    {
        $menu   = new static::$menu_class();
        $source = [
            tr('Home') => [
                'url'  => '/',
            ],
            tr('Blog') => [
                'url'  => '/blog',
            ],
            tr('News') => [
                'url'  => '/news',
            ],
            tr('Downloads') => [
                'url'  => '/downloads',
            ]
        ];

        if (Session::getUser()->hasAllRights('admin')) {
            $source[tr('Admin')] = [
                'url'  => '/admin'
            ];
        }

        return $menu->setSource($source);
    }
}