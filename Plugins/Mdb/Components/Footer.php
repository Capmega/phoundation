<?php

namespace Plugins\Mdb\Components;

use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * MDB Plugin Footer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class Footer extends ElementsBlock
{
    /**
     * Footer class constructor
     */
    public function __construct()
    {
        parent::__construct();
    }



    /**
     * Returns a new footer object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }



    /**
     * Renders and returns the HTML for the footer
     *
     * @return string
     */
    public function render(): string
    {
        $html = '<footer id="mdb-footer" class="mt-5" style="background-color: hsl(216, 25%, 95.1%); ">
                    <div class="container py-5">                        
                        <div class="text-center">               
                        <p class="">
                          This is the default Phoundation template built using the Mdb plugin, see <a class="" href="https://mdbootstrap.com/"> <strong>MDBootstrap.com</strong></a> 
                        </p>
                        </div>
                    </div>
                    <div class="text-center p-3" style="background-color: hsl(216, 25%, 90%);">
                        © 2022 Framework Copyright: <a class="" href="https://phoundation.org/"> <strong>phoundation.org</strong></a>,
                        © 2022 Html UI Copyright: <a class="" href="https://mdbootstrap.com/"> <strong>MDBootstrap.com</strong></a>
                    </div>                    
                </footer>';

        return $html;
    }
}