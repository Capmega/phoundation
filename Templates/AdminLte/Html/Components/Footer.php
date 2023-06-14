<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components;

use Phoundation\Core\Core;
use Phoundation\Web\Http\Html\Renderer;


/**
 * AdminLte Plugin Footer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Footer extends Renderer
{
    /**
     * Footer class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Footer $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the HTML for the footer
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '  <footer class="main-footer">
                    <div class="float-right d-none d-sm-block">
                      <b>' . tr('Mediweb using Phoundation (AdminLte template)') . '</b> ' . Core::FRAMEWORKCODEVERSION . '
                    </div>
                    <strong>Copyright © 2023 <a href="https://medinet.ca" target="_blank">Medinet</a>.</strong> All rights reserved. <br>
                  </footer>';
//        <strong>Copyright © 2014-2021 <a href="https://adminlte.io" target="_blank">AdminLTE.io</a>.</strong> All rights reserved.
//        <strong>Copyright © 2017-2023 <a href="https://phoundation.org" target="_blank">phoundation.org</a>.</strong> All rights reserved. <br>
    }
}