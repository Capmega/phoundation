<?php

namespace Templates\Mdb\Components;



/**
 * MDB Plugin Footer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class Footer extends \Phoundation\Web\Http\Html\Components\Footer
{
    /**
     * Renders and returns the HTML for the footer
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->render = ' <footer id="mdb-footer" class="mt-5" style="background-color: hsl(216, 25%, 95.1%); ">
                            <div class="container py-5">                        
                                <div class="text-center">               
                                <p class="">
                                  This is the default Mdb template built using the Mdb plugin, see <a class="" href="https://mdbootstrap.com/"> <strong>MDBootstrap.com</strong></a>
                                </p>
                                <a target="_blank" href="https://mdbootstrap.com/docs/">MDB documentation</a> 
                                </div>
                            </div>
                            <div class="text-center p-3" style="background-color: hsl(216, 25%, 90%);">
                                © 2022 Framework Copyright: <a class="" href="https://phoundation.org/"> <strong>phoundation.org</strong></a>,
                                © 2022 Html UI Copyright: <a class="" href="https://mdbootstrap.com/"> <strong>MDBootstrap.com</strong></a>
                            </div>                    
                        </footer>';

        return $this->render;
    }
}