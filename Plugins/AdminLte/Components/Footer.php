<?php

namespace Plugins\AdminLte\Components;

use Phoundation\Web\Http\Html\Elements\ElementsBlock;



/**
 * AdminLte Plugin Footer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\AdminLte
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
     * @return string|null
     */
    public function render(): ?string
    {
        $html = '<footer class="main-footer">
                    <div class="float-right d-none d-sm-block">
                      <b>' . tr('Phoundation (AdminLte template) Version') . '</b> ' . FRAMEWORKDBVERSION . '
                    </div>
                    <strong>Copyright © 2017-2023 <a href="https://phoundation.org">phoundation.org</a>.</strong> All rights reserved. <br>
                    <strong>Copyright © 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
                  </footer>';

        return $html;
    }
}