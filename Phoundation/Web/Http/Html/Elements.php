<?php

namespace Phoundation\Web\Http\Html;



/**
 * Class Elements
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Elements
{
    /**
     * Returns a new generic element
     *
     * @param string $type
     * @return Element
     */
    public static function element(string $type): Element
    {
        return new Element($type);
    }



    /**
     * Returns a new img element
     *
     * @return Img
     */
    public static function img(): Img
    {
        return new Img();
    }



    /**
     * Returns a new table element
     *
     * @return Table
     */
    public static function table(): Table
    {
        return new Table();
    }



    /**
     * Returns a new select element
     *
     * @return Select
     */
    public static function select(): Select
    {
        return new Select();
    }
}